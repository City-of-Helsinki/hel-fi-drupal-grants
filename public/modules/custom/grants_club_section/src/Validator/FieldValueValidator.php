<?php

namespace Drupal\grants_club_section\Validator;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a validator function for other cost/income elements.
 */
class FieldValueValidator {

  /**
   * Form element validation for club section adult age fields.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateAdults(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $parent = $form_state->getValue($parents);
    $tOpts = ['context' => 'grants_club_section'];

    $dhsc = $element;

    if (empty($parent['men']) && empty($parent['women']) && empty($parent['adultOthers'])) {
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
   * Form element validation for club section senior age fields.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateSeniors(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $parent = $form_state->getValue($parents);
    $tOpts = ['context' => 'grants_club_section'];

    if (empty($parent['seniorMen']) && empty($parent['seniorWomen']) && empty($parent['seniorOthers'])) {
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
   * Form element validation for club section junior age fields.
   *
   * Note that #required is validated by _form_validate() already.
   */
  public static function validateJuniors(&$element, FormStateInterface $form_state, &$complete_form) {
    $parents = $element['#parents'];
    $field = array_pop($parents);
    $parent = $form_state->getValue($parents);
    $tOpts = ['context' => 'grants_club_section'];

    if (empty($parent['boys']) && empty($parent['girls']) && empty($parent['juniorOthers'])) {
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

}
