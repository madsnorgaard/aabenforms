<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\SmsService;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Drupal\eca\Plugin\ECA\PluginFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sends SMS notification via Danish SMS gateway.
 */
#[Action(
  id: 'aabenforms_send_sms',
  label: new TranslatableMarkup('Send SMS Notification'),
  type: 'entity',
)]
#[EcaAction(
  description: new TranslatableMarkup('Sends SMS message via Danish SMS gateway'),
  version_introduced: '2.0.0',
)]
class SendSmsAction extends AabenFormsActionBase {

  use PluginFormTrait;

  /**
   * The SMS service.
   *
   * @var \Drupal\aabenforms_workflows\Service\SmsService
   */
  protected SmsService $smsService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->smsService = $container->get('aabenforms_workflows.sms_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'phone_field' => 'phone',
      'message_template' => 'Din ansøgning er modtaget. Sagsnummer: [submission:id]',
      'sender_name' => 'ÅbenForms',
      'store_message_id_in' => 'sms_message_id',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['phone_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone Number Field Name'),
      '#description' => $this->t('The webform field containing recipient phone number (format: +4512345678).'),
      '#default_value' => $this->configuration['phone_field'],
      '#required' => TRUE,
    ];

    $form['message_template'] = [
      '#type' => 'textarea',
      '#title' => $this->t('SMS Message Template'),
      '#description' => $this->t('SMS message content. Max 160 characters for single SMS. Use [submission:field_name] for dynamic values.'),
      '#default_value' => $this->configuration['message_template'],
      '#required' => TRUE,
      '#rows' => 3,
      '#maxlength' => 1600,
    ];

    $form['sender_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sender Name'),
      '#description' => $this->t('Sender name shown to recipient (max 11 characters).'),
      '#default_value' => $this->configuration['sender_name'],
      '#maxlength' => 11,
    ];

    $form['store_message_id_in'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store Message ID In'),
      '#description' => $this->t('Field name to store the SMS message ID.'),
      '#default_value' => $this->configuration['store_message_id_in'],
    ];

    $form['token_help'] = [
      '#type' => 'details',
      '#title' => $this->t('Available Tokens'),
      '#open' => FALSE,
    ];

    $form['token_help']['list'] = [
      '#markup' => $this->t('<ul>
        <li>[submission:id] - Submission ID</li>
        <li>[submission:created] - Submission date</li>
        <li>[submission:field_name] - Any field value</li>
        <li>[webform:title] - Form title</li>
      </ul>'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    $submission = $this->getSubmission($entity);
    if (!$submission) {
      $this->logger->error('SendSmsAction: No webform submission found');
      return;
    }

    $data = $submission->getData();

    // Extract phone number.
    $phone_field = $this->configuration['phone_field'];
    $phone = $data[$phone_field] ?? NULL;

    if (!$phone) {
      $this->logger->error('SendSmsAction: Phone field "@field" not found in submission @id', [
        '@field' => $phone_field,
        '@id' => $submission->id(),
      ]);
      return;
    }

    // Normalize phone number to +45 format.
    $phone = $this->normalizePhoneNumber($phone);

    // Process message template with token replacement.
    $message = $this->processMessageTemplate($this->configuration['message_template'], $submission);

    // Prepare SMS options.
    $options = [
      'sender' => $this->configuration['sender_name'],
    ];

    // Send SMS.
    $result = $this->smsService->sendSms($phone, $message, $options);

    // Store result in submission.
    if ($this->configuration['store_message_id_in']) {
      $message_id_field = $this->configuration['store_message_id_in'];

      if ($result['status'] === 'sent') {
        $submission->setElementData($message_id_field, $result['message_id']);
        $submission->setElementData('sms_status', 'sent');
        $submission->setElementData('sms_segments', $result['segments']);

        $this->logger->info('SMS sent successfully to @phone for submission @id: @message_id', [
          '@phone' => $phone,
          '@id' => $submission->id(),
          '@message_id' => $result['message_id'],
        ]);
      }
      else {
        $submission->setElementData('sms_status', 'failed');
        $submission->setElementData('sms_error', $result['error']);

        $this->logger->warning('SMS failed for submission @id: @error', [
          '@id' => $submission->id(),
          '@error' => $result['error'],
        ]);
      }

      $submission->save();
    }
  }

  /**
   * Normalizes phone number to +45 format.
   *
   * @param string $phone
   *   Raw phone number.
   *
   * @return string
   *   Normalized phone number.
   */
  protected function normalizePhoneNumber(string $phone): string {
    // Remove all non-digit characters.
    $phone = preg_replace('/[^0-9+]/', '', $phone);

    // Add +45 prefix if missing.
    if (!str_starts_with($phone, '+45')) {
      // Remove leading zeros.
      $phone = ltrim($phone, '0');

      // Add country code.
      if (strlen($phone) === 8) {
        $phone = '+45' . $phone;
      }
    }

    return $phone;
  }

  /**
   * Processes message template with token replacement.
   *
   * @param string $template
   *   Message template.
   * @param mixed $submission
   *   Webform submission.
   *
   * @return string
   *   Processed message.
   */
  protected function processMessageTemplate(string $template, $submission): string {
    // Simple token replacement.
    $message = $template;

    // Replace [submission:id].
    $message = str_replace('[submission:id]', $submission->id(), $message);

    // Replace [submission:created].
    $message = str_replace('[submission:created]', date('Y-m-d H:i', $submission->getCreatedTime()), $message);

    // Replace field tokens [submission:field_name].
    $data = $submission->getData();
    foreach ($data as $field_name => $field_value) {
      if (is_scalar($field_value)) {
        $message = str_replace('[submission:' . $field_name . ']', $field_value, $message);
      }
    }

    // Replace [webform:title].
    $message = str_replace('[webform:title]', $submission->getWebform()->label(), $message);

    return $message;
  }

}
