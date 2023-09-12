<?php

namespace Drupal\grants_metadata\Validator;

use Drupal\Core\Form\FormStateInterface;

/**
 * The EndDateValidator class.
 */
class EndDateValidator {

  /**
   * The machine name of the start date element.
   */
  const START_DATE_ELEMENT_ID = 'alkaa';

  /**
   * The machine name of the end date element.
   */
  const END_DATE_ELEMENT_ID = 'paattyy';

  /**
   * Validate an end date.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form): void {
    $startDateValue = $formState->getValue(self::START_DATE_ELEMENT_ID);
    $endDateValue = $formState->getValue(self::END_DATE_ELEMENT_ID);
    $startDate = strtotime($startDateValue);
    $endDate = strtotime($endDateValue);
    $tOpts = ['context' => 'grants_metadata'];

    // Skip this particular validation if we don't have either value.
    if (!$endDate || !$startDate) {
      return;
    }

    // Check that the end dates is after the start date.
    if ($endDate < $startDate) {
      $formState->setError($element, t('The end date must come after the start date.', [], $tOpts));
    }
  }

}
