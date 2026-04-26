<?php

namespace Drupal\aabenforms_mitid\Controller;

use Drupal\aabenforms_mitid\Service\MitIdOidcClient;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for MitID OIDC authentication endpoints.
 */
class MitIdController extends ControllerBase {

  /**
   * The MitID OIDC client.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdOidcClient
   */
  protected MitIdOidcClient $oidcClient;

  /**
   * Constructs a MitIdController.
   *
   * @param \Drupal\aabenforms_mitid\Service\MitIdOidcClient $oidc_client
   *   The OIDC client service.
   */
  public function __construct(MitIdOidcClient $oidc_client) {
    $this->oidcClient = $oidc_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('aabenforms_mitid.oidc_client')
    );
  }

  /**
   * Initiates MitID login flow.
   *
   * Route: /mitid/login.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   Redirect to MitID authorization.
   */
  public function login(Request $request): TrustedRedirectResponse {
    // Get workflow ID from query params or generate. The generated ID is the
    // bearer capability for the session payload, so it must be unguessable -
    // bin2hex(random_bytes(...)) gives 32 hex chars (128 bits of entropy).
    $workflowId = $request->query->get('workflow_id') ?? 'wf_' . bin2hex(random_bytes(16));

    // Get redirect URL (where to return after auth).
    // Validate that it's an internal path to prevent open redirect attacks.
    $returnUrl = $request->query->get('return_url') ?? '/';

    // Strip any external URLs - only allow internal paths or trusted frontend origins.
    if (preg_match('#^https?://#i', $returnUrl)) {
      $currentHost = $request->getSchemeAndHttpHost();
      $returnHost = parse_url($returnUrl, PHP_URL_SCHEME) . '://' . parse_url($returnUrl, PHP_URL_HOST);
      $returnHostWithPort = $returnHost;
      $port = parse_url($returnUrl, PHP_URL_PORT);
      if ($port) {
        $returnHostWithPort = $returnHost . ':' . $port;
      }

      // Allow the current site and configured CORS origins (frontend).
      $corsOrigin = getenv('CORS_ALLOW_ORIGIN') ?: '';
      $allowedOrigins = array_filter([$currentHost, $corsOrigin]);

      if (!in_array($returnHost, $allowedOrigins, TRUE) && !in_array($returnHostWithPort, $allowedOrigins, TRUE)) {
        $this->getLogger('aabenforms_mitid')->warning('Rejected external return_url: @url', ['@url' => $returnUrl]);
        $returnUrl = '/';
      }
    }
    elseif (str_starts_with($returnUrl, '//')) {
      // Reject protocol-relative URLs (e.g., //evil.com).
      $this->getLogger('aabenforms_mitid')->warning('Rejected protocol-relative return_url: @url', ['@url' => $returnUrl]);
      $returnUrl = '/';
    }
    elseif (!str_starts_with($returnUrl, '/')) {
      // Ensure path starts with / for relative URLs.
      $returnUrl = '/' . $returnUrl;
    }

    // Generate authorization URL.
    $result = $this->oidcClient->getAuthorizationUrl([
      'redirect_uri' => Url::fromRoute('aabenforms_mitid.callback', [], ['absolute' => TRUE])->toString(),
    ]);

    // Store state and workflow ID in session for CSRF protection.
    $tempStore = \Drupal::service('tempstore.private')->get('aabenforms_mitid');
    $tempStore->set('oauth_state_' . $result['state'], [
      'workflow_id' => $workflowId,
      'return_url' => $returnUrl,
      'created' => time(),
      'code_verifier' => $result['code_verifier'] ?? '',
      'nonce' => $result['nonce'] ?? '',
    ]);

    // Redirect to MitID (external URL - use TrustedRedirectResponse).
    return new TrustedRedirectResponse($result['url']);
  }

  /**
   * Handles MitID OIDC callback.
   *
   * Route: /mitid/callback.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\JsonResponse
   *   Redirect to return URL or JSON response on error.
   */
  public function callback(Request $request): RedirectResponse|JsonResponse {
    $code = $request->query->get('code');
    $state = $request->query->get('state');
    $error = $request->query->get('error');

    // Handle errors from MitID.
    if ($error) {
      $errorDescription = $request->query->get('error_description', 'Unknown error');
      $this->getLogger('aabenforms_mitid')->error('MitID authentication error: {error}', [
        'error' => $error,
        'description' => $errorDescription,
      ]);

      $this->messenger()->addError($this->t('Authentication failed: @error', ['@error' => $errorDescription]));
      return new RedirectResponse('/');
    }

    // Validate required parameters.
    if (!$code || !$state) {
      return new JsonResponse(['error' => 'Missing required parameters'], 400);
    }

    // Verify state (CSRF protection).
    $tempStore = \Drupal::service('tempstore.private')->get('aabenforms_mitid');
    $stateData = $tempStore->get('oauth_state_' . $state);

    if (!$stateData) {
      $this->getLogger('aabenforms_mitid')->error('Invalid OAuth state parameter');
      return new JsonResponse(['error' => 'Invalid state parameter'], 400);
    }

    // Extract stored data.
    $workflowId = $stateData['workflow_id'];
    $returnUrl = $stateData['return_url'];

    // Clean up state.
    $tempStore->delete('oauth_state_' . $state);

    try {
      // Complete OIDC flow. Pass code_verifier + nonce stashed alongside
      // the state record so PKCE-enabled providers accept the token
      // exchange and id_token replay protection works.
      $sessionData = $this->oidcClient->completeFlow($code, $workflowId, [
        'redirect_uri' => Url::fromRoute('aabenforms_mitid.callback', [], ['absolute' => TRUE])->toString(),
        'code_verifier' => $stateData['code_verifier'] ?? '',
        'nonce' => $stateData['nonce'] ?? '',
      ]);

      // Store session ID in user's session for frontend access.
      $request->getSession()->set('mitid_workflow_id', $workflowId);
      $request->getSession()->set('mitid_authenticated', TRUE);
      $request->getSession()->set('mitid_cpr', $sessionData['cpr'] ?? NULL);

      $this->messenger()->addStatus($this->t('Successfully authenticated with MitID'));

      // Append session ID to return URL so frontend can retrieve session data.
      $separator = str_contains($returnUrl, '?') ? '&' : '?';
      $redirectUrl = $returnUrl . $separator . 'session=' . urlencode($workflowId);

      // Use TrustedRedirectResponse when the return URL is external - the
      // open-redirect guard in login() already validated the origin against
      // CORS_ALLOW_ORIGIN, so this is safe.
      if (preg_match('#^https?://#i', $redirectUrl)) {
        return new TrustedRedirectResponse($redirectUrl);
      }
      return new RedirectResponse($redirectUrl);

    }
    catch (\Exception $e) {
      $this->getLogger('aabenforms_mitid')->error('MitID callback failed: {error}', [
        'error' => $e->getMessage(),
      ]);

      $this->messenger()->addError($this->t('Authentication failed. Please try again.'));
      return new RedirectResponse('/');
    }
  }

  /**
   * Logout endpoint.
   *
   * Route: /mitid/logout.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to homepage.
   */
  public function logout(Request $request): RedirectResponse {
    // Clear MitID session data.
    $request->getSession()->remove('mitid_workflow_id');
    $request->getSession()->remove('mitid_authenticated');
    $request->getSession()->remove('mitid_cpr');

    $this->messenger()->addStatus($this->t('You have been logged out.'));

    return new RedirectResponse('/');
  }

  /**
   * Returns session data as JSON for frontend consumption.
   *
   * Route: /mitid/session/{session_id}.
   *
   * @param string $session_id
   *   The workflow/session ID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Session data or error.
   */
  public function getSession(string $session_id): JsonResponse {
    $sessionManager = \Drupal::service('aabenforms_mitid.session_manager');
    $session = $sessionManager->getSession($session_id);

    if (!$session) {
      return new JsonResponse(['error' => 'Session not found or expired'], 404);
    }

    $address = $sessionManager->getAddressFromSession($session_id);

    // Return session data in JSON:API-like format for frontend compatibility.
    // Additive shape: name/cpr/email/expiry have always been here; the demo
    // route consumes given_name/family_name/birthdate/address/assurance_level
    // when present. mitid_uuid/auth_time/issuer remain server-side only.
    return new JsonResponse([
      'data' => [
        'type' => 'mitid-session',
        'id' => $session_id,
        'attributes' => [
          'name' => $session['name'] ?? $session['given_name'] ?? '',
          'cpr' => $session['cpr'] ?? '',
          'email' => $session['email'] ?? '',
          'expiry' => $session['expires_at'] ?? '',
          'given_name' => $session['given_name'] ?? '',
          'family_name' => $session['family_name'] ?? '',
          'birthdate' => $session['birthdate'] ?? '',
          'address' => $address,
          'assurance_level' => $session['assurance_level'] ?? '',
        ],
      ],
    ]);
  }

}
