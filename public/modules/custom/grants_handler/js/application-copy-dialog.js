(function (Drupal) {
  Drupal.behaviors.copyApplicationModalForm = {
    attach: function (context, settings) {
      const triggerButton = document.getElementById('copy-application-button');

      triggerButton.addEventListener('click', function (event) {

        event.preventDefault();

        const htmlContent = drupalSettings.grants_handler.htmlContent;
        const copyUrl = drupalSettings.grants_handler.copyUrl;

        Drupal.dialogFunctions.createDialog({
          dialogContent: htmlContent,
          actionButtonText: Drupal.t('Copy application'),
          backButtonText: Drupal.t('Close', {}, { context: 'grants_handler' }),
          closeButtonText: Drupal.t('Close', {}, { context: 'grants_handler' }),
          actionButtonCallback: () => {
            // Redirect to a new URL
            window.location.href = copyUrl;
            /*
            We probably should handle the whole copy process here, but for
            now we just redirect to the copy URL.

            Much better UX would be to show spinner here while copying
            application and then redirect when it's ready

             */
          },
          dialogTitle: Drupal.t('Copy application'),
          customSelector: 'application-copy-dialog'
        })
      });
    },
  };
})(Drupal);
