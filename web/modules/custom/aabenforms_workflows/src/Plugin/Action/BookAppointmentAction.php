<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\CalendarService;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Drupal\eca\Plugin\ECA\PluginFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Books an appointment time slot.
 */
#[Action(
  id: 'aabenforms_book_appointment',
  label: new TranslatableMarkup('Book Appointment'),
  type: 'entity',
)]
#[EcaAction(
  description: new TranslatableMarkup('Books selected time slot with double-booking prevention'),
  version_introduced: '2.0.0',
)]
class BookAppointmentAction extends AabenFormsActionBase {

  use PluginFormTrait;

  /**
   * The calendar service.
   *
   * @var \Drupal\aabenforms_workflows\Service\CalendarService
   */
  protected CalendarService $calendarService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->calendarService = $container->get('aabenforms_workflows.calendar_service');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'slot_id_field' => 'selected_slot_id',
      'attendee_name_field' => 'name',
      'attendee_email_field' => 'email',
      'attendee_phone_field' => 'phone',
      'attendee_cpr_field' => 'cpr',
      'store_booking_id_in' => 'booking_id',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['slot_id_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slot ID Field'),
      '#description' => $this->t('Webform field containing the selected slot ID.'),
      '#default_value' => $this->configuration['slot_id_field'],
      '#required' => TRUE,
    ];

    $form['attendee_name_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attendee Name Field'),
      '#description' => $this->t('Webform field containing attendee name.'),
      '#default_value' => $this->configuration['attendee_name_field'],
      '#required' => TRUE,
    ];

    $form['attendee_email_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attendee Email Field'),
      '#description' => $this->t('Webform field containing attendee email.'),
      '#default_value' => $this->configuration['attendee_email_field'],
      '#required' => TRUE,
    ];

    $form['attendee_phone_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attendee Phone Field'),
      '#description' => $this->t('Webform field containing attendee phone number.'),
      '#default_value' => $this->configuration['attendee_phone_field'],
    ];

    $form['attendee_cpr_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Attendee CPR Field'),
      '#description' => $this->t('Optional field containing CPR number (encrypted).'),
      '#default_value' => $this->configuration['attendee_cpr_field'],
    ];

    $form['store_booking_id_in'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store Booking ID In'),
      '#description' => $this->t('Field name to store the booking ID.'),
      '#default_value' => $this->configuration['store_booking_id_in'],
      '#required' => TRUE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    $submission = $this->getSubmission($entity);
    if (!$submission) {
      $this->logger->error('BookAppointmentAction: No webform submission found');
      return;
    }

    $data = $submission->getData();

    // Extract slot ID.
    $slot_id_field = $this->configuration['slot_id_field'];
    $slot_id = $data[$slot_id_field] ?? NULL;

    if (!$slot_id) {
      $this->logger->error('BookAppointmentAction: Slot ID field "@field" not found in submission @id', [
        '@field' => $slot_id_field,
        '@id' => $submission->id(),
      ]);
      return;
    }

    // Build attendee data.
    $attendees = [];

    // Primary attendee.
    $name_field = $this->configuration['attendee_name_field'];
    $email_field = $this->configuration['attendee_email_field'];
    $phone_field = $this->configuration['attendee_phone_field'];
    $cpr_field = $this->configuration['attendee_cpr_field'];

    if (isset($data[$name_field]) && isset($data[$email_field])) {
      $attendees[] = [
        'name' => $data[$name_field],
        'email' => $data[$email_field],
        'phone' => $data[$phone_field] ?? '',
        'cpr' => $data[$cpr_field] ?? '',
      ];
    }

    // Check for second attendee (for marriages).
    if (isset($data['partner2_name']) && isset($data['partner2_email'])) {
      $attendees[] = [
        'name' => $data['partner2_name'],
        'email' => $data['partner2_email'],
        'phone' => $data['partner2_phone'] ?? '',
        'cpr' => $data['partner2_cpr'] ?? '',
      ];
    }

    if (empty($attendees)) {
      $this->logger->error('BookAppointmentAction: No valid attendees found in submission @id', [
        '@id' => $submission->id(),
      ]);
      return;
    }

    // Additional booking details.
    $booking_details = [
      'submission_id' => $submission->id(),
      'webform_id' => $submission->getWebform()->id(),
      'booking_type' => $data['booking_type'] ?? 'appointment',
    ];

    // Book the slot.
    $result = $this->calendarService->bookSlot($slot_id, $attendees, $booking_details);

    // Store result in submission.
    $booking_id_field = $this->configuration['store_booking_id_in'];

    if ($result['status'] === 'success') {
      $submission->setElementData($booking_id_field, $result['booking_id']);
      $submission->setElementData('booking_slot_id', $result['slot_id']);
      $submission->setElementData('booking_status', 'confirmed');
      $submission->setElementData('booked_at', $result['booked_at']);

      $this->logger->info('Appointment booked successfully for submission @id: @booking_id (slot: @slot_id, attendees: @count)', [
        '@id' => $submission->id(),
        '@booking_id' => $result['booking_id'],
        '@slot_id' => $slot_id,
        '@count' => count($attendees),
      ]);
    }
    else {
      $submission->setElementData('booking_status', 'failed');
      $submission->setElementData('booking_error', $result['error']);

      $this->logger->warning('Appointment booking failed for submission @id: @error', [
        '@id' => $submission->id(),
        '@error' => $result['error'],
      ]);
    }

    $submission->save();
  }

}
