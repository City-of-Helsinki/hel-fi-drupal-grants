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

      $(document).ready(function() {
        const startDate = $("#edit-tapahtuma-ajankohta #edit-alkaa");
        const endDate = $("#edit-tapahtuma-ajankohta #edit-paattyy");

        if (startDate.length && endDate.length) {
          startDate.datepicker("option", "onSelect", function (selectedDate) {
            endDate.datepicker("option", "minDate", selectedDate);
          });
        }

      });
    }
  };
})(jQuery, Drupal);
