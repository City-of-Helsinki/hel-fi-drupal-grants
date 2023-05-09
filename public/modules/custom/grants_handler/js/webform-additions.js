(function ($, Drupal, drupalSettings) {
  Drupal.behaviors.GrantsHandlerBehavior = {
    attach: function (context, settings) {

      const formData = drupalSettings.grants_handler.formData
      const selectedCompany = drupalSettings.grants_handler.selectedCompany
      const submissionId = drupalSettings.grants_handler.submissionId

      if (formData['status'] === 'DRAFT' && !$("#webform-button--delete-draft").length) {
        $('#edit-actions').append($('<a id="webform-button--delete-draft" class="webform-button--delete-draft hds-button hds-button--secondary" href="/hakemus/' + submissionId + '/clear">' +
            '  <span class="hds-button__label">' + Drupal.t('Delete draft') + '</span>' +
            '</a>'));
      }

      $("#edit-bank-account-account-number-select").change(function () {
        // Get selected account from dropdown
        const selectedNumber = $(this).val();
        // Get bank account info on this selected account.
        const selectedAccountArray = drupalSettings.grants_handler
            .grantsProfile.bankAccounts
            .filter(item => item.bankAccount === selectedNumber);
        const selectedAccount = selectedAccountArray[0];

        // Always set the number
        $("[data-drupal-selector='edit-bank-account-account-number']").val(selectedAccount.bankAccount);

        // Only set name & ssn if they're present in the profile.
        if (selectedAccount.ownerName !== null) {
          $("[data-drupal-selector='edit-bank-account-account-number-owner-name']")
              .val(selectedAccount.ownerName);
        }
        if (selectedAccount.ownerSsn !== null) {
          $("[data-drupal-selector='edit-bank-account-account-number-ssn']")
              .val(selectedAccount.ownerSsn);
        }


      });
      $("#edit-community-address-community-address-select").change(function () {
        const selectedDelta = $(this).val()

        const selectedAddress = drupalSettings.grants_handler.grantsProfile.addresses.filter(address => address.address_id === selectedDelta)[0];

        $("[data-drupal-selector='edit-community-address-community-street']").val(selectedAddress.street)
        $("[data-drupal-selector='edit-community-address-community-post-code']").val(selectedAddress.postCode)
        $("[data-drupal-selector='edit-community-address-community-city']").val(selectedAddress.city)
        $("[data-drupal-selector='edit-community-address-community-country']").val(selectedAddress.country)
      });
      $(".community-officials-select").change(function () {
        // get selection
        const selectedItem = $(this).val()
        // parse element delta.
        // there must be better way but can't figure out
        let elementDelta = $(this).attr('data-drupal-selector')
        elementDelta = elementDelta.replace('edit-community-officials-items-', '')
        elementDelta = elementDelta.replace('-item-community-officials-select', '')
        // get selected official
        const selectedOfficial = drupalSettings.grants_handler.grantsProfile.officials[selectedItem];

        // @codingStandardsIgnoreStart
        // set up data selectors for delta
        const nameTarget = `[data-drupal-selector='edit-community-officials-items-${elementDelta}-item-name']`
        const roleTarget = `[data-drupal-selector='edit-community-officials-items-${elementDelta}-item-role']`
        const emailTarget = `[data-drupal-selector='edit-community-officials-items-${elementDelta}-item-email']`
        const phoneTarget = `[data-drupal-selector='edit-community-officials-items-${elementDelta}-item-phone']`
        // @codingStandardsIgnoreEnd

        // set values
        $(nameTarget).val(selectedOfficial.name)
        $(roleTarget).val(selectedOfficial.role)
        $(emailTarget).val(selectedOfficial.email)
        $(phoneTarget).val(selectedOfficial.phone)
      });

      // Managed file #states handling is a bit wonky,
      // so we need to manually handle checkbox disables in the
      // composite element
      const checkBoxStateFn = function () {
        if (this.checked) {
          $(this).prop('disabled', false);
        }
      }

      $('[data-webform-composite-attachment-inOtherFile]').once('disable-state-handling').on('change', checkBoxStateFn);
      $('[data-webform-composite-attachment-isDeliveredLater]').once('disable-state-handling').on('change', checkBoxStateFn);
      $('.js-form-type-managed-file ').once('filefield-state-handling').each(function () {

        const parent = $(this).parents('.fieldset-wrapper').first();
        const box1 = $(parent).find('[data-webform-composite-attachment-inOtherFile]');
        const box2 = $(parent).find('[data-webform-composite-attachment-isDeliveredLater]');
        const attachment = $(this).find('input');
        const attachmentValue = $(attachment).val();

        // Notice that we might have attachmentName field instead of managedFile
        // (If you need to change logic here).
        if (attachmentValue && attachmentValue !== '') {
          box1.prop('disabled', true)
          box2.prop('disabled', true)
        }
        else if (attachment) {
          box1.prop('disabled', false)
          box2.prop('disabled', false)
        }
      });
    }
  };
})(jQuery, Drupal, drupalSettings);
