<?php

declare(strict_types=1);

namespace Drupal\aabenforms_tenant\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form for AabenForms tenant employee provisioning.
 *
 * Maps an OIDC claim (delivered by MitID/NemLogin in the id_token) to
 * the aabenforms_employee role. Empty claim field disables automatic
 * provisioning so admins can fall back to manual role assignment.
 */
class TenantSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['aabenforms_tenant.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'aabenforms_tenant_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('aabenforms_tenant.settings');

    $form['employee_provisioning'] = [
      '#type' => 'details',
      '#title' => $this->t('Employee role provisioning'),
      '#open' => TRUE,
      '#description' => $this->t('Citizens whose OIDC claim matches the rule below are granted the aabenforms_employee role on login, which unlocks HR webforms. Leave the claim field empty to disable automatic provisioning.'),
    ];
    $form['employee_provisioning']['employee_claim_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Claim field'),
      '#description' => $this->t('OIDC claim name to read (e.g. <code>employee_id</code>, <code>dk:gov:saml:attribute:CprNumberIdentifier</code>, <code>email</code>).'),
      '#default_value' => (string) $config->get('employee_provisioning.employee_claim_field'),
    ];
    $form['employee_provisioning']['employee_claim_match'] = [
      '#type' => 'select',
      '#title' => $this->t('Match rule'),
      '#options' => [
        'equals' => $this->t('Exact equals'),
        'starts_with' => $this->t('Starts with (useful for email domains)'),
      ],
      '#default_value' => (string) ($config->get('employee_provisioning.employee_claim_match') ?: 'equals'),
    ];
    $form['employee_provisioning']['employee_claim_value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Expected value'),
      '#description' => $this->t('For "equals", the exact claim string. For "starts_with", a prefix like <code>@mycommune.dk</code>.'),
      '#default_value' => (string) $config->get('employee_provisioning.employee_claim_value'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('aabenforms_tenant.settings')
      ->set('employee_provisioning.employee_claim_field', (string) $form_state->getValue('employee_claim_field'))
      ->set('employee_provisioning.employee_claim_match', (string) $form_state->getValue('employee_claim_match'))
      ->set('employee_provisioning.employee_claim_value', (string) $form_state->getValue('employee_claim_value'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
