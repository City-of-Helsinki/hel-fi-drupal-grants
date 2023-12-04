(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.GrantsHandlerMandateSubmitBehavior = {
    attach: function (context, settings) {
      $('#edit-submit--2').prop('disabled', true);

      $('#edit-unregistered-community-selection').change(function() {
        var selectedValue = $(this).val();
        var submitButton = $('#edit-submit--2');

        if (selectedValue === '0') {
          submitButton.prop('disabled', true);
        }
        else {
          submitButton.prop('disabled', false);
        }

        if (selectedValue === 'new') {
          submitButton.html('<span class="hds-button__label">' + Drupal.t('Add new Unregistered community or group', {}, {context: "grants_mandate"}) + '</span>');
        } else {
          submitButton.html('<span class="hds-button__label">' + Drupal.t('Select Unregistered community or group role', {}, {context: "grants_mandate"}) + '</span>');
        }
     });
    }
  };
})(jQuery, Drupal, drupalSettings);
