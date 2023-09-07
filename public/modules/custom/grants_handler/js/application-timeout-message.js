(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.applicationTimeoutMessage = {
    attach: function(context, settings) {

      const formTimestamp = settings.grants_handler.settings.formTimestamp;
      let intervalId;

      if (typeof formTimestamp !== 'undefined') {
        checkTimeout();
        intervalId = setInterval(checkTimeout, 10000);
      }

      function checkTimeout() {
        const currentTime = new Date().toLocaleString('en-US', {timeZone: 'Europe/Helsinki'});
        const currentTimestamp = Math.floor(new Date(currentTime).getTime() / 1000);

        if (currentTimestamp > formTimestamp) {
          const element = document.querySelector('.application-timeout-message');
          element.classList.add('slide-in');
          clearInterval(intervalId);
        }
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
