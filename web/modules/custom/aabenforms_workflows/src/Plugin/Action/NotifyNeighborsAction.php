<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\GisService;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Drupal\eca\Plugin\ECA\PluginFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Notifies neighbors within radius about construction.
 */
#[Action(
  id: 'aabenforms_notify_neighbors',
  label: new TranslatableMarkup('Notify Neighbors'),
  type: 'entity',
)]
#[EcaAction(
  description: new TranslatableMarkup('Notifies neighbors within radius about construction'),
  version_introduced: '2.0.0',
)]
class NotifyNeighborsAction extends AabenFormsActionBase {
  use PluginFormTrait;

  /**
   * The GIS service.
   *
   * @var \Drupal\aabenforms_workflows\Service\GisService
   */
  protected GisService $gisService;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->gisService = $container->get('aabenforms_workflows.gis_service');
    $instance->mailManager = $container->get('plugin.manager.mail');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'address_field' => 'property_address',
      'radius_meters' => '50',
      'notification_method' => 'email',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['address_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address Field'),
      '#default_value' => $this->configuration['address_field'],
      '#required' => TRUE,
    ];
    $form['radius_meters'] = [
      '#type' => 'number',
      '#title' => $this->t('Notification Radius (meters)'),
      '#default_value' => $this->configuration['radius_meters'],
      '#min' => 10,
      '#max' => 200,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL): void {
    $submission = $this->getSubmission($entity);
    if (!$submission) {
      return;
    }

    $data = $submission->getData();
    $address = $data[$this->configuration['address_field']] ?? NULL;
    if (!$address) {
      return;
    }

    $radius = (int) $this->configuration['radius_meters'];
    $result = $this->gisService->findNeighborsInRadius($address, $radius);

    if ($result['status'] === 'success') {
      $notified = [];
      foreach ($result['neighbors'] as $neighbor) {
        $notified[] = $neighbor['contact_email'];
      }

      $submission->setElementData('neighbors_notified_count', count($notified));
      $submission->setElementData('neighbors_notified_list', implode(', ', $notified));
      $submission->save();

      $this->logger->info('Notified @count neighbors within @radius m of @address', [
        '@count' => count($notified),
        '@radius' => $radius,
        '@address' => $address,
      ]);
    }
  }

}
