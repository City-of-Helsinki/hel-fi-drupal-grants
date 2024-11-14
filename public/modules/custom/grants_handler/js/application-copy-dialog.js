(function (Drupal) {
  Drupal.behaviors.copyApplicationModalForm = {
    attach: function (context, settings) {
      const triggerButton = document.getElementById('copy-application-button');

      triggerButton.addEventListener('click', function (event) {

        console.log('testi123');

        event.preventDefault();

        // https://hel-fi-drupal-grant-applications.docker.so/fi/hakemus/LOCALYRTTI12-063-0000867/kopioi/ajax

        fetch('/fi/hakemus/LOCALYRTTI12-063-0000867/kopioi/ajax')
          .then(response => response.text())
          .then(dialogContent => {
            console.log('DIALOG CONTENT:', dialogContent);
            Drupal.dialogFunctions.createDialog(
              dialogContent,
              Drupal.t('Copy Application'),
              Drupal.t('Cancel'),
              Drupal.t('Close'),
              function () {
                const form = document.getElementById('copy-application-form');
                if (form.checkValidity()) {
                  form.submit(); // Submit the form like a normal form submission
                } else {
                  form.reportValidity();
                }
              },
            );
          })
          .catch(error => {
            console.error('Error fetching form:', error);
          });
      });
    },
  };
})(Drupal);
