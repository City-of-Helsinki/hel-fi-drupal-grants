(($, Drupal) => {

  /**
   * Unsaved changes.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for unsaved changes.
   */
  Drupal.behaviors.profileFormUnsaved = {
    attach: function (context) {
      const initial_name = $('#edit-companynamewrapper-companyname').val();
      let is_element_click = false;
      $('form').submit(function() {
        window.onbeforeunload = null
        is_element_click = true;
      });

      once('profile_dialog', 'a').forEach((element) => {
        element.addEventListener('click', (event) => {
          is_element_click = true;
          const current_name = $('#edit-companynamewrapper-companyname').val();
          let unset_name = false
          if (current_name === '') {
            unset_name = true
          }

          let containingElement = document.querySelector('form');
          if ((unset_name) && !containingElement.contains(event.target)) {
            event.preventDefault();
            return Drupal.dialogFunctions.createDialog({
              dialogContent: Drupal.t('You need to have a name for your unregistered community or group. Please add a name and save or cancel them.', {}, { context: 'grants_profile' }),
              actionButtonText: '',
              backButtonText: Drupal.t('Back to profile', {}, { context: 'grants_profile' }),
              closeButtonText: Drupal.t('Close', {}, { context: 'grants_profile' }),
            });
          } else if ((current_name !== initial_name) && !containingElement.contains(event.target)) {
            event.preventDefault();
            return Drupal.dialogFunctions.createDialog({
              dialogContent: Drupal.t('You have unsaved changes in your profile. Please save or cancel them.', {}, { context: 'grants_profile' }),
              actionButtonText: '',
              backButtonText: Drupal.t('Back to profile', {}, { context: 'grants_profile' }),
              closeButtonText: Drupal.t('Close', {}, { context: 'grants_profile' }),
            });
          } else if (($('[data-drupal-selector="edit-isnewprofile"]').val() === 'initialSave') && !containingElement.contains(event.target)) {
            event.preventDefault();
            return Drupal.dialogFunctions.createDialog({
              dialogContent: Drupal.t('You have not saved your profile. Please save your profile before leaving the form.', {}, { context: 'grants_profile' }),
              actionButtonText: '',
              backButtonText: Drupal.t('Back to profile', {}, { context: 'grants_profile' }),
              closeButtonText: Drupal.t('Close', {}, { context: 'grants_profile' }),
            });
          }
          is_element_click = false;

        });
      });

      window.onbeforeunload = function(event) {
        // Cancel the event as stated by the standard.
        // Chrome requires returnValue to be set.
        let containingElement = document.querySelector('form');

        const current_name = $('#edit-companynamewrapper-companyname').val();
        let unset_name = false;

        if (current_name === '') {
          unset_name = true;
        }

        let message = '';

        if (unset_name && !containingElement.contains(event.target) && !is_element_click) {
          message = Drupal.t('You need to have a name for your unregistered community or group. Are you sure you want to leave the form?');
        }
        if ((current_name !== initial_name) && !containingElement.contains(event.target) && !is_element_click) {
          message = Drupal.t('You have unsaved changes in your profile. Are you sure you want to leave the form?');
        }
        if (($('[data-drupal-selector="edit-isnewprofile"]').val() === 'initialSave') && !containingElement.contains(event.target) && !is_element_click) {
          message = Drupal.t('You have not saved your profile. Are you sure you want to leave the form?');
        }

        if (message) {
          event.preventDefault();
          // For modern browsers.
          return message;
        }
        is_element_click = false;
      };
    }
  }
})(jQuery, Drupal);
