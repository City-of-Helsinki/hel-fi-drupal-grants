<?php

namespace Drupal\grants_metadata\Validator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Entity\Webform;

/**
 * The EndDateValidator class.
 */
class EndDateValidator {

  /*
   * A Application type <=> Date fields map.
   *
   * This constant maps a Webforms Application type
   * to the machine names of a start and end
   * date field on said form.
   */
  const APPLICATION_TYPE_DATE_FIELD_MAP = [
    'LIIKUNTATAPAHTUMA' => [
      'start_date_field' => 'alkaa',
      'end_date_field' => 'paattyy',
    ],
    'NUORPROJ' => [
      'start_date_field' => 'projekti_alkaa',
      'end_date_field' => 'projekti_loppuu',
    ],
    'KASKOIPLISA' => [
      'start_date_field' => 'alkaen',
      'end_date_field' => 'paattyy',
    ],
    'KUVAPROJ' => [
      'start_date_field' => 'hanke_alkaa',
      'end_date_field' => 'hanke_loppuu',
    ],
    'KUVAKEHA' => [
      'start_date_field' => 'hanke_alkaa',
      'end_date_field' => 'hanke_loppuu',
    ],
  ];

  /**
   * Validate an end date.
   *
   * The validation is done by comparing a pair of date fields.
   * If the end date is before the start date, then an error
   * is added to the form.
   *
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form): void {
    $webform = Webform::load($form['#webform_id']);
    if (!$webform) {
      return;
    }

    $applicationType = $webform->getThirdPartySetting('grants_metadata', 'applicationType');
    if (!$applicationType) {
      return;
    }

    $dateFields = self::APPLICATION_TYPE_DATE_FIELD_MAP[$applicationType];
    $startDateValue = $formState->getValue($dateFields['start_date_field']);
    $endDateValue = $formState->getValue($dateFields['end_date_field']);

    $startDate = strtotime($startDateValue);
    $endDate = strtotime($endDateValue);

    // Skip this particular validation if we don't have either value.
    if (!$endDate || !$startDate) {
      return;
    }

    // Check that the end dates is after the start date.
    if ($endDate < $startDate) {
      $tOpts = ['context' => 'grants_metadata'];
      $formState->setError($element, t('The end date must come after the start date.', [], $tOpts));
    }
  }

}
