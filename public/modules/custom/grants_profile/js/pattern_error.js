(function (Drupal) {

  'use strict';

  /**
   * The patternError behavior.
   *
   * This behavior adds custom error messages to inputs
   * that have defined a data-pattern-error error message.
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
