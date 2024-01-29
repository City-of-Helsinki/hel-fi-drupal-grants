<?php

namespace Drupal\grants_handler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_form_errors\FormErrorHandler as InlineFormErrorHandler;

/**
 * Produces inline form errors.
 */
class FormErrorHandler extends InlineFormErrorHandler {

  /**
   * Loops through and displays all form errors.
   *
   * To disable inline form errors for an entire form set the
   * #disable_inline_form_errors property to TRUE on the top level of the $form
   * array:
   * @code
   * $form['#disable_inline_form_errors'] = TRUE;
   * @endcode
   * This should only be done when another appropriate accessibility strategy is
   * in place.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function displayErrorMessages(array $form, FormStateInterface $form_state) {
    // Skip generating inline form errors when opted out.
    if (!empty($form['#disable_inline_form_error_messages'])) {
      return;
    }

    parent::displayErrorMessages($form, $form_state);
  }

}
