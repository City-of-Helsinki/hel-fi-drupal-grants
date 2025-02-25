(($, Drupal) => {
  /**
   * The restrictedDatepicker behavior.
   *
   * This behavior dynamically changes the "min" and "value" values
   * on an "end date" field based on the selected date value
   * on a "start date" field.
   */
  Drupal.behaviors.restrictedDatepicker = {
    attach(context, settings) {
      /*
       * A Application type <=> Date fields map.
       *
       * This constant maps a Webforms Application type
       * to the machine names of a start and end
       * date field on said form.
       */
      const applicationTypeDateFieldMap = {
        LIIKUNTATAPAHTUMA: {
          start_date_field: 'edit-alkaa',
          end_date_field: 'edit-paattyy',
        },
        NUORPROJ: {
          start_date_field: 'edit-projekti-alkaa',
          end_date_field: 'edit-projekti-loppuu',
        },
        KASKOIPLISA: {
          start_date_field: 'edit-alkaen',
          end_date_field: 'edit-paattyy',
        },
        KUVAPROJ: {
          start_date_field: 'edit-hanke-alkaa',
          end_date_field: 'edit-hanke-loppuu',
        },
        KUVAKEHA: {
          start_date_field: 'edit-hanke-alkaa',
          end_date_field: 'edit-hanke-loppuu',
        },
      };

      if (
        !settings.restricted_datepicker ||
        !settings.restricted_datepicker.application_type
      ) {
        return;
      }

      // Find the date field for the active Webform.
      const applicationType = settings.restricted_datepicker.application_type;
      const startDateInput = document.getElementById(
        applicationTypeDateFieldMap[applicationType].start_date_field,
      );
      const endDateInput = document.getElementById(
        applicationTypeDateFieldMap[applicationType].end_date_field,
      );

      if (startDateInput && endDateInput) {
        startDateInput.addEventListener('change', () => {
          // Set the minimum value for the end date.
          endDateInput.min = startDateInput.value;

          // Change the value of the end date if it is before the start date.
          if (endDateInput.value && startDateInput.value > endDateInput.value) {
            endDateInput.value = startDateInput.value;
          }
        });
      }
    },
  };
})(jQuery, Drupal);
