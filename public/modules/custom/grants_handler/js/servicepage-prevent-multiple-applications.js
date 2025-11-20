// On service page, when user clicks one of the links, we prevent any further clicks
// to prevent the creation of multiple applications.
(function (Drupal) {
  Drupal.behaviors.PreventMultipleApplications = {
    attach: function (context, settings) {
      // Only target the correct element.
      const element = document.getElementById('block-servicepageauthblock');
      if (!element) {
        return;
      }

      // Add rage-click prevention on all primary-button links.
      const buttons = element.querySelectorAll('.hds-button--primary');
      buttons.forEach(function (button) {
        button.addEventListener('click', function () {
          button.classList.add('disabled');
        })
      })

    }
  }
})(Drupal);
