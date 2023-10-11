(function (Drupal) {

  'use strict';

  /**
   * The patternError behavior.
   *
   * This behavior adds a custom error messages to inputs
   * that have defined a data-pattern-error error message.
   * The message is displayed by the browser if an inputs #pattern
   * fails to validate.
   */
  Drupal.behaviors.patternError = {
    attach: function(context, settings) {

      const inputsWithCustomErrors = document.querySelectorAll('[data-pattern-error]');

      inputsWithCustomErrors.forEach(input => {
        const errorMessage = input.getAttribute('data-pattern-error');

        input.addEventListener('invalid', function(event) {
          event.target.setCustomValidity(errorMessage);
        });

        input.addEventListener('input', function(event) {
          event.target.setCustomValidity('');
        });
      });

    }
  };
})(Drupal);
