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
    // Get workflow ID from query params or generate.
    $workflowId = $request->query->get('workflow_id') ?? 'default_' . uniqid();

    // Get redirect URL (where to return after auth).
    $returnUrl = $request->query->get('return_url') ?? '/';

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
      // Complete OIDC flow.
      $sessionData = $this->oidcClient->completeFlow($code, $workflowId, [
        'redirect_uri' => Url::fromRoute('aabenforms_mitid.callback', [], ['absolute' => TRUE])->toString(),
      ]);

      // Store session ID in user's session for frontend access.
      $request->getSession()->set('mitid_workflow_id', $workflowId);
      $request->getSession()->set('mitid_authenticated', TRUE);
      $request->getSession()->set('mitid_cpr', $sessionData['person']['cpr'] ?? NULL);

      $this->messenger()->addStatus($this->t('Successfully authenticated with MitID'));

      // Redirect back to the return URL.
      return new RedirectResponse($returnUrl);

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

}
