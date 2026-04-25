<?php

declare(strict_types=1);

namespace Drupal\aabenforms_mitid\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * MitID OIDC settings form.
 *
 * Surfaces the most-touched fields from aabenforms_mitid.settings so
 * implementors don't need drush to switch between MitID test
 * (pp.mitid.dk) and a Keycloak mock during local development. The
 * advanced/seldom-changed bits (scopes, session storage backend, etc.)
 * stay in YAML for now.
 */
class MitidSettingsForm extends ConfigFormBase {

  protected function getEditableConfigNames(): array {
    return ['aabenforms_mitid.settings'];
  }

  public function getFormId(): string {
    return 'aabenforms_mitid_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('aabenforms_mitid.settings');

    $form['production'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Production mode'),
      '#description' => $this->t('Tick when pointing at a real MitID issuer. Leave unchecked when using the local Keycloak mock.'),
      '#default_value' => (bool) $config->get('production'),
    ];

    $form['oidc'] = [
      '#type' => 'details',
      '#title' => $this->t('OIDC endpoints'),
      '#open' => TRUE,
    ];
    foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'userinfo_endpoint'] as $key) {
      $form['oidc'][$key] = [
        '#type' => 'url',
        '#title' => $this->t('@label', ['@label' => str_replace('_', ' ', ucfirst($key))]),
        '#default_value' => (string) $config->get($key),
        '#required' => TRUE,
      ];
    }

    $form['client'] = [
      '#type' => 'details',
      '#title' => $this->t('Client credentials'),
      '#open' => TRUE,
    ];
    $form['client']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => (string) $config->get('client_id'),
    ];
    // Don't echo the existing secret - require an explicit re-entry to
    // change it. Empty submission leaves the stored value untouched.
    $form['client']['client_secret'] = [
      '#type' => 'password',
      '#title' => $this->t('Client secret'),
      '#description' => (string) $config->get('client_secret') !== ''
        ? $this->t('A secret is currently configured. Leave blank to keep it; type a new value to replace.')
        : $this->t('No secret configured.'),
    ];

    $form['security'] = [
      '#type' => 'details',
      '#title' => $this->t('Security'),
      '#open' => FALSE,
    ];
    $form['security']['required_assurance_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Required assurance level'),
      '#options' => [
        'low' => $this->t('Low'),
        'substantial' => $this->t('Substantial'),
        'high' => $this->t('High'),
      ],
      '#default_value' => (string) $config->get('security.required_assurance_level') ?: 'substantial',
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $config = $this->config('aabenforms_mitid.settings');
    $config->set('production', (bool) $form_state->getValue('production'));
    foreach (['issuer', 'authorization_endpoint', 'token_endpoint', 'userinfo_endpoint'] as $key) {
      $config->set($key, (string) $form_state->getValue($key));
    }
    $config->set('client_id', (string) $form_state->getValue('client_id'));
    $secret = (string) $form_state->getValue('client_secret');
    if ($secret !== '') {
      $config->set('client_secret', $secret);
    }
    $config->set('security.required_assurance_level', (string) $form_state->getValue('required_assurance_level'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
