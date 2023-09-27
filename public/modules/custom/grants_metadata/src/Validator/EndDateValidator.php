<?php

namespace Drupal\grants_metadata\Validator;

use Drupal\Core\Form\FormStateInterface;

/**
 * The EndDateValidator class.
 */
class EndDateValidator {

  /*
   * A Webform ID <=> Date fields map.
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
   * @param array $element
   *   The form element to process.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   * @param array $form
   *   The complete form structure.
   */
  public static function validate(array &$element, FormStateInterface $formState, array &$form): void {
    $webformId = $form['#webform_id'];
    $dateFields = self::WEBFORM_ID_DATE_FIELD_MAP[$webformId];

    $startDateValue = $formState->getValue($dateFields['start_date_field']);
    $endDateValue = $formState->getValue($dateFields['end_date_field']);

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
