<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_core\Service\ServiceplatformenClient;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: CPR Person Lookup via Serviceplatformen SF1520.
 *
 * @Action(
 *   id = "aabenforms_cpr_lookup",
 *   label = @Translation("CPR Person Lookup"),
 *   description = @Translation("Looks up person data from Serviceplatformen SF1520 using CPR number."),
 *   type = "aabenforms"
 * )
 */
class CprLookupAction extends AabenFormsActionBase {

  /**
   * The Serviceplatformen client.
   *
   * @var \Drupal\aabenforms_core\Service\ServiceplatformenClient
   */
  protected ServiceplatformenClient $serviceplatformenClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->serviceplatformenClient = $container->get('aabenforms_core.serviceplatformen_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'cpr_token' => 'cpr',
      'result_token' => 'person_data',
      'use_cache' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['cpr_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CPR token name'),
      '#description' => $this->t('Token containing the CPR number to look up.'),
      '#default_value' => $this->configuration['cpr_token'],
      '#required' => TRUE,
    ];

    $form['result_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result token name'),
      '#description' => $this->t('Token to store person data result.'),
      '#default_value' => $this->configuration['result_token'],
      '#required' => TRUE,
    ];

    $form['use_cache'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use cache'),
      '#description' => $this->t('Cache lookup results for 15 minutes.'),
      '#default_value' => $this->configuration['use_cache'],
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $this->configuration['cpr_token'] = $form_state->getValue('cpr_token');
    $this->configuration['result_token'] = $form_state->getValue('result_token');
    $this->configuration['use_cache'] = $form_state->getValue('use_cache');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $cpr = $this->getTokenValue($this->configuration['cpr_token']);

    if (empty($cpr)) {
      $this->log('CPR lookup failed: No CPR number provided', [], 'warning');
      $this->setTokenValue($this->configuration['result_token'], NULL);
      return;
    }

    // Clean CPR (remove hyphens/spaces).
    $cpr = preg_replace('/[^0-9]/', '', $cpr);

    try {
      $this->log('Performing CPR lookup via SF1520 for: {cpr}', [
        'cpr' => substr($cpr, 0, 6) . 'XXXX',
      ]);

      // Call SF1520 via ServiceplatformenClient.
      $options = [
        'no_cache' => !$this->configuration['use_cache'],
      ];

      $result = $this->serviceplatformenClient->request(
        'SF1520',
        'PersonLookup',
        ['cpr' => $cpr],
        $options
      );

      // Extract person data.
      $personData = $result['person'] ?? NULL;

      if (empty($personData)) {
        $this->log('CPR lookup returned no data for: {cpr}', [
          'cpr' => substr($cpr, 0, 6) . 'XXXX',
        ], 'warning');
        $this->setTokenValue($this->configuration['result_token'], NULL);
        return;
      }

      $this->log('CPR lookup successful: {name}', [
        'name' => $personData['full_name'] ?? 'Unknown',
      ]);

      $this->setTokenValue($this->configuration['result_token'], $personData);

    }
    catch (\Exception $e) {
      $this->handleError($e, 'CPR lookup via SF1520');
      $this->setTokenValue($this->configuration['result_token'], NULL);
    }
  }

}
