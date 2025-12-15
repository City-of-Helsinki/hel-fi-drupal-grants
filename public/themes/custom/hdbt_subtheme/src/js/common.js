(($, Drupal, _drupalSettings) => {
  Drupal.behaviors.themeCommon = {
    attach: function attach() {
      $(document).ready(() => {
        $('input:not([type="file"]):not(.js-webform-input-mask), textarea').on('change', function fn() {
          this.value = $.trim($(this).val());
        });

        const queryString = window.location.search;
        const subString = 'items_per_page=';

        const substringIndex = queryString.indexOf(subString);

        if (queryString.includes(subString)) {
          const selectElement = document.getElementById('search-result-amount');

          if (selectElement) {
            // Loop through the <option> elements in the <select>
            for (const option of selectElement) {
              const characterAfterSubstring = queryString.substring(substringIndex + subString.length);

              // Check if the option's label matches the value you want to select
              if (option.label === characterAfterSubstring) {
                // Set the option as selected
                option.selected = true;

                // Optionally, break the loop if you only want to select one option
                break;
              }
            }
          }
        }

        $('button.reset-search').on('click', function fn() {
          const datafieldRaw = $(this).attr('data-field');
          const datafield = datafieldRaw.replaceAll('_', '-');
          $(`#${datafield}`).val('All');
          $('#views-exposed-form-application-search-search-api-search-page').submit();
        });

        // Attach a click event handler to the close button.
        $('.information-announcement-close').on('click', () => {
          // Send an AJAX request to the Drupal route.
          $.ajax({ url: '/oma-asiointi/log-close-time/', method: 'GET' });
        });
      });
    },
  };
})(jQuery, Drupal, drupalSettings);
