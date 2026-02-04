<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_workflows\Service\GisService;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Drupal\eca\Plugin\ECA\PluginFormTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Validates construction type against zoning rules.
 */
#[Action(
  id: 'aabenforms_validate_zoning',
  label: new TranslatableMarkup('Validate Zoning'),
  type: 'entity',
)]
#[EcaAction(
  description: new TranslatableMarkup('Validates construction type against zoning rules'),
  version_introduced: '2.0.0',
)]
class ValidateZoningAction extends AabenFormsActionBase {
  use PluginFormTrait;
  protected GisService $gisService;

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->gisService = $container->get('aabenforms_workflows.gis_service');
    return $instance;
  }

  public function defaultConfiguration(): array {
    return [
      'address_field' => 'property_address',
      'construction_type_field' => 'construction_type',
      'store_result_in' => 'zoning_validation',
    ] + parent::defaultConfiguration();
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['address_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address Field'),
      '#default_value' => $this->configuration['address_field'],
      '#required' => TRUE,
    ];
    $form['construction_type_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Construction Type Field'),
      '#default_value' => $this->configuration['construction_type_field'],
      '#required' => TRUE,
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  public function execute($entity = NULL): void {
    $submission = $this->getSubmission($entity);
    if (!$submission) return;

    $data = $submission->getData();
    $address = $data[$this->configuration['address_field']] ?? NULL;
    $construction_type = $data[$this->configuration['construction_type_field']] ?? NULL;

    if (!$address || !$construction_type) {
      $this->logger->error('Missing address or construction type');
      return;
    }

    $result = $this->gisService->validateConstructionType($address, $construction_type);
    
    $submission->setElementData('zoning_allowed', $result['allowed']);
    $submission->setElementData('zoning_zone_type', $result['zone_type']);
    $submission->setElementData('zoning_reason', $result['reason']);
    $submission->save();

    $this->logger->info('Zoning validation: @type at @address = @result', [
      '@type' => $construction_type,
      '@address' => $address,
      '@result' => $result['allowed'] ? 'ALLOWED' : 'NOT ALLOWED',
    ]);
  }
}
