<?php

namespace Drupal\grants_metadata\Validator;

use Drupal\Core\Form\FormStateInterface;

/**
 * The YearValidator class.
 *
 * This class validates the year field in either the
 * "myonnetty_avustus" or "haettu_avustus_tieto" composite
 * Webform fields.
 */
class YearValidator {

  /**
   * Validate a year.
   *
   * Validates if the entered year is a number
   * between 1900 - 2100.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form): void {
    $webformKey = $element['#webform_key'];
    $value = $formState->getValue($webformKey);
    $tOpts = ['context' => 'grants_metadata'];

    if (!is_array($value) || !isset($value[0]['year'])) {
      return;
    }

    $year = $value[0]['year'];
    if ($year === '') {
      return;
    }

    if (!preg_match("/^(19\d\d|20\d\d|2100)$/", $year)) {
      $formState->setError($element, t('Enter a year between 1900 - 2100.', [], $tOpts));
    }
  }

}
