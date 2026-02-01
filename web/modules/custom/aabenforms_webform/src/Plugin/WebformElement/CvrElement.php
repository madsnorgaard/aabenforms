<?php

namespace Drupal\aabenforms_webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\TextField;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'cvr' webform element for Danish CVR numbers.
 *
 * @WebformElement(
 *   id = "cvr",
 *   label = @Translation("CVR Number"),
 *   description = @Translation("Provides a form element for Danish company registration numbers (CVR)."),
 *   category = @Translation("Danish Elements"),
 * )
 */
class CvrElement extends TextField {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    return [
      'maxlength' => 11,
      'pattern' => '\d{8}',
      'placeholder' => '12 34 56 78',
      'description' => $this->t('Enter an 8-digit Danish company registration number (CVR).'),
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, ?WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Add CVR-specific attributes.
    $element['#attributes']['class'][] = 'cvr-field';
    $element['#attributes']['autocomplete'] = 'off';
    $element['#attributes']['inputmode'] = 'numeric';

    // Set default pattern if not specified.
    if (empty($element['#pattern'])) {
      $element['#pattern'] = '\d{8}';
    }

    // Set default placeholder if not specified.
    if (empty($element['#placeholder'])) {
      $element['#placeholder'] = '12 34 56 78';
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

    // Get the CVR validator service.
    $validator = \Drupal::service('aabenforms_webform.cvr_validator');

    if (!$validator->isValid($value)) {
      $form_state->setError($element, $this->t('Please enter a valid CVR number (8 digits).'));
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

    // Format CVR for display.
    $validator = \Drupal::service('aabenforms_webform.cvr_validator');
    return $validator->format($value);
  }

}
