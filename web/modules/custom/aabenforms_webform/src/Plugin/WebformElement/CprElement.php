<?php

namespace Drupal\aabenforms_webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'cpr' webform element for Danish CPR numbers.
 *
 * @WebformElement(
 *   id = "cpr",
 *   label = @Translation("CPR Number"),
 *   description = @Translation("Provides a form element for Danish personal identification numbers (CPR)."),
 *   category = @Translation("Danish Elements"),
 * )
 */
class CprElement extends TextField {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'maxlength' => 11,
      'pattern' => '\d{10}',
      'placeholder' => 'DDMMYY-XXXX',
      'description' => $this->t('Enter a 10-digit Danish personal identification number (CPR).'),
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Add CPR-specific attributes.
    $element['#attributes']['class'][] = 'cpr-field';
    $element['#attributes']['autocomplete'] = 'off';
    $element['#attributes']['inputmode'] = 'numeric';

    // GDPR/Security: Mask input for privacy.
    $element['#attributes']['data-mask-input'] = 'true';

    // Set default pattern if not specified.
    if (empty($element['#pattern'])) {
      $element['#pattern'] = '\d{10}';
    }

    // Set default placeholder if not specified.
    if (empty($element['#placeholder'])) {
      $element['#placeholder'] = 'DDMMYY-XXXX';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateElement(array $element, FormStateInterface $form_state, array $complete_form) {
    parent::validateElement($element, $form_state, $complete_form);

    $value = $element['#value'] ?? '';

    if (empty($value)) {
      // Let required validation handle empty values.
      return;
    }

    // Get the CPR validator service.
    $validator = \Drupal::service('aabenforms_webform.cpr_validator');

    if (!$validator->isValid($value)) {
      $form_state->setError($element, $this->t('Please enter a valid CPR number (10 digits).'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    if (empty($value)) {
      return '';
    }

    // For GDPR compliance, mask CPR numbers in display.
    // Only show first 6 digits (birthdate) and mask the rest.
    $cleaned = preg_replace('/[\s-]/', '', $value);

    if (strlen($cleaned) === 10) {
      return substr($cleaned, 0, 6) . '-****';
    }

    return '******-****';
  }

}
