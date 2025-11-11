(function (Drupal) {
  Drupal.dialogFunctions = {

    /**
     * Creates a dialog and appends it to the body.
     *
     * @todo: The dialog implementation will be refactored in UHF-12503.
     *
     * @param {string} dialogContent - The title displayed at the top of the
     *   dialog.
     * @param {string} dialogVariant - The variant for the dialog.
     * @param {string} dialogIcon - The icon to display in the dialog header.
     * @param {string} actionButtonText - The text for the "leave" button.
     * @param {string} actionButtonIcon - The icon for the "action" button.
     * @param {string} backButtonText - The text for the "back" button.
     * @param {string} closeButtonText - The text for the "close" button that
     *   closes the dialog.
     * @param {string} dialogTitle - The title for the dialog.
     *  If we want to override the default title
     * @param {Function} actionButtonCallback - The function to execute when
     *   the "action" button is clicked.
     * @param {Function} actionButtonCallbackIsAsync - Whether the action 
     *  button callback is asynchronous.
     * @param {Function} closeButtonCallback
     *  The function to execute when the "close" button is clicked.
     * @param {Function} backButtonCallback
     *  The function to execute when the "back" button is clicked.
     * @param {Function} escapeButtonCallback
     *  The function to execute when the "escape" button is clicked.
     * @param {string} customSelector
     *  If we want to add a custom class to the dialog container
     * @param {boolean} enforceWait - Whether to enforce waiting when the
     *  "action" button is clicked. This will show a throbber and disable all
     *  buttons in the dialog.
     */
    createDialog: ({
      dialogContent,
      dialogVariant = '',
      dialogIcon = '',
      actionButtonText,
      actionButtonIcon = '',
      backButtonText,
      closeButtonText,
      dialogTitle = Drupal.t('Attention', {}, { context: 'grants_handler' }),
      actionButtonCallback = null,
      actionButtonCallbackIsAsync = false,
      closeButtonCallback = null,
      backButtonCallback = null,
      escapeButtonCallback = null,
      customSelector = '',
      enforceWait = false,
    }) => {
      const actionButtonHTML = actionButtonText &&
        `<button
          class="dialog__action-button"
          id="helfi-dialog__action-button"
          data-hds-component="button"
          data-hds-variant="${dialogVariant || 'primary'}"
          ${actionButtonIcon ? `data-hds-icon-start="${actionButtonIcon}"` : ''}
        >
          ${actionButtonText}
        </button>`;
      const backButtonHTML = backButtonText && `<button class="dialog__action-button" id="helfi-dialog__back-button" data-hds-component="button" data-hds-variant="secondary">${backButtonText}</button>`;
      const closeButtonHTML = closeButtonText && `<button class="dialog__close-button" id="helfi-dialog__close-button"><span class="is-hidden">${closeButtonText}</span></button>`;
      const dialogIconHTML = dialogIcon && `<span class="hel-icon hel-icon--${dialogIcon}" role="img" aria-hidden="true"></span>&nbsp;`;

      const dialogHTML = `
    <div class="dialog__container" id="helfi-dialog__container">
      <div class="dialog__overlay"></div>
      <dialog
        class="dialog${dialogVariant ? ` dialog--${dialogVariant}` : ''}${customSelector ? ` ${customSelector}` : ''}"
        id="helfi-dialog"
        aria-labelledby="helfi-dialog__title"
        aria-modal="true"
      >
        <div class="dialog__header">
          ${closeButtonHTML}
          <h2 class="dialog__title" id="helfi-dialog__title">${dialogIconHTML}${dialogTitle}</h2>
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
        initialFocus: () => '#helfi-dialog__title',
      });
      let waitingForAction = false;

      // Activate the focus trap so that the user needs to react to the dialog.
      dialogFocusTrap.activate();

      // Add click event listener to action button
      if (actionButtonCallback && actionButtonText) {
        actionButton.addEventListener('click', () => {
          Drupal.dialogFunctions.preActionButtonCallback(dialog, enforceWait);
          if (actionButtonCallbackIsAsync) {
            actionButtonCallback(dialog).then((callbackResult = null) => {
              Drupal.dialogFunctions.postActionButtonCallback(dialog, dialogFocusTrap, callbackResult);
            });
          } else {
            const callbackResult = actionButtonCallback(dialog);
            Drupal.dialogFunctions.postActionButtonCallback(dialog, dialogFocusTrap, callbackResult);
          }
        });
      }

      // Add click event listener to back button
      backButton.addEventListener('click', () => {
        if (waitingForAction) {
          return;
        }

        dialogFocusTrap.deactivate();
        // If we have a callback, execute it.
        if (backButtonCallback) {
          backButtonCallback();
        }
        Drupal.dialogFunctions.removeDialog(dialog);
      });

      // Add click event listener to close button
      closeButton.addEventListener('click', () => {
        if (waitingForAction) {
          return;
        }

        dialogFocusTrap.deactivate();
        // If we have a callback, execute it.
        if (closeButtonCallback) {
          closeButtonCallback();
        }
        Drupal.dialogFunctions.removeDialog(dialog);
      });

      // Add event listener to ESC button to remove the dialog
      document.body.addEventListener('keydown', function (event) {
        if (waitingForAction) {
          return;
        }

        if (event.key === 'Escape') {
          // If we have a escapeButtonCallback, execute it also when pressing escape.
          if (escapeButtonCallback) {
            escapeButtonCallback();
          }
          Drupal.dialogFunctions.removeDialog(dialog);
        }
      });
    },

    preActionButtonCallback: (dialog, enforceWait) => {
      if (enforceWait) {
        waitingForAction = true;
        Drupal.dialogFunctions.setThrobber(dialog);
      }
    },

    postActionButtonCallback: (dialog, dialogFocusTrap, callbackResult) => {
      if (typeof callbackResult === 'function') {
        callbackResult(dialog, dialogFocusTrap);
      }
    },

    setBodyPaddingRight: (enable) => {
      if (enable) {
        document.body.style.paddingRight = `${
          window.innerWidth - document.documentElement.clientWidth
        }px`;
      } else {
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
    },

    setThrobber: (dialog) => {
      dialog.querySelectorAll('button').forEach(button => {
        button.setAttribute('disabled', true);
      });

      if (Drupal.theme?.ajaxProgressThrobber) {
        dialog.insertAdjacentHTML('beforeend', '<div id="js-dialog-progress-throbber">' + Drupal.theme.ajaxProgressThrobber() + '</div>');
      }
    },

    removeThrobber: (dialog) => {
      dialog.querySelectorAll('button').forEach(button => {
        button.removeAttribute('disabled');
      });

      if (Drupal.theme?.ajaxProgressThrobber) {
        dialog.querySelector('#js-dialog-progress-throbber').remove();
      }
    },
  };
})(Drupal);
