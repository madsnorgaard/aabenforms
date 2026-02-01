<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: Audit Log Entry.
 *
 * @Action(
 *   id = "aabenforms_audit_log",
 *   label = @Translation("Audit Log"),
 *   description = @Translation("Creates an audit log entry for GDPR compliance."),
 *   type = "aabenforms"
 * )
 */
class AuditLogAction extends AabenFormsActionBase {

  /**
   * The audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger
   */
  protected AuditLogger $auditLogger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->auditLogger = $container->get('aabenforms_core.audit_logger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'event_type' => 'workflow_action',
      'cpr_token' => 'cpr',
      'message_template' => 'Workflow action executed',
      'additional_data_token' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['event_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Event type'),
      '#description' => $this->t('Type of audit event.'),
      '#options' => [
        'workflow_action' => $this->t('Workflow Action'),
        'cpr_access' => $this->t('CPR Access'),
        'cvr_access' => $this->t('CVR Access'),
        'data_export' => $this->t('Data Export'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $this->configuration['event_type'],
      '#required' => TRUE,
    ];

    $form['cpr_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPR token name'),
      '#description' => $this->t('Token containing CPR number (for GDPR compliance). Leave empty if not applicable.'),
      '#default_value' => $this->configuration['cpr_token'],
    ];

    $form['message_template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message template'),
      '#description' => $this->t('Log message template. Use [token_name] for token replacement.'),
      '#default_value' => $this->configuration['message_template'],
      '#required' => TRUE,
      '#rows' => 3,
    ];

    $form['additional_data_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Additional data token'),
      '#description' => $this->t('Optional token containing additional structured data to log.'),
      '#default_value' => $this->configuration['additional_data_token'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['event_type'] = $form_state->getValue('event_type');
    $this->configuration['cpr_token'] = $form_state->getValue('cpr_token');
    $this->configuration['message_template'] = $form_state->getValue('message_template');
    $this->configuration['additional_data_token'] = $form_state->getValue('additional_data_token');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    try {
      // Get CPR if specified.
      $cpr = NULL;
      if (!empty($this->configuration['cpr_token'])) {
        $cpr = $this->getTokenValue($this->configuration['cpr_token']);
        if ($cpr) {
          // Clean CPR.
          $cpr = preg_replace('/[^0-9]/', '', $cpr);
        }
      }

      // Process message template with token replacement.
      $message = $this->configuration['message_template'];
      $message = $this->replaceTokensInString($message);

      // Get additional data.
      $additionalData = [];
      if (!empty($this->configuration['additional_data_token'])) {
        $data = $this->getTokenValue($this->configuration['additional_data_token']);
        if (is_array($data)) {
          $additionalData = $data;
        }
      }

      // Create audit log entry.
      if ($cpr && $this->configuration['event_type'] === 'cpr_access') {
        // Special handling for CPR access logging.
        $this->auditLogger->logCprLookup(
          $cpr,
          'workflow_action',
          'success',
          array_merge(['message' => $message], $additionalData)
        );
      }
      else {
        // Generic audit log.
        $this->auditLogger->log(
          $this->configuration['event_type'],
          $message,
          $cpr,
          array_merge([
            'action_id' => $this->getPluginId(),
          ], $additionalData)
        );
      }

      $this->log('Audit log entry created: {type}', [
        'type' => $this->configuration['event_type'],
      ]);

    }
    catch (\Exception $e) {
      $this->handleError($e, 'Creating audit log entry');
    }
  }

  /**
   * Replaces tokens in a string.
   *
   * @param string $string
   *   The string with tokens.
   *
   * @return string
   *   String with tokens replaced.
   */
  protected function replaceTokensInString(string $string): string {
    // Simple token replacement: [token_name] -> value.
    preg_match_all('/\[([^\]]+)\]/', $string, $matches);

    foreach ($matches[1] as $tokenName) {
      $value = $this->getTokenValue($tokenName, '[' . $tokenName . ']');
      if (!is_string($value) && !is_numeric($value)) {
        $value = is_array($value) ? json_encode($value) : (string) $value;
      }
      $string = str_replace('[' . $tokenName . ']', $value, $string);
    }

    return $string;
  }

}
