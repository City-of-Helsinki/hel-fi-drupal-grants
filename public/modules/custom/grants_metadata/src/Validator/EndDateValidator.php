<?php

namespace Drupal\grants_metadata\Validator;

use Drupal\Core\Form\FormStateInterface;

/**
 * The EndDateValidator class.
 */
class EndDateValidator {

  /*
   * A Webform ID <=> Date fields map.
   *
   * This constant maps a Webform ID to
   * the machine names of a start and end
   * date field on said form.
   */
  const WEBFORM_ID_DATE_FIELD_MAP = [
    'liikunta_tapahtuma' => [
      'start_date_field' => 'alkaa',
      'end_date_field' => 'paattyy',
    ],
    'nuorisotoiminta_projektiavustush' => [
      'start_date_field' => 'projekti_alkaa',
      'end_date_field' => 'projekti_loppuu',
    ],
    'kasko_ip_lisa' => [
      'start_date_field' => 'alkaen',
      'end_date_field' => 'paattyy',
    ],
    'kuva_projekti' => [
      'start_date_field' => 'hanke_alkaa',
      'end_date_field' => 'hanke_loppuu',
    ],
    'taide_ja_kulttuuri_kehittamisavu' => [
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
    $tOpts = ['context' => 'grants_metadata'];
    $webformId = $form['#webform_id'];

    if (!$webformId) {
      return;
    }

    $dateFields = self::WEBFORM_ID_DATE_FIELD_MAP[$webformId];
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
      $formState->setError($element, t('The end date must come after the start date.', [], $tOpts));
    }
  }

}
