(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.applicationTimeoutMessage = {

    /**
     * Attach the behavior.
     *
     * @param context
     *   The context.
     * @param settings
     *   Drupal settings.
     */
    attach: function(context, settings) {
      const applicationCloseTimestamp = settings.grants_handler.settings.applicationCloseTimestamp;
      if (typeof applicationCloseTimestamp !== 'undefined') {
        this.checkTimeout(applicationCloseTimestamp);
        this.closeButton();
      }
    },

    /**
     * The checkTimeout function.
     *
     * This function loops the hasTimeoutPassed() interval
     * in order to check if the current time has passed an
     * applications closing time. If the time is passed,
     * a warning message is triggered.
     *
     * @param applicationCloseTimestamp
     *   The applications closing time timestamp.
     */
    checkTimeout: function (applicationCloseTimestamp) {
      let intervalId;
      intervalId = setInterval(hasTimeoutPassed, 10000);

      function hasTimeoutPassed() {
        const currentTime = new Date().toLocaleString('en-US', {timeZone: 'Europe/Helsinki'});
        const currentTimestamp = Math.floor(new Date(currentTime).getTime() / 1000);

        if (currentTimestamp > applicationCloseTimestamp) {
          const element = document.querySelector('.application-timeout-message');
          element.classList.add('slide-in');
          clearInterval(intervalId);
        }
      }
    },

    /**
     * The closeButton function.
     *
     * This function adds an event listener to the close
     * button of the timeout message. When the button is
     * clicked, the message is hidden.
     */
    closeButton: function () {
      const closeButton = document.querySelector('.close-application-timeout-message');
      closeButton.addEventListener('click', function () {
        const messageContainer = this.closest('.application-timeout-message');
        messageContainer.style.display = 'none';
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
