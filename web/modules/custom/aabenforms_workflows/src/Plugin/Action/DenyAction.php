<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: Deny and stop the flow.
 *
 * A terminal node placed on the failure branch of an identity or consent
 * gate. ECA has no flow-abort primitive, so a denied flow is modelled by
 * routing to this action and giving it no successors: nothing downstream
 * (CPR lookup, Digital Post, payment, approval) runs. It records a
 * citizen-facing failed step so the synchronous response reads as failed,
 * and writes a 'denied' audit row.
 */
#[Action(
  id: 'aabenforms_workflow_deny',
  label: new TranslatableMarkup('Deny and stop flow'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Stops the flow on a failed identity or consent gate and records a denied outcome.'),
  version_introduced: '2.1.0',
)]
class DenyAction extends AabenFormsActionBase {

  /**
   * The audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger
   */
  protected AuditLogger $auditLogger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->auditLogger = $container->get('aabenforms_core.audit_logger');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'event_type' => 'workflow_denied',
      'step_label' => 'Adgang afvist',
      'message' => 'Sagen blev ikke behandlet, fordi identiteten ikke kunne bekraeftes.',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['event_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Audit event type'),
      '#description' => $this->t('Audit log action recorded for the denial, for example citizen_service_denied.'),
      '#default_value' => $this->configuration['event_type'],
      '#required' => TRUE,
    ];

    $form['step_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Step label'),
      '#description' => $this->t('Short label shown to the citizen in the execution steps.'),
      '#default_value' => $this->configuration['step_label'],
      '#required' => TRUE,
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Citizen message'),
      '#description' => $this->t('Citizen-facing reason for the denial. Keep it free of technical detail.'),
      '#default_value' => $this->configuration['message'],
      '#required' => TRUE,
      '#rows' => 2,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['event_type'] = $form_state->getValue('event_type');
    $this->configuration['step_label'] = $form_state->getValue('step_label');
    $this->configuration['message'] = $form_state->getValue('message');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $eventType = (string) ($this->configuration['event_type'] ?? 'workflow_denied');
    $label = (string) ($this->configuration['step_label'] ?? 'Adgang afvist');
    $message = (string) ($this->configuration['message'] ?? 'Sagen blev ikke behandlet.');

    $this->log('Flow denied at gate: {type}', ['type' => $eventType], 'warning');
    $this->recordStep($label, $message, 'failed');

    try {
      $this->auditLogger->log(
        $eventType,
        'system',
        $message,
        'denied',
        ['action_id' => $this->getPluginId()]
      );
    }
    catch (\Exception $e) {
      $this->handleError($e, 'Recording denial audit entry');
    }
  }

}
