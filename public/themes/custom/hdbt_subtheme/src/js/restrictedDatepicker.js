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

      const dateFieldPairs = [
        ["edit-alkaa", "edit-paattyy"],
        ["edit-projekti-alkaa", "edit-projekti-loppuu"],
        ["edit-alkaen", "edit-paattyy"],
        ["edit-hanke-alkaa", "edit-hanke-loppuu"],
      ];

      for (let pair of dateFieldPairs) {
        let startDateInput = document.getElementById(pair[0]);
        let endDateInput = document.getElementById(pair[1]);

        if (startDateInput && endDateInput) {
          startDateInput.addEventListener("input", function () {
            endDateInput.min = startDateInput.value;
          });
        }
      }

    }
  };
})(jQuery, Drupal);
