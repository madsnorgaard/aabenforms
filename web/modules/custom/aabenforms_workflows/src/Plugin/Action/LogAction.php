<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;

/**
 * ECA Action: write a message to Drupal's logger.
 *
 * Replaces the upstream ECA 'eca_base_log' plugin, which was removed from
 * newer ECA releases. Templates shipped with this module and the
 * WorkflowTemplateInstantiator used 'eca_base_log' as a lightweight
 * breadcrumb action; this shim keeps that pattern working under our own
 * plugin namespace so we don't depend on a plugin ID that upstream can
 * reintroduce or change.
 */
#[Action(
  id: 'aabenforms_log',
  label: new TranslatableMarkup('Log a message'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Writes a message to the aabenforms_workflows logger at the configured severity.'),
  version_introduced: '2.0.0',
)]
class LogAction extends AabenFormsActionBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'level' => 'info',
      'message' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['level'] = [
      '#type' => 'select',
      '#title' => $this->t('Severity'),
      '#options' => [
        'debug' => $this->t('Debug'),
        'info' => $this->t('Info'),
        'notice' => $this->t('Notice'),
        'warning' => $this->t('Warning'),
        'error' => $this->t('Error'),
        'critical' => $this->t('Critical'),
      ],
      '#default_value' => $this->configuration['level'] ?? 'info',
      '#required' => TRUE,
    ];
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Tokens are supported.'),
      '#default_value' => $this->configuration['message'] ?? '',
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['level'] = $form_state->getValue('level');
    $this->configuration['message'] = $form_state->getValue('message');
  }

  /**
   * {@inheritdoc}
   */
  public function execute(mixed $entity = NULL): void {
    $level = $this->configuration['level'] ?? 'info';
    $message = $this->tokenService->replace($this->configuration['message'] ?? '');
    $valid = ['debug', 'info', 'notice', 'warning', 'error', 'critical'];
    if (!in_array($level, $valid, TRUE)) {
      $level = 'info';
    }
    $this->logger->log($level, $message);
    $this->recordStep($level, $message);
  }

}
