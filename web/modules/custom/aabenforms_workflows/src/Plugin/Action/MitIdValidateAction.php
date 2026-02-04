<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_mitid\Service\MitIdSessionManager;
use Drupal\Core\Action\Attribute\Action;
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->sessionManager = $container->get('aabenforms_mitid.session_manager');
    return $instance;
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
    $workflowId = $this->getTokenValue($this->configuration['workflow_id_token']);

    if (empty($workflowId)) {
      $this->log('MitID validation failed: No workflow ID provided', [], 'warning');
      $this->setTokenValue($this->configuration['result_token'], FALSE);
      return;
    }

    try {
      // Get session data.
      $sessionData = $this->sessionManager->getSession($workflowId);

      if (empty($sessionData)) {
        $this->log('MitID validation failed: No session found for workflow {workflow_id}', [
          'workflow_id' => $workflowId,
        ], 'warning');
        $this->setTokenValue($this->configuration['result_token'], FALSE);
        return;
      }

      // Check if session has expired.
      $expiresAt = $sessionData['expires_at'] ?? 0;
      if ($expiresAt < time()) {
        $this->log('MitID validation failed: Session expired for workflow {workflow_id}', [
          'workflow_id' => $workflowId,
        ], 'warning');
        $this->setTokenValue($this->configuration['result_token'], FALSE);
        return;
      }

      // Session is valid.
      $this->log('MitID session validated successfully for workflow {workflow_id}', [
        'workflow_id' => $workflowId,
      ]);

      $this->setTokenValue($this->configuration['result_token'], TRUE);
      $this->setTokenValue($this->configuration['session_data_token'], $sessionData);

    }
    catch (\Exception $e) {
      $this->handleError($e, 'Validating MitID session');
      $this->setTokenValue($this->configuration['result_token'], FALSE);
    }
  }

}
