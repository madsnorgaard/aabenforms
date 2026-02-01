<?php

namespace Drupal\aabenforms_webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'dawa_address' webform element for Danish addresses.
 *
 * Integrates with DAWA (Danmarks Adressers Web API) for autocomplete.
 *
 * @WebformElement(
 *   id = "dawa_address",
 *   label = @Translation("DAWA Address"),
 *   description = @Translation("Danish address with autocomplete via DAWA API."),
 *   category = @Translation("Danish Elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class DawaAddressElement extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'enable_geolocation' => FALSE,
      'require_valid_address' => TRUE,
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultMultipleProperties() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    if (empty($value)) {
      return '';
    }

    $lines = [];
    if (!empty($value['street'])) {
      $lines[] = $value['street'];
    }
    if (!empty($value['postal_code']) || !empty($value['city'])) {
      $lines[] = trim(($value['postal_code'] ?? '') . ' ' . ($value['city'] ?? ''));
    }

    return implode("\n", array_filter($lines));
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];

    // Address search field with autocomplete.
    $elements['search'] = [
      '#type' => 'textfield',
      '#title' => t('Search address'),
      '#placeholder' => t('Start typing street name...'),
      '#attributes' => [
        'class' => ['dawa-address-search'],
        'data-dawa-autocomplete' => 'true',
        'autocomplete' => 'off',
      ],
    ];

    // Street address.
    $elements['street'] = [
      '#type' => 'textfield',
      '#title' => t('Street address'),
      '#required' => !empty($element['#required']),
      '#attributes' => [
        'class' => ['dawa-address-street'],
        'readonly' => 'readonly',
      ],
    ];

    // Postal code.
    $elements['postal_code'] = [
      '#type' => 'textfield',
      '#title' => t('Postal code'),
      '#maxlength' => 4,
      '#required' => !empty($element['#required']),
      '#attributes' => [
        'class' => ['dawa-address-postal-code'],
        'readonly' => 'readonly',
        'pattern' => '\d{4}',
      ],
    ];

    // City.
    $elements['city'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
      '#required' => !empty($element['#required']),
      '#attributes' => [
        'class' => ['dawa-address-city'],
        'readonly' => 'readonly',
      ],
    ];

    // Hidden fields for structured data.
    $elements['dawa_id'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['dawa-address-id'],
      ],
    ];

    $elements['x_coordinate'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['dawa-address-x'],
      ],
    ];

    $elements['y_coordinate'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'class' => ['dawa-address-y'],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Attach DAWA JavaScript library.
    $element['#attached']['library'][] = 'aabenforms_webform/dawa_address';

    // Add DAWA API endpoint to drupalSettings.
    $element['#attached']['drupalSettings']['aabenforms_webform']['dawa_api_url'] = 'https://api.dataforsyningen.dk/autocomplete';
  }

  /**
   * {@inheritdoc}
   */
  public static function validateWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::validateWebformComposite($element, $form_state, $complete_form);

    $value = $element['#value'] ?? [];

    // Skip validation if not required and empty.
    if (empty($element['#required']) && empty($value['street'])) {
      return;
    }

    // Validate required fields.
    $requiredFields = ['street', 'postal_code', 'city'];
    foreach ($requiredFields as $field) {
      if (empty($value[$field])) {
        $form_state->setError($element, t('Please select a valid Danish address from the autocomplete suggestions.'));
        return;
      }
    }

    // Validate postal code format.
    if (!empty($value['postal_code']) && !preg_match('/^\d{4}$/', $value['postal_code'])) {
      $form_state->setError($element, t('Postal code must be 4 digits.'));
    }

    // Validate DAWA ID if require_valid_address is enabled.
    if (!empty($element['#require_valid_address']) && empty($value['dawa_id'])) {
      $form_state->setError($element, t('Please select an address from the autocomplete suggestions.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['element']['enable_geolocation'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable geolocation'),
      '#description' => $this->t('Store X/Y coordinates (ETRS89/UTM32) for mapping.'),
      '#return_value' => TRUE,
    ];

    $form['element']['require_valid_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Require valid DAWA address'),
      '#description' => $this->t('User must select from autocomplete suggestions (prevents free-text entry).'),
      '#return_value' => TRUE,
    ];

    return $form;
  }

}
