<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_core\Service\ServiceplatformenClient;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: CVR Company Lookup via Serviceplatformen SF1530.
 *
 * @Action(
 *   id = "aabenforms_cvr_lookup",
 *   label = @Translation("CVR Company Lookup"),
 *   description = @Translation("Looks up company data from Serviceplatformen SF1530 using CVR number."),
 *   type = "aabenforms"
 * )
 */
class CvrLookupAction extends AabenFormsActionBase {

  /**
   * The Serviceplatformen client.
   *
   * @var \Drupal\aabenforms_core\Service\ServiceplatformenClient
   */
  protected ServiceplatformenClient $serviceplatformenClient;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->serviceplatformenClient = $container->get('aabenforms_core.serviceplatformen_client');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'cvr_token' => 'cvr',
      'result_token' => 'company_data',
      'use_cache' => TRUE,
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['cvr_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CVR token name'),
      '#description' => $this->t('Token containing the CVR number to look up.'),
      '#default_value' => $this->configuration['cvr_token'],
      '#required' => TRUE,
    ];

    $form['result_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Result token name'),
      '#description' => $this->t('Token to store company data result.'),
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
    $this->configuration['cvr_token'] = $form_state->getValue('cvr_token');
    $this->configuration['result_token'] = $form_state->getValue('result_token');
    $this->configuration['use_cache'] = $form_state->getValue('use_cache');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $cvr = $this->getTokenValue($this->configuration['cvr_token']);

    if (empty($cvr)) {
      $this->log('CVR lookup failed: No CVR number provided', [], 'warning');
      $this->setTokenValue($this->configuration['result_token'], NULL);
      return;
    }

    // Clean CVR (remove spaces/hyphens).
    $cvr = preg_replace('/[^0-9]/', '', $cvr);

    try {
      $this->log('Performing CVR lookup via SF1530 for: {cvr}', [
        'cvr' => $cvr,
      ]);

      // Call SF1530 via ServiceplatformenClient.
      $options = [
        'no_cache' => !$this->configuration['use_cache'],
      ];

      $result = $this->serviceplatformenClient->request(
        'SF1530',
        'CompanyLookup',
        ['cvr' => $cvr],
        $options
      );

      // Extract company data.
      $companyData = $result['company'] ?? NULL;

      if (empty($companyData)) {
        $this->log('CVR lookup returned no data for: {cvr}', [
          'cvr' => $cvr,
        ], 'warning');
        $this->setTokenValue($this->configuration['result_token'], NULL);
        return;
      }

      $this->log('CVR lookup successful: {name}', [
        'name' => $companyData['name'] ?? 'Unknown',
      ]);

      $this->setTokenValue($this->configuration['result_token'], $companyData);

    }
    catch (\Exception $e) {
      $this->handleError($e, 'CVR lookup via SF1530');
      $this->setTokenValue($this->configuration['result_token'], NULL);
    }
  }

}
