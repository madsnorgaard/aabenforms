<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_mitid\Service\MitIdSessionManager;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: Validate MitID session.
 */
#[Action(
  id: 'aabenforms_mitid_validate',
  label: new TranslatableMarkup('Validate MitID Session'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Validates that a MitID authentication session is active and valid.'),
  version_introduced: '2.0.0',
)]
class MitIdValidateAction extends AabenFormsActionBase {

  /**
   * The MitID session manager.
   *
   * @var \Drupal\aabenforms_mitid\Service\MitIdSessionManager
   */
  protected MitIdSessionManager $sessionManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sessionManager = $container->get('aabenforms_mitid.session_manager');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * Whether MitID demo mode (pass with no real session) is permitted.
   *
   * Default is FALSE: without a verified session the action fails CLOSED so
   * the audit trail never records an unverified identity as "verified".
   */
  protected function demoModeAllowed(): bool {
    return (bool) ($this->configFactory->get('aabenforms_workflows.settings')->get('allow_mitid_demo_mode') ?? FALSE);
  }

  /**
   * Records the "no valid MitID session" outcome, fail-closed by default.
   *
   * @param string $context
   *   Short reason for logging.
   */
  protected function recordNoSession(string $context): void {
    if ($this->demoModeAllowed()) {
      $this->log('MitID validation: ' . $context . ' - demo mode (allow_mitid_demo_mode on)', [], 'info');
      $this->setTokenValue($this->configuration['result_token'], TRUE);
      $this->setResultStatus('verified');
      $this->recordStep('MitID Identity Validation', 'Demo mode: identity was NOT verified (no MitID session)', 'completed');
      return;
    }
    $this->log('MitID validation failed: ' . $context . ' (demo mode off)', [], 'warning');
    $this->setTokenValue($this->configuration['result_token'], FALSE);
    $this->setResultStatus('failed');
    $this->recordStep('MitID Identity Validation', 'No valid MitID session - identity could not be verified', 'failed');
  }

  /**
   * Writes an explicit scalar status companion token for downstream gating.
   *
   * The boolean result_token is kept for backward compatibility, but ECA
   * conditions and the audit action read this `<result_token>_status` scalar
   * (`verified` or `failed`) because comparing a rendered boolean token is
   * unreliable.
   *
   * @param string $status
   *   Either 'verified' or 'failed'.
   */
  protected function setResultStatus(string $status): void {
    $resultToken = (string) ($this->configuration['result_token'] ?? '');
    if ($resultToken !== '') {
      $this->setTokenValue($resultToken . '_status', $status);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'workflow_id_token' => 'workflow_id',
      'result_token' => 'mitid_valid',
      'session_data_token' => 'mitid_session',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['workflow_id_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Workflow ID token'),
      '#description' => $this->t('Token containing the workflow instance ID.'),
      '#default_value' => $this->configuration['workflow_id_token'],
      '#required' => TRUE,
    ];

    $form['result_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result token name'),
      '#description' => $this->t('Token to store validation result (TRUE/FALSE).'),
      '#default_value' => $this->configuration['result_token'],
      '#required' => TRUE,
    ];

    $form['session_data_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Session data token name'),
      '#description' => $this->t('Token to store session data if valid.'),
      '#default_value' => $this->configuration['session_data_token'],
      '#required' => TRUE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['workflow_id_token'] = $form_state->getValue('workflow_id_token');
    $this->configuration['result_token'] = $form_state->getValue('result_token');
    $this->configuration['session_data_token'] = $form_state->getValue('session_data_token');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $workflowId = $this->getTokenValue($this->configuration['workflow_id_token'] ?? 'workflow_id', '');

    if (empty($workflowId)) {
      $this->recordNoSession('no workflow id in context');
      return;
    }

    try {
      // Get session data.
      $sessionData = $this->sessionManager->getSession($workflowId);

      if (empty($sessionData)) {
        $this->recordNoSession('no session for workflow ' . $workflowId);
        return;
      }

      // Check if session has expired.
      $expiresAt = $sessionData['expires_at'] ?? 0;
      if ($expiresAt < time()) {
        $this->log('MitID validation failed: Session expired for workflow {workflow_id}', [
          'workflow_id' => $workflowId,
        ], 'warning');
        $this->setTokenValue($this->configuration['result_token'], FALSE);
        $this->setResultStatus('failed');
        $this->recordStep('MitID Identity Validation', 'Session expired - re-authentication required', 'failed');
        return;
      }

      // Session is valid.
      $this->log('MitID session validated successfully for workflow {workflow_id}', [
        'workflow_id' => $workflowId,
      ]);

      $this->setTokenValue($this->configuration['result_token'], TRUE);
      $this->setResultStatus('verified');
      $this->setTokenValue($this->configuration['session_data_token'], $sessionData);
      $this->recordStep('MitID Identity Validation', 'Citizen identity verified via NemID/MitID national eID');

    }
    catch (\Exception $e) {
      $this->handleError($e, 'MitID Identity Validation');
      $this->setTokenValue($this->configuration['result_token'], FALSE);
      $this->setResultStatus('failed');
    }
  }

}
