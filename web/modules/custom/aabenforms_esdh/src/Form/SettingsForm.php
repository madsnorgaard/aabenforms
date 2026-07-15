<?php

declare(strict_types=1);

namespace Drupal\aabenforms_esdh\Form;

use Drupal\aabenforms_esdh\EsdhConnectorManager;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Selects the active ESDH connector.
 *
 * Live connectors additionally require their own transport config (base URLs,
 * OAuth2/certificate credentials sourced from env vars) before they will run;
 * until then they fail hard rather than silently degrading to demo.
 */
final class SettingsForm extends ConfigFormBase {

  public function __construct(
    $config_factory,
    $typed_config_manager,
    private readonly EsdhConnectorManager $connectorManager,
  ) {
    parent::__construct($config_factory, $typed_config_manager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('plugin.manager.aabenforms_esdh_connector'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'aabenforms_esdh_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['aabenforms_esdh.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $options = [];
    foreach ($this->connectorManager->getDefinitions() as $id => $definition) {
      $label = (string) ($definition['label'] ?? $id);
      $options[$id] = $definition['demo'] ?? FALSE ? $label . ' — ' . $this->t('demo') : $label;
    }

    $form['active_connector'] = [
      '#type' => 'select',
      '#title' => $this->t('Active ESDH connector'),
      '#description' => $this->t('The system of record cases are journalised into. Live connectors require their own credentials (env vars) and will fail until configured. This is separate from SF1470, the fælleskommunale index.'),
      '#options' => $options,
      '#default_value' => $this->config('aabenforms_esdh.settings')->get('active_connector') ?: 'demo',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('aabenforms_esdh.settings')
      ->set('active_connector', (string) $form_state->getValue('active_connector'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
