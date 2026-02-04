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
 * Fetches available time slots from calendar service.
 */
#[Action(
  id: 'aabenforms_fetch_available_slots',
  label: new TranslatableMarkup('Fetch Available Time Slots'),
  type: 'entity',
)]
#[EcaAction(
  description: new TranslatableMarkup('Retrieves available booking slots from calendar service'),
  version_introduced: '2.0.0',
)]
class FetchAvailableSlotsAction extends AabenFormsActionBase {

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
      'start_date_field' => 'preferred_date',
      'date_range_days' => '30',
      'slot_duration' => '60',
      'location' => 'Borgerservice',
      'store_slots_in' => 'available_slots',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['start_date_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Start Date Field'),
      '#description' => $this->t('Webform field containing preferred start date (Y-m-d format).'),
      '#default_value' => $this->configuration['start_date_field'],
    ];

    $form['date_range_days'] = [
      '#type' => 'number',
      '#title' => $this->t('Date Range (Days)'),
      '#description' => $this->t('Number of days to search for available slots.'),
      '#default_value' => $this->configuration['date_range_days'],
      '#min' => 1,
      '#max' => 365,
      '#required' => TRUE,
    ];

    $form['slot_duration'] = [
      '#type' => 'select',
      '#title' => $this->t('Slot Duration'),
      '#description' => $this->t('Duration of each time slot in minutes.'),
      '#options' => [
        '30' => $this->t('30 minutes'),
        '45' => $this->t('45 minutes'),
        '60' => $this->t('1 hour'),
        '90' => $this->t('1.5 hours'),
        '120' => $this->t('2 hours'),
      ],
      '#default_value' => $this->configuration['slot_duration'],
      '#required' => TRUE,
    ];

    $form['location'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Location/Venue'),
      '#description' => $this->t('Location or venue name for the booking.'),
      '#default_value' => $this->configuration['location'],
    ];

    $form['store_slots_in'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store Slots In'),
      '#description' => $this->t('Field name to store the available slots array.'),
      '#default_value' => $this->configuration['store_slots_in'],
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
      $this->logger->error('FetchAvailableSlotsAction: No webform submission found');
      return;
    }

    $data = $submission->getData();

    // Determine start date.
    $start_date_field = $this->configuration['start_date_field'];
    $start_date = $data[$start_date_field] ?? date('Y-m-d');

    // Calculate end date.
    $date_range_days = (int) $this->configuration['date_range_days'];
    $end_date = date('Y-m-d', strtotime($start_date . ' +' . $date_range_days . ' days'));

    // Fetch available slots.
    $result = $this->calendarService->getAvailableSlots(
      $start_date,
      $end_date,
      (int) $this->configuration['slot_duration'],
      [
        'location' => $this->configuration['location'],
      ]
    );

    // Store slots in submission.
    $slots_field = $this->configuration['store_slots_in'];

    if ($result['status'] === 'success') {
      $submission->setElementData($slots_field, json_encode($result['slots']));
      $submission->setElementData('slots_count', $result['total_slots']);
      $submission->setElementData('slots_start_date', $start_date);
      $submission->setElementData('slots_end_date', $end_date);

      $this->logger->info('Fetched @count available slots for submission @id (date range: @start to @end)', [
        '@count' => $result['total_slots'],
        '@id' => $submission->id(),
        '@start' => $start_date,
        '@end' => $end_date,
      ]);
    }
    else {
      $submission->setElementData('slots_status', 'error');
      $submission->setElementData('slots_error', $result['error']);

      $this->logger->error('Failed to fetch slots for submission @id: @error', [
        '@id' => $submission->id(),
        '@error' => $result['error'],
      ]);
    }

    $submission->save();
  }

}
