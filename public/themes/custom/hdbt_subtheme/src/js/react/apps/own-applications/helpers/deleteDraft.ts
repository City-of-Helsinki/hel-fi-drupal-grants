const fetchToken = async (): Promise<string> => {
  try {
    const response = await fetch('/session/token');
    return response.ok ? await response.text() : '';
  } catch (error) {
    console.error(error);
    return '';
  }
};

/**
 * Deletes a draft application.
 *
 * @param url - The delete URL of the draft.
 * @return The URL to redirect to, or an empty string if deleting failed.
 */
const deleteDraft = async (url: string): Promise<string> => {
  const token = await fetchToken();
  if (!token) {
    return '';
  }

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-Token': token },
    });
    const data = await response.json();
    return data.redirectUrl ?? '';
  } catch (error) {
    console.error(error);
    return '';
  }
};

const displayErrorMessage = (message: string) => {
  const messages = new Drupal.Message();
  messages.add(message, { type: 'error' });
  document.querySelector('[data-drupal-messages]')?.scrollIntoView({ behavior: 'smooth' });
};

/**
 * Opens the delete draft confirmation dialog.
 *
 * Mirrors grants_handler/js/application-delete-draft-dialog.js.
 *
 * @param url - The delete URL of the draft.
 */
export const confirmDeleteDraft = (url: string) => {
  Drupal.dialogFunctions.createDialog({
    dialogContent: Drupal.t(
      'Are you sure you want to delete the application?',
      {},
      { context: 'grants_handler_dialog' },
    ),
    dialogVariant: 'danger',
    actionButtonText: Drupal.t('Delete', {}, { context: 'grants_handler_dialog' }),
    actionButtonIcon: 'trash',
    backButtonText: Drupal.t('Cancel', {}, { context: 'grants_handler_dialog' }),
    closeButtonText: Drupal.t('Close', {}, { context: 'grants_handler' }),
    actionButtonCallback: async () => {
      const redirectUrl = await deleteDraft(url);
      if (redirectUrl) {
        window.location.href = redirectUrl;
        return undefined;
      }

      return (dialog: HTMLElement, dialogFocusTrap: { deactivate: () => void }) => {
        displayErrorMessage(
          Drupal.t(
            'Deleting application failed. Please try again or contact support.',
            {},
            { context: 'grants_handler_dialog' },
          ),
        );
        dialogFocusTrap.deactivate();
        Drupal.dialogFunctions.removeDialog(dialog);
      };
    },
    actionButtonCallbackIsAsync: true,
    dialogTitle: Drupal.t('Delete application', {}, { context: 'grants_handler_dialog' }),
    dialogIcon: 'alert-circle',
    customSelector: 'dialog--danger',
    enforceWait: true,
  });
};
