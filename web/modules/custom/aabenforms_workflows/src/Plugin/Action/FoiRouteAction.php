<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_core\Service\AuditLogger;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: Route a freedom-of-information request.
 *
 * Replaces the previous log-and-forget FOI flow. It assigns a case
 * reference, computes the statutory response due date (7 working days),
 * emails an acknowledgement to the requester and a routing notification
 * to the case worker inbox, and records an audit entry. This is what
 * backs the confirmation message's promise of a response within 7
 * working days.
 */
#[Action(
  id: 'aabenforms_foi_route',
  label: new TranslatableMarkup('Route FOI request'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Assigns a reference and due date, acknowledges the requester, and routes the FOI request to a case worker.'),
  version_introduced: '2.1.0',
)]
class FoiRouteAction extends AabenFormsActionBase {

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The audit logger.
   *
   * @var \Drupal\aabenforms_core\Service\AuditLogger
   */
  protected AuditLogger $auditLogger;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $timeService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->auditLogger = $container->get('aabenforms_core.audit_logger');
    $instance->timeService = $container->get('datetime.time');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'caseworker_email' => 'aktindsigt@example.dk',
      'response_working_days' => 7,
      'requester_name_token' => '[webform_submission:values:requester_name:raw]',
      'requester_email_token' => '[webform_submission:values:requester_email:raw]',
      'request_text_token' => '[webform_submission:values:request_text:raw]',
      'reference_token' => '[webform_submission:sid]',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['caseworker_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Case worker inbox'),
      '#description' => $this->t('Email address the FOI request is routed to.'),
      '#default_value' => $this->configuration['caseworker_email'],
      '#required' => TRUE,
    ];
    $form['response_working_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Response deadline (working days)'),
      '#default_value' => $this->configuration['response_working_days'],
      '#min' => 1,
      '#required' => TRUE,
    ];
    $form['requester_name_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Requester name token'),
      '#default_value' => $this->configuration['requester_name_token'],
    ];
    $form['requester_email_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Requester email token'),
      '#default_value' => $this->configuration['requester_email_token'],
      '#required' => TRUE,
    ];
    $form['request_text_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Request text token'),
      '#default_value' => $this->configuration['request_text_token'],
    ];
    $form['reference_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reference token'),
      '#description' => $this->t('Token used to build the case reference, for example the submission id.'),
      '#default_value' => $this->configuration['reference_token'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $keys = [
      'caseworker_email',
      'response_working_days',
      'requester_name_token',
      'requester_email_token',
      'request_text_token',
      'reference_token',
    ];
    foreach ($keys as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $requesterEmail = $this->getTokenValue((string) $this->configuration['requester_email_token'], '');
    $requesterName = $this->getTokenValue((string) $this->configuration['requester_name_token'], '');
    $requestText = $this->getTokenValue((string) $this->configuration['request_text_token'], '');
    $sid = $this->getTokenValue((string) $this->configuration['reference_token'], '');
    $reference = 'FOI-' . ($sid !== '' ? $sid : 'NEW');
    $caseworkerEmail = (string) $this->configuration['caseworker_email'];
    $days = (int) ($this->configuration['response_working_days'] ?? 7);

    $dueTimestamp = $this->addWorkingDays($this->timeService->getRequestTime(), $days);
    $dueDate = date('Y-m-d', $dueTimestamp);

    $params = [
      'reference' => $reference,
      'requester_name' => $requesterName !== '' ? $requesterName : $this->t('anmoder'),
      'requester_email' => $requesterEmail,
      'request_text' => $requestText,
      'due_date' => $dueDate,
    ];
    $langcode = LanguageInterface::LANGCODE_DEFAULT;

    // Route to the case worker inbox.
    $this->mailManager->mail('aabenforms_workflows', 'foi_caseworker_notification', $caseworkerEmail, $langcode, $params);
    $this->recordStep('FOI routed to case worker', sprintf('Sag %s sendt til sagsbehandling. Frist: %s.', $reference, $dueDate), 'completed');

    // Acknowledge the requester, if an address was given.
    if ($requesterEmail !== '') {
      $this->mailManager->mail('aabenforms_workflows', 'foi_acknowledgement', $requesterEmail, $langcode, $params);
      $this->recordStep('FOI acknowledgement sent', sprintf('Kvittering sendt til anmoder med referencenummer %s.', $reference), 'completed');
    }
    else {
      $this->recordStep('FOI acknowledgement skipped', 'Ingen e-mailadresse angivet til kvittering.', 'skipped');
    }

    try {
      $this->auditLogger->log(
        'foi_request_routed',
        'system',
        sprintf('FOI %s routed; due %s', $reference, $dueDate),
        'success',
        ['reference' => $reference, 'due_date' => $dueDate]
      );
    }
    catch (\Exception $e) {
      $this->handleError($e, 'Recording FOI routing audit entry');
    }
  }

  /**
   * Adds a number of working days to a timestamp, skipping weekends.
   *
   * @param int $timestamp
   *   The starting Unix timestamp.
   * @param int $days
   *   The number of working days to add.
   *
   * @return int
   *   The resulting Unix timestamp.
   */
  protected function addWorkingDays(int $timestamp, int $days): int {
    $result = $timestamp;
    $added = 0;
    while ($added < $days) {
      $result += 86400;
      $dayOfWeek = (int) date('N', $result);
      if ($dayOfWeek < 6) {
        $added++;
      }
    }
    return $result;
  }

}
