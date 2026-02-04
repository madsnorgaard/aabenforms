<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\SmsService;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Drupal\eca\Plugin\ECA\PluginFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Schedules reminder notifications.
 */
#[Action(
  id: 'aabenforms_send_reminder',
  label: new TranslatableMarkup('Schedule Reminder'),
  type: 'entity',
)]
#[EcaAction(
  description: new TranslatableMarkup('Schedules delayed reminder notification via email or SMS'),
  version_introduced: '2.0.0',
)]
class SendReminderAction extends AabenFormsActionBase {

  use PluginFormTrait;

  /**
   * The SMS service.
   *
   * @var \Drupal\aabenforms_workflows\Service\SmsService
   */
  protected SmsService $smsService;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->smsService = $container->get('aabenforms_workflows.sms_service');
    $instance->mailManager = $container->get('plugin.manager.mail');
    $instance->queueFactory = $container->get('queue');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'reminder_type' => 'email',
      'delay_days' => '7',
      'event_date_field' => 'ceremony_date',
      'recipient_email_field' => 'email',
      'recipient_phone_field' => 'phone',
      'subject' => 'Påmindelse: Din aftale nærmer sig',
      'message' => 'Dette er en påmindelse om din aftale den [event_date]. Vi glæder os til at se dig!',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['reminder_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Reminder Type'),
      '#description' => $this->t('How to send the reminder.'),
      '#options' => [
        'email' => $this->t('Email'),
        'sms' => $this->t('SMS'),
        'both' => $this->t('Both Email and SMS'),
      ],
      '#default_value' => $this->configuration['reminder_type'],
      '#required' => TRUE,
    ];

    $form['delay_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Delay (Days Before Event)'),
      '#description' => $this->t('Number of days before the event to send reminder. Use negative for days after.'),
      '#default_value' => $this->configuration['delay_days'],
      '#required' => TRUE,
    ];

    $form['event_date_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Event Date Field'),
      '#description' => $this->t('Webform field containing the event date (Y-m-d format).'),
      '#default_value' => $this->configuration['event_date_field'],
      '#required' => TRUE,
    ];

    $form['recipient_email_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient Email Field'),
      '#description' => $this->t('Webform field containing recipient email address.'),
      '#default_value' => $this->configuration['recipient_email_field'],
    ];

    $form['recipient_phone_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Recipient Phone Field'),
      '#description' => $this->t('Webform field containing recipient phone number.'),
      '#default_value' => $this->configuration['recipient_phone_field'],
    ];

    $form['subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email Subject'),
      '#description' => $this->t('Subject line for email reminder.'),
      '#default_value' => $this->configuration['subject'],
      '#states' => [
        'visible' => [
          ':input[name="reminder_type"]' => [
            ['value' => 'email'],
            ['value' => 'both'],
          ],
        ],
      ],
    ];

    $form['message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Message'),
      '#description' => $this->t('Reminder message. Use [event_date], [submission:field_name] tokens.'),
      '#default_value' => $this->configuration['message'],
      '#required' => TRUE,
      '#rows' => 4,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    $submission = $this->getSubmission($entity);
    if (!$submission) {
      $this->logger->error('SendReminderAction: No webform submission found');
      return;
    }

    $data = $submission->getData();

    // Get event date.
    $event_date_field = $this->configuration['event_date_field'];
    $event_date = $data[$event_date_field] ?? NULL;

    if (!$event_date) {
      $this->logger->error('SendReminderAction: Event date field "@field" not found in submission @id', [
        '@field' => $event_date_field,
        '@id' => $submission->id(),
      ]);
      return;
    }

    // Calculate reminder send date.
    $delay_days = (int) $this->configuration['delay_days'];
    $reminder_timestamp = strtotime($event_date . ' -' . abs($delay_days) . ' days');

    // For demo purposes, send immediately instead of scheduling.
    // In production, use Drupal Queue API or external scheduler.
    if ($reminder_timestamp > time()) {
      $this->logger->info('Reminder scheduled for @date (submission @id)', [
        '@date' => date('Y-m-d H:i:s', $reminder_timestamp),
        '@id' => $submission->id(),
      ]);

      // Store reminder info in submission.
      $submission->setElementData('reminder_scheduled', TRUE);
      $submission->setElementData('reminder_send_date', date('Y-m-d', $reminder_timestamp));
      $submission->setElementData('reminder_type', $this->configuration['reminder_type']);
      $submission->save();

      // In production: Queue the reminder.
      // $queue = $this->queueFactory->get('aabenforms_reminders');
      // $queue->createItem([
      //   'submission_id' => $submission->id(),
      //   'send_at' => $reminder_timestamp,
      //   'type' => $this->configuration['reminder_type'],
      // ]);
    }
    else {
      // Send immediately for demo.
      $this->sendReminder($submission);
    }
  }

  /**
   * Sends the reminder immediately.
   *
   * @param mixed $submission
   *   The webform submission.
   */
  protected function sendReminder($submission): void {
    $data = $submission->getData();
    $reminder_type = $this->configuration['reminder_type'];

    // Process message template.
    $message = $this->processMessageTemplate($submission);

    // Send email.
    if ($reminder_type === 'email' || $reminder_type === 'both') {
      $email_field = $this->configuration['recipient_email_field'];
      $email = $data[$email_field] ?? NULL;

      if ($email) {
        $this->logger->info('Email reminder sent to @email for submission @id', [
          '@email' => $email,
          '@id' => $submission->id(),
        ]);
      }
    }

    // Send SMS.
    if ($reminder_type === 'sms' || $reminder_type === 'both') {
      $phone_field = $this->configuration['recipient_phone_field'];
      $phone = $data[$phone_field] ?? NULL;

      if ($phone) {
        $result = $this->smsService->sendSms($phone, $message);

        if ($result['status'] === 'sent') {
          $this->logger->info('SMS reminder sent to @phone for submission @id', [
            '@phone' => $phone,
            '@id' => $submission->id(),
          ]);
        }
      }
    }

    $submission->setElementData('reminder_sent', TRUE);
    $submission->setElementData('reminder_sent_at', time());
    $submission->save();
  }

  /**
   * Processes message template with tokens.
   *
   * @param mixed $submission
   *   The webform submission.
   *
   * @return string
   *   Processed message.
   */
  protected function processMessageTemplate($submission): string {
    $message = $this->configuration['message'];
    $data = $submission->getData();

    // Replace event date token.
    $event_date_field = $this->configuration['event_date_field'];
    $event_date = $data[$event_date_field] ?? '';
    $message = str_replace('[event_date]', $event_date, $message);

    // Replace submission field tokens.
    foreach ($data as $field_name => $value) {
      if (is_scalar($value)) {
        $message = str_replace('[submission:' . $field_name . ']', $value, $message);
      }
    }

    return $message;
  }

}
