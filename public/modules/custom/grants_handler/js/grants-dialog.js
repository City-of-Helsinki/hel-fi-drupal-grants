(function (Drupal) {
  Drupal.dialogFunctions = {

    /**
     * Creates a dialog and appends it to the body.
     *
     * @param {string} dialogContent - The title displayed at the top of the
     *   dialog.
     * @param {string} actionButtonText - The text for the "leave" button.
     * @param {string} backButtonText - The text for the "back" button.
     * @param {string} closeButtonText - The text for the "close" button that
     *   closes the dialog.
     * @param {Function} actionButtonCallback - The function to execute when
     *   the "action" button is clicked.
     */
    createDialog: (dialogContent, actionButtonText, backButtonText, closeButtonText, actionButtonCallback = null) => {
      const dialogTitle = Drupal.t('Attention', {}, { context: 'grants_handler' });
      const actionButtonHTML = actionButtonText && `<button class="dialog__action-button" id="helfi-dialog__action-button" data-hds-component="button" data-hds-variant="primary">${actionButtonText}</button>`;
      const backButtonHTML = backButtonText && `<button class="dialog__action-button" id="helfi-dialog__back-button" data-hds-component="button" data-hds-variant="secondary">${backButtonText}</button>`;
      const closeButtonHTML = closeButtonText && `<button class="dialog__close-button" id="helfi-dialog__close-button"><span class="is-hidden">${closeButtonText}</span></button>`;

      const dialogHTML = `
        <div class="dialog__container" id="helfi-dialog__container">
          <div class="dialog__overlay"></div>
          <dialog class="dialog" id="helfi-dialog" aria-labelledby="helfi-dialog__title" aria-modal="true">
            <div class="dialog__header">
              ${closeButtonHTML}
              <h2 class="dialog__title" id="helfi-dialog__title">${dialogTitle}</h2>
            </div>
            <div class="dialog__content">
             ${dialogContent}
            </div>
            <div class="dialog__actions">
              ${actionButtonHTML}
              ${backButtonHTML}
            </div>
          </dialog>
        </div>
      `;

      // TODO: Surveys use very similar javascript dialog implementation.
      // This and the survey implementation could possibly be merged with some
      // refactoring.

      // Add the dialog to the body
      document.body.insertAdjacentHTML('beforeend', dialogHTML);

      Drupal.dialogFunctions.setBodyPaddingRight(true);

      Drupal.dialogFunctions.toggleNoScroll(true);

      const actionButton = document.getElementById('helfi-dialog__action-button');
      const backButton = document.getElementById('helfi-dialog__back-button');
      const closeButton = document.getElementById('helfi-dialog__close-button');
      const dialog = document.getElementById('helfi-dialog__container');
      const dialogFocusTrap = window.focusTrap.createFocusTrap('#helfi-dialog__container', {
        initialFocus: () => '#helfi-dialog__title'
      });

      // Activate the focus trap so that the user needs to react to the dialog.
      dialogFocusTrap.activate();

      // Add click event listener to action button
      if (actionButtonCallback && actionButtonText) {
        actionButton.addEventListener('click', actionButtonCallback);
      }

      // Add click event listener to back button
      backButton.addEventListener('click', () => {
        dialogFocusTrap.deactivate();
        Drupal.dialogFunctions.removeDialog(dialog);
      });

      // Add click event listener to close button
      closeButton.addEventListener('click', () => {
        dialogFocusTrap.deactivate();
        Drupal.dialogFunctions.removeDialog(dialog);
      });

      // Add event listener to ESC button to remove the dialog
      document.body.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          Drupal.dialogFunctions.removeDialog(dialog);
        }
      });
    },

    setBodyPaddingRight: (enable) => {
      if (enable) {
        document.body.style.paddingRight = `${
          window.innerWidth - document.documentElement.clientWidth
        }px`;
      }
      else {
        document.body.style.removeProperty('padding-right');
      }
    },

    toggleNoScroll: (enable) => {
      const root = document.documentElement;
      root.classList.toggle('noscroll', enable);
    },

    removeDialog: (dialog) => {
      dialog.remove();
      Drupal.dialogFunctions.toggleNoScroll(false);
      Drupal.dialogFunctions.setBodyPaddingRight(false);
    }
  }
})(Drupal);
