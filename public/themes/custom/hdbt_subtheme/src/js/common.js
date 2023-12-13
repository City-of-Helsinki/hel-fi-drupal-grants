// eslint-disable-next-line no-unused-vars
(($, Drupal, drupalSettings) => {
  Drupal.behaviors.themeCommon = {
    attach: function attach() {
      $(document).ready(function () {
        const queryString = window.location.search;
        const subString = "items_per_page=";

        const substringIndex = queryString.indexOf(subString);

        if (queryString.includes(subString)) {
          const selectElement = document.getElementById("search-result-amount");

          if (selectElement) {
            // Loop through the <option> elements in the <select>
            for (const option of selectElement) {
              const characterAfterSubstring = queryString.substring(
                substringIndex + subString.length,
              );

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

        $("button.reset-search").on("click", function () {
          const datafieldRaw = $(this).attr("data-field");
          const datafield = datafieldRaw.replaceAll("_", "-");
          $(`#${datafield}`).val("All");
          $(
            "#views-exposed-form-application-search-search-api-search-page",
          ).submit();
        });

        // Attach a click event handler to the close button.
        $('.announcement__close').on('click', function () {
          // Get the current time in milliseconds.
          const closeTime = Date.now();

          // Send an AJAX request to the Drupal route with the close time.
          $.ajax({
            url: '/oma-asiointi/log-close-time/' + closeTime,
            method: 'GET',
          });

        });
      });
    },
  };
  // eslint-disable-next-line no-undef
})(jQuery, Drupal, drupalSettings);
