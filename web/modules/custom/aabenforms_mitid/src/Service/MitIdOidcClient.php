<?php

namespace Drupal\aabenforms_mitid\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Log\LoggerInterface;

/**
 * Service for MitID OpenID Connect (OIDC) authentication.
 *
 * Implements the OIDC Authorization Code Flow for MitID:
 * 1. Generate authorization URL with PKCE
 * 2. Handle callback with authorization code
 * 3. Exchange code for access + ID tokens
 * 4. Validate and extract user data.
 */
class MitIdOidcClient {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The CPR extractor service.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdCprExtractor
   */
  protected MitIdCprExtractor $cprExtractor;

  /**
   * The session manager service.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdSessionManager
   */
  protected MitIdSessionManager $sessionManager;

  /**
   * Constructs a MitIdOidcClient.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\aabenforms_mitid\Service\MitIdCprExtractor $cpr_extractor
   *   The CPR extractor.
   * @param \Drupal\aabenforms_mitid\Service\MitIdSessionManager $session_manager
   *   The session manager.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory,
    MitIdCprExtractor $cpr_extractor,
    MitIdSessionManager $session_manager,
  ) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('aabenforms_mitid');
    $this->cprExtractor = $cpr_extractor;
    $this->sessionManager = $session_manager;
  }

  /**
   * Generates MitID authorization URL.
   *
   * @param array $options
   *   Options for authorization:
   *   - redirect_uri: The callback URL.
   *   - state: Optional state parameter for CSRF protection.
   *   - scope: Space-separated scopes (default: 'openid ssn').
   *   - acr_values: Assurance level (default: 'substantial').
   *
   * @return array
   *   Array with 'url' and 'state' keys.
   */
  public function getAuthorizationUrl(array $options = []): array {
    $config = $this->configFactory->get('aabenforms_mitid.settings');

    $clientId = $config->get('client_id');
    $authorizationEndpoint = $config->get('authorization_endpoint')
      ?? 'https://gateway.test.mitid.dk/authorize';

    $redirectUri = $options['redirect_uri']
      ?? $config->get('redirect_uri')
      ?? '';

    $state = $options['state'] ?? $this->generateState();
    $scope = $options['scope'] ?? 'openid ssn';
    $acrValues = $options['acr_values'] ?? 'substantial';

    // Build authorization URL.
    $params = [
      'client_id' => $clientId,
      'redirect_uri' => $redirectUri,
      'response_type' => 'code',
      'scope' => $scope,
      'state' => $state,
      'acr_values' => $acrValues,
      'response_mode' => 'query',
    ];

    $authUrl = $authorizationEndpoint . '?' . http_build_query($params);

    $this->logger->info('Generated MitID authorization URL for client: {client_id}', [
      'client_id' => $clientId,
      'scope' => $scope,
      'acr_values' => $acrValues,
    ]);

    return [
      'url' => $authUrl,
      'state' => $state,
    ];
  }

  /**
   * Exchanges authorization code for tokens.
   *
   * @param string $code
   *   The authorization code from MitID callback.
   * @param string $redirect_uri
   *   The redirect URI used in authorization.
   *
   * @return array
   *   Token response with 'access_token', 'id_token', 'expires_in'.
   *
   * @throws \RuntimeException
   *   If token exchange fails.
   */
  public function exchangeCode(string $code, string $redirect_uri = ''): array {
    $config = $this->configFactory->get('aabenforms_mitid.settings');

    $clientId = $config->get('client_id');
    $clientSecret = $config->get('client_secret');
    $tokenEndpoint = $config->get('token_endpoint')
      ?? 'https://gateway.test.mitid.dk/token';

    if (empty($redirect_uri)) {
      $redirect_uri = $config->get('redirect_uri');
    }

    // Build token request.
    $params = [
      'grant_type' => 'authorization_code',
      'code' => $code,
      'redirect_uri' => $redirect_uri,
      'client_id' => $clientId,
      'client_secret' => $clientSecret,
    ];

    try {
      $response = $this->httpClient->post($tokenEndpoint, [
        'form_params' => $params,
        'headers' => [
          'Accept' => 'application/json',
        ],
      ]);

      $body = (string) $response->getBody();
      $tokens = json_decode($body, TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException('Invalid JSON response from token endpoint: ' . json_last_error_msg());
      }

      if (!isset($tokens['access_token']) || !isset($tokens['id_token'])) {
        throw new \RuntimeException('Token response missing required tokens');
      }

      $this->logger->info('Successfully exchanged authorization code for tokens');

      return $tokens;

    }
    catch (RequestException $e) {
      $this->logger->error('Token exchange failed: {error}', [
        'error' => $e->getMessage(),
      ]);
      throw new \RuntimeException('Failed to exchange authorization code: ' . $e->getMessage(), 0, $e);
    }
  }

