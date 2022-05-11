(function ($, Drupal, drupalSettings) {
    Drupal.behaviors.GrantsHandlerBehavior = {
        attach: function (context, settings) {
            $("#edit-account-number-select").change(function () {
                $("[data-drupal-selector='edit-account-number']").val($(this).val())
            });
            $("#edit-community-address-community-address-select").change(function () {
                const selectedDelta = $(this).val()
                const selectedAddress = drupalSettings.grants_handler.grantsProfile.addresses[selectedDelta];
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

                // set up data selectors for delta
                const nameTarget = `[data - drupal - selector = 'edit-community-officials-items-${elementDelta}-item-name']`
                const roleTarget = `[data - drupal - selector = 'edit-community-officials-items-${elementDelta}-item-role'`
                const emailTarget = `[data - drupal - selector = 'edit-community-officials-items-${elementDelta}-item-email'`
                const phoneTarget = `[data - drupal - selector = 'edit-community-officials-items-${elementDelta}-item-phone'`

                // set values
                $(nameTarget).val(selectedOfficial.name)
                $(roleTarget).val(selectedOfficial.role)
                $(emailTarget).val(selectedOfficial.email)
                $(phoneTarget).val(selectedOfficial.phone)

                console.log(selectedOfficial, nameTarget)
            });
        }
    };
})(jQuery, Drupal, drupalSettings);
