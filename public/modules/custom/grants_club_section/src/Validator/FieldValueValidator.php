<?php

namespace Drupal\grants_club_section\Validator;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a validator function for other cost/income elements.
 */
class FieldValueValidator {

  /**
   * Form element validation for club section age fields.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validate(&$element, FormStateInterface $form_state) {
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $parent = $form_state->getValue($parents);
    $tOpts = ['context' => 'grants_club_section'];

    if (empty($parent['men'])
      && empty($parent['women'])
      && empty($parent['adultOthers'])
      && empty($parent['seniorMen'])
      && empty($parent['seniorWomen'])
      && empty($parent['seniorOthers'])
      && empty($parent['boys'])
      && empty($parent['girls'])
      && empty($parent['juniorOthers'])) {
      $form_state->setError(
        $element,
        t(
          "At least one age group is required.",
          [],
          $tOpts
        )
      );
    }

  }

  /**
   * Form element validation for club section adult hours fields.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateAdultHours(&$element, FormStateInterface $form_state) {
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $parent = $form_state->getValue($parents);
    $tOpts = ['context' => 'grants_club_section'];

    if ((!empty($parent['men']) || !empty($parent['women']) || !empty($parent['adultOthers'])) && empty($parent['adultHours'])) {
      $form_state->setError(
        $element,
        t(
          "Add practice hours.",
          [],
          $tOpts
        )
      );
    }

  }

  /**
   * Form element validation for club section senior hours fields.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateSeniorHours(&$element, FormStateInterface $form_state) {
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $parent = $form_state->getValue($parents);
    $tOpts = ['context' => 'grants_club_section'];

    if (!empty($parent['seniorMen']) || !empty($parent['seniorWomen']) || !empty($parent['seniorOthers'])) {
      $form_state->setError(
        $element,
        t(
          "Add practice hours.",
          [],
          $tOpts
        )
      );
    }

  }

  /**
   * Form element validation for club section junior hours fields.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateJuniorHours(&$element, FormStateInterface $form_state) {
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $parent = $form_state->getValue($parents);
    $tOpts = ['context' => 'grants_club_section'];

    if (!empty($parent['boys']) || !empty($parent['girls']) || !empty($parent['juniorOthers'])) {
      $form_state->setError(
        $element,
        t(
          "Add practice hours.",
          [],
          $tOpts
        )
      );
    }

  }

}
