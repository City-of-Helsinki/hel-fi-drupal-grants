(function ($, Drupal, drupalSettings, once) {
  Drupal.behaviors.GrantsHandlerBehavior = {
    attach: function (context, settings) {

      // Let's start by calling the translation lines that are used in overrides in the Form.
      Drupal.t('Close', {}, {context: 'grants_handler'});

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

        if (selectedAddress) {
          $("[data-drupal-selector='edit-community-address-community-street']").val(selectedAddress.street);
          $("[data-drupal-selector='edit-community-address-community-post-code']").val(selectedAddress.postCode)
          $("[data-drupal-selector='edit-community-address-community-city']").val(selectedAddress.city)
          $("[data-drupal-selector='edit-community-address-community-country']").val(selectedAddress.country)
        }
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
        let selectedOfficial = [];
        if (selectedItem === '') {
          selectedOfficial.name = null
          selectedOfficial.role = null
          selectedOfficial.roletext = null
          selectedOfficial.email = null
          selectedOfficial.phone = null
        } else {
          selectedOfficial = drupalSettings.grants_handler.grantsProfile.officials[selectedItem];
        }



        // @codingStandardsIgnoreStart
        // set up data selectors for delta
        const nameTarget = `[data-drupal-selector='edit-community-officials-items-${elementDelta}-item-name']`
        const roleTarget = `[data-drupal-selector='edit-community-officials-items-${elementDelta}-item-role']`
        const roletextTarget = `[data-drupal-selector='edit-community-officials-items-${elementDelta}-item-roletext']`
        const emailTarget = `[data-drupal-selector='edit-community-officials-items-${elementDelta}-item-email']`
        const phoneTarget = `[data-drupal-selector='edit-community-officials-items-${elementDelta}-item-phone']`
        // @codingStandardsIgnoreEnd

        // set values
        $(nameTarget).val(selectedOfficial.name)
        $(roleTarget).val(selectedOfficial.role)
        $(roletextTarget).val(drupalSettings.grants_handler.officialsArray[selectedOfficial.role])
        $(emailTarget).val(selectedOfficial.email)
        $(phoneTarget).val(selectedOfficial.phone)
        if (selectedItem === '') {
          $(`.community_officials_wrapper [data-drupal-selector="edit-community-officials-items-${elementDelta}"] .webform-readonly`).hide();
        } else {
          $(`.community_officials_wrapper [data-drupal-selector="edit-community-officials-items-${elementDelta}"] .webform-readonly`).show();
        }


      });

      $(".community-officials-select").trigger('change');

      $(once('disable-state-handling', '[data-webform-composite-attachment-inOtherFile]')).on('change', function() {
        const parent = $(this).parents('.fieldset-wrapper').first();
        let box1 = $(parent).find('[data-webform-composite-attachment-inOtherFile]');
        setTimeout(function(){
          $(box1).prop('disabled', false);
        },100);
      });
      $(once('disable-state-handling', '[data-webform-composite-attachment-isDeliveredLater]')).on('change', function() {
        const parent = $(this).parents('.fieldset-wrapper').first();
        let box2 = $(parent).find('[data-webform-composite-attachment-isDeliveredLater]');
        setTimeout(function(){
          $(box2).prop('disabled', false);
        },100);
      });
      $(once('filefield-state-handling', '.js-form-type-managed-file ')).each(function () {

        const parent = $(this).parents('.fieldset-wrapper').first();
        let box1 = $(parent).find('[data-webform-composite-attachment-inOtherFile]');
        let box2 = $(parent).find('[data-webform-composite-attachment-isDeliveredLater]');
        const attachment = $(this).find('input');
        const attachmentValue = $(attachment).val();
        const checkBoxesAreEqual = box1.prop('checked') === box2.prop('checked');

        // Notice that we might have attachmentName field instead of managedFile
        // (If you need to change logic here).
        if (attachmentValue && attachmentValue !== '') {
          setTimeout(function(){
            box1.prop('disabled', true)
            box2.prop('disabled', true)
          },100);

        }
        else if (attachment && checkBoxesAreEqual) {
          setTimeout(function(){
            box1.prop('disabled', false)
            box2.prop('disabled', false)
          },100);

        }
        else if (!checkBoxesAreEqual) {
          // If we are returning to edit a draft, make sure
          // we disable the other box.
          setTimeout(function(){
            box1.prop('disabled', box2.prop('checked') === true)
            box2.prop('disabled', box1.prop('checked') === true)
          },100);

        }
      });

      // Target buttons, custom link-like elements, and dynamically added links
      const elementsToDisable = [
        'button', // All buttons
        '[role="link"]', // Custom elements acting as links
        'a', // All anchor links (newly added ones too)
        '.webform-button--draft',
        '.webform-button--preview',
        '.webform-button--previous',
      ];

      // Function to disable elements
      function disableElements() {
        $(elementsToDisable.join(',')).each(function() {
          const $el = $(this);
          if ($el.is('button, input')) {
            $el.prop('disabled', true);
          }
          if ($el.is('[role="link"]')) {
            $el.addClass('disabled-link').attr('tabindex', '-1').on('click.disable', function(e) {
              e.preventDefault(); // Disable click behavior
            });
          }
          if ($el.is('a')) {
            $el.addClass('disabled-link').on('click.disable', function(e) {
              e.preventDefault(); // Disable anchor link clicks
            });
          }
        });
      }

      // Function to re-enable elements
      function enableElements() {
        $(elementsToDisable.join(',')).each(function() {
          const $el = $(this);
          if ($el.is('button, input')) {
            $el.prop('disabled', false);
          }
          if ($el.is('[role="link"]')) {
            $el.removeClass('disabled-link').attr('tabindex', '0').off('click.disable'); // Re-enable click and tabbing
          }
          if ($el.is('a')) {
            $el.removeClass('disabled-link').off('click.disable'); // Re-enable anchor link clicks
          }
        });
      }

      $(document).ajaxStart(function () {
        // Disable elements when AJAX starts
        disableElements();
        // Optionally, add a class to the body to show the page is busy
        $('body').addClass('ajax-loading');
      });

      $(document).ajaxComplete(function () {
        // Disable newly added links after AJAX completes
        disableElements();
        // Enable elements once AJAX is done (if you want to re-enable them after processing)
        enableElements();
        // Remove the ajax-loading class
        $('body').removeClass('ajax-loading');
      });

    }
  };
})(jQuery, Drupal, drupalSettings, once);
