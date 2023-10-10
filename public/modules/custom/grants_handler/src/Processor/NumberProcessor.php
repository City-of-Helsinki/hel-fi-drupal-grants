<?php

namespace Drupal\grants_handler\Processor;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a process.
 */
class NumberProcessor {

  /**
   * Process number fields to allow . or , and convert them for validators.
   */
  public static function process(&$element, FormStateInterface $form_state, &$complete_form) {
    $valueFromElement = $element['#value'] ?? NULL;
    $value = trim($valueFromElement);

    if (empty($value)) {
      return $element;
    }

    // Count the number of dots and commas.
    $dot_count = substr_count($value, '.');
    $comma_count = substr_count($value, ',');

    // Something weird, let the validators throw errors.
    if ($dot_count + $comma_count > 1) {
      return $element;
    }

    // All looks good, modify format.
    $value = str_replace(',', '.', $value);

    $element['#value'] = $value;

    return $element;
  }

}
