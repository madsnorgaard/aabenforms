<?php

namespace Drupal\aabenforms_workflows\Plugin\Action;

use Drupal\aabenforms_core\Service\ServiceplatformenClient;
use Drupal\Core\Action\Attribute\Action;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\eca\Attribute\EcaAction;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ECA Action: CPR Person Lookup via Serviceplatformen SF1520.
 */
#[Action(
  id: 'aabenforms_cpr_lookup',
  label: new TranslatableMarkup('CPR Person Lookup'),
  type: 'aabenforms',
)]
#[EcaAction(
  description: new TranslatableMarkup('Looks up person data from Serviceplatformen SF1520 using CPR number.'),
  version_introduced: '2.0.0',
)]
class CprLookupAction extends AabenFormsActionBase {

  /**
   * The Serviceplatformen client.
   *
   * @var \Drupal\aabenforms_core\Service\ServiceplatformenClient
   */
  protected ServiceplatformenClient $serviceplatformenClient;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->serviceplatformenClient = $container->get('aabenforms_core.serviceplatformen_client');
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * Whether to run CPR lookup in demo mode (no real Serviceplatformen call).
   *
   * True when the demo flag is set, or - the common case for a POC - when no
   * Serviceplatformen client certificate is provisioned. Once a certificate is
   * configured, real SF1520 lookups resume automatically.
   */
  protected function demoModeAllowed(): bool {
    if ($this->configFactory->get('aabenforms_workflows.settings')->get('allow_cpr_demo_mode')) {
      return TRUE;
    }
    $certs = $this->configFactory->get('aabenforms_core.settings')->get('serviceplatformen.certificates') ?? [];
    return empty($certs['cert_path']) && empty($certs['key_path']);
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
    $cpr = $this->getTokenValue($this->configuration['cpr_token'], '');

    // Clean CPR (remove hyphens/spaces).
    $cpr = $cpr ? preg_replace('/[^0-9]/', '', $cpr) : '';

    if (empty($cpr)) {
      $this->log('CPR lookup skipped: no CPR available to look up', [], 'warning');
      $this->setTokenValue($this->configuration['result_token'], NULL);
      $this->recordStep('CPR Registry Lookup', 'Skipped - no CPR available to look up', 'skipped');
      return;
    }

    if ($this->demoModeAllowed()) {
      // No Serviceplatformen certificate is provisioned (the POC case). Do not
      // call SF1520; record an honest, clearly-labelled demo step with test
      // data so the flow continues without surfacing a connection error.
      $demoPerson = [
        'cpr' => substr($cpr, 0, 6) . 'XXXX',
        'full_name' => 'Demoborger (testdata)',
        'demo' => TRUE,
      ];
      $this->setTokenValue($this->configuration['result_token'], $demoPerson);
      $this->log('CPR lookup ran in demo mode (no Serviceplatformen certificate).', [], 'info');
      $this->recordStep('CPR Registry Lookup', 'Demo: CPR-opslag simuleret med testdata. Rigtige Serviceplatformen-opslag kraever klientcertifikat.', 'completed');
      return;
    }

    try {
      $this->log('Performing CPR lookup via SF1520 for: {cpr}', [
        'cpr' => substr($cpr, 0, 6) . 'XXXX',
      ]);

      $options = [
        'no_cache' => !$this->configuration['use_cache'],
      ];

      $result = $this->serviceplatformenClient->request(
        'SF1520',
        'PersonLookup',
        ['cpr' => $cpr],
        $options
      );

      $personData = $result['person'] ?? NULL;

      if (empty($personData)) {
        $this->log('CPR lookup returned no data for: {cpr}', [
          'cpr' => substr($cpr, 0, 6) . 'XXXX',
        ], 'warning');
        $this->setTokenValue($this->configuration['result_token'], NULL);
        $this->recordStep('CPR Registry Lookup', 'No person found in the national CPR registry (SF1520)', 'failed');
        return;
      }

      $this->log('CPR lookup successful: {name}', [
        'name' => $personData['full_name'] ?? 'Unknown',
      ]);

      $this->setTokenValue($this->configuration['result_token'], $personData);
      $this->recordStep('CPR Registry Lookup', 'Personal data retrieved from the national CPR registry (SF1520)');

    }
    catch (\Exception $e) {
      // Keep the technical detail in the server log, but never surface a raw
      // cURL/SSL/Serviceplatformen error to the citizen.
      $this->log('CPR lookup failed: {message}', ['message' => $e->getMessage()], 'error');
      $this->setTokenValue($this->configuration['result_token'], NULL);
      $this->recordStep('CPR Registry Lookup', 'CPR-opslaget er midlertidigt utilgaengeligt. Prov igen senere.', 'failed');
    }
  }

}
