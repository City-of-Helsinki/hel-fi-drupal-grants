(function (Drupal) {
  Drupal.behaviors.copyApplicationModalForm = {
    attach: function (context, settings) {
      const triggerButton = document.getElementById('copy-application-button');

      triggerButton.addEventListener('click', function (event) {

        event.preventDefault();

        const htmlContent = drupalSettings.grants_handler.htmlContent;
        const copyUrl = drupalSettings.grants_handler.copyUrl;

        Drupal.dialogFunctions.createDialog(
          htmlContent,
          Drupal.t('Copy application', [], { context: 'grants_handler' }),
          Drupal.t('Cancel'),
          Drupal.t('Close'),
          function () {
            // Redirect to a new URL
            window.location.href = copyUrl;
            /*
            We probably should handle the whole copy process here, but for
            now we just redirect to the copy URL.

            Much better UX would be to show spinner here while copying
            application and then redirect when it's ready

             */
          },
          Drupal.t('Copy application', [], { context: 'grants_handler' }),
          'application-copy-dialog'
        )
      });
    },
  };
})(Drupal);
