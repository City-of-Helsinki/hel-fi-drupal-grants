// eslint-disable-next-line no-unused-vars
(($, Drupal, drupalSettings) => {
  Drupal.behaviors.themeCommon = {
    attach: function attach() {

      $(document).ready(function(){
        const queryString = window.location.search;
        const subString = 'items_per_page=';

        const substringIndex = queryString.indexOf(subString);

        if (queryString.includes(subString)) {
          const selectElement = document.getElementById('search-result-amount');

          if (selectElement) {
            // Loop through the <option> elements in the <select>
            for (let i = 0; i < selectElement.options.length; i++) {
              const characterAfterSubstring = queryString.substring(substringIndex + subString.length);
              const option = selectElement.options[i];

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

        $('a.reset-search').on( 'click', function() {
          console.log('sfsdf')
          const datafield = $(this).attr('data-field');
          console.log(datafield)
          $('#'+datafield).val('All');
          $( '#views-exposed-form-application-search-search-api-search-page' ).submit();
        });
      });

    },
  };
  // eslint-disable-next-line no-undef
})(jQuery, Drupal, drupalSettings);
