(function (Drupal) {
  Drupal.behaviors.deleteDraftDialogForm = {
    attach: function (context, settings) {
      /**
       * Fetches the CSRF token from Drupal.
       *
       * @returns {Promise<string>} - The CSRF token
       */
      const fetchToken = async () => {
        try {
          const response = await fetch('/session/token');
          token = response?.ok ? await response.text() : '';
        } catch (error) {
          console.error(error);
        }

        return token || '';
      }

      /**
       * Deletes the draft application.
       *
       * @param {HTMLElement} button - The button element that was clicked
       * @returns {Promise<string>} - The URL of the redirect
       */
      const deleteDraft = async (button) => {
        const token = await fetchToken();
        if (!token) {
          return '';
        }

        try {
          const response = await fetch(button.getAttribute('href'), {
            method: 'POST',
            headers: {
              'X-CSRF-Token': token,
            },
          });
          const data = await response.json();
          return data.redirectUrl;
        } catch (error) {
          console.error(error);
          return '';
        }
      }

      /**
       * Display an error message.
       *
       * @param {string} message - The error message
       */
      const displayErrorMessage = (message) => {
        const messages = new Drupal.Message();
        messages.add(message, {type: 'error'});
        document.querySelector('[data-drupal-messages]').scrollIntoView({ behavior: 'smooth' });
      }

      const deleteButtons = document.querySelectorAll('.js-delete-draft-link');
      deleteButtons.forEach(button => {
        button.addEventListener('click', function (event) {
          event.preventDefault();

          Drupal.dialogFunctions.createDialog({
            dialogContent: Drupal.t('Are you sure you want to delete the application?', {}, { context: 'grants_handler_dialog' }),
            dialogVariant: 'danger',
            actionButtonText: Drupal.t('Delete', {}, { context: 'grants_handler_dialog' }),
            actionButtonIcon: 'trash',
            backButtonText: Drupal.t('Cancel', {}, { context: 'grants_handler_dialog' }),
            closeButtonText: Drupal.t('Close', {}, { context: 'grants_handler' }),
            actionButtonCallback: async () => {
              // Delete the draft and redirect.
              const url = await deleteDraft(button);
              if (url) {
                window.location.href = url;
              } else {
                return (dialog, dialogFocusTrap) => {
                  displayErrorMessage(Drupal.t('Deleting application failed. Please try again or contact support.', {}, { context: 'grants_handler_dialog' }));
                  dialogFocusTrap.deactivate();
                  Drupal.dialogFunctions.removeDialog(dialog);
                }
              }
            },
            actionButtonCallbackIsAsync: true,
            dialogTitle: Drupal.t('Delete application', {}, { context: 'grants_handler_dialog' }),
            dialogIcon: 'info',
            customSelector: 'dialog--danger',
            enforceWait: true,
          })
        });
      });
    },
  };
})(Drupal);
