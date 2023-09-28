(function ($, Drupal) {

  'use strict';

  /**
   * The restrictedDatepicker behavior.
   *
   * This behavior dynamically changes the "min" and "value" values
   * on an "end date" field based on the selected date value
   * on a "start date" field.
   */
  Drupal.behaviors.restrictedDatepicker = {
    attach: function(context, settings) {

      /*
       * A Webform ID <=> Date fields map.
       *
       * This constant maps a Webform ID to
       * the machine names of a start and end
       * date field on said form.
       */
      const webformIdDateFieldMap = {
        'liikunta_tapahtuma': { // OK
          'start_date_field': 'edit-alkaa',
          'end_date_field': 'edit-paattyy',
        },
        'nuorisotoiminta_projektiavustush': { // OK
          'start_date_field': 'edit-projekti-alkaa',
          'end_date_field': 'edit-projekti-loppuu',
        },
        'kasko_ip_lisa': { // OK
          'start_date_field': 'edit-alkaen',
          'end_date_field': 'edit-paattyy',
        },
        'kuva_projekti': { // OK
          'start_date_field': 'edit-hanke-alkaa',
          'end_date_field': 'edit-hanke-loppuu',
        },
        'taide_ja_kulttuuri_kehittamisavu': {
          'start_date_field': 'edit-hanke-alkaa',
          'end_date_field': 'edit-hanke-loppuu',
        }
      };


      if (!settings.restricted_datepicker || !settings.restricted_datepicker.webform_id) {
        return;
      }

      // Find the date field for the active Webform.
      let webformId = settings.restricted_datepicker.webform_id;
      let startDateInput = document.getElementById(webformIdDateFieldMap[webformId]['start_date_field']);
      let endDateInput = document.getElementById(webformIdDateFieldMap[webformId]['end_date_field']);

      if (startDateInput && endDateInput) {
        startDateInput.addEventListener("change", function () {

          // Set the minimum value for the end date.
          endDateInput.min = startDateInput.value;

          // Change the value of the end date if it is before the start date.
          if (endDateInput.value && startDateInput.value > endDateInput.value) {
            endDateInput.value = startDateInput.value;
          }
        });
      }

    }
  };
})(jQuery, Drupal);