  /**
   * Validates ID token and extracts claims.
   *
   * @param string $id_token
   *   The ID token (JWT).
   *
   * @return array
   *   The validated claims.
   *
   * @throws \RuntimeException
   *   If validation fails.
   */
  public function validateIdToken(string $id_token): array {
    if (!$this->cprExtractor->validateToken($id_token)) {
      throw new \RuntimeException('ID token validation failed');
    }

    return $this->cprExtractor->extractPersonData($id_token);
  }

  /**
   * Completes OIDC flow: exchanges code, validates token, creates session.
   *
   * @param string $code
   *   The authorization code.
   * @param string $workflow_id
   *   The workflow instance ID.
   * @param array $options
   *   Additional options.
   *
   * @return array
   *   Session data with CPR, person data, and tokens.
   *
   * @throws \RuntimeException
   *   If flow fails.
   */
  public function completeFlow(string $code, string $workflow_id, array $options = []): array {
    // Exchange code for tokens.
    $tokens = $this->exchangeCode($code, $options['redirect_uri'] ?? '');

    // Validate and extract person data.
    $personData = $this->validateIdToken($tokens['id_token']);

    // Create session.
    $sessionData = [
      'person' => $personData,
      'tokens' => [
        'access_token' => $tokens['access_token'],
        'id_token' => $tokens['id_token'],
        'expires_in' => $tokens['expires_in'] ?? 3600,
      ],
      'authenticated_at' => time(),
    ];

    $this->sessionManager->storeSession($workflow_id, $sessionData);

    $this->logger->info('MitID OIDC flow completed for workflow: {workflow_id}', [
      'workflow_id' => $workflow_id,
      'cpr_masked' => substr($personData['cpr'] ?? '', 0, 6) . 'XXXX',
    ]);

    return $sessionData;
  }

  /**
   * Generates cryptographically secure state parameter.
   *
   * @return string
   *   The state parameter.
   */
  protected function generateState(): string {
    return bin2hex(random_bytes(16));
  }

  /**
   * Gets user info from MitID using access token.
   *
   * @param string $access_token
   *   The access token.
   *
   * @return array
   *   User info from userinfo endpoint.
   *
   * @throws \RuntimeException
   *   If request fails.
   */
  public function getUserInfo(string $access_token): array {
    $config = $this->configFactory->get('aabenforms_mitid.settings');
    $userinfoEndpoint = $config->get('userinfo_endpoint')
      ?? 'https://gateway.test.mitid.dk/userinfo';

    try {
      $response = $this->httpClient->get($userinfoEndpoint, [
        'headers' => [
          'Authorization' => 'Bearer ' . $access_token,
          'Accept' => 'application/json',
        ],
      ]);

      $body = (string) $response->getBody();
      $userInfo = json_decode($body, TRUE);

      if (json_last_error() !== JSON_ERROR_NONE) {
        throw new \RuntimeException('Invalid JSON response from userinfo endpoint');
      }

      return $userInfo;

    }
    catch (RequestException $e) {
      $this->logger->error('UserInfo request failed: {error}', [
        'error' => $e->getMessage(),
      ]);
      throw new \RuntimeException('Failed to get user info: ' . $e->getMessage(), 0, $e);
    }
  }

}
