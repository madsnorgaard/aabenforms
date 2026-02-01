<?php

namespace Drupal\aabenforms_webform\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'cvr' webform element for Danish company registration numbers.
 *
 * @FormElement("cvr")
 */
class CvrField extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#input' => TRUE,
      '#process' => [
        [get_class($this), 'processWebformComposite'],
        [get_class($this), 'processAjaxForm'],
      ],
      '#pre_render' => [
        [get_class($this), 'preRenderWebformCompositeFormElement'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#pattern' => '\d{8}',
      '#maxlength' => 11,
      '#placeholder' => '12 34 56 78',
      '#attributes' => [
        'class' => ['cvr-field'],
        'autocomplete' => 'off',
      ],
    ] + parent::getInfo();
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];

    $elements['cvr'] = [
      '#type' => 'textfield',
      '#title' => t('CVR Number'),
      '#title_display' => 'invisible',
      '#maxlength' => 11,
      '#pattern' => '\d{8}',
      '#placeholder' => '12 34 56 78',
      '#attributes' => [
        'class' => ['cvr-input'],
        'autocomplete' => 'off',
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    parent::validateWebformComposite($element, $form_state, $complete_form);

    $value = $element['#value']['cvr'] ?? '';

    if (empty($value)) {
      // Let required validation handle empty values.
      return;
    }

    // Get the CVR validator service.
    $validator = \Drupal::service('aabenforms_webform.cvr_validator');

    if (!$validator->isValid($value)) {
      $form_state->setError($element, t('Please enter a valid CVR number (8 digits).'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preRenderCompositeFormElement($element) {
    $element = parent::preRenderCompositeFormElement($element);

    // Add help text.
    if (empty($element['#description'])) {
      $element['#description'] = t('Enter an 8-digit Danish company registration number (CVR).');
    }

    return $element;
  }

}
