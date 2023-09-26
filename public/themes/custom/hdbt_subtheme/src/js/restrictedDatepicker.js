(function ($, Drupal) {

  'use strict';

  /**
   * The restrictedDatepicker behavior.
   *
   * This behavior dynamically changes the "minDate" value
   * on an "end date" field based on the selected date value
   * on a "start date" field.
   */
  Drupal.behaviors.restrictedDatepicker = {
    attach: function(context, settings) {

      const startDateInput = document.getElementById("edit-alkaa");
      const endDateInput = document.getElementById("edit-paattyy");

      if (startDateInput && endDateInput) {
        startDateInput.addEventListener("input", function () {
          endDateInput.min = startDateInput.value;
        });
      }
    }
  };
})(jQuery, Drupal);
