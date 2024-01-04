(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.pendingBankAccountConfirmation = {

    /**
     * Attach the behavior.
     *
     * @param context
     *   The context.
     * @param settings
     *   Drupal settings.
     */
    attach: function (context, settings) {
      const selectedAccount = settings.grants_attachments.settings.selectedAccount;
      if (typeof selectedAccount !== 'undefined') {
        this.displayPendingBankAccountConfirmationMessage(selectedAccount);
      }
    },

    /**
     * The displayPendingBankAccountConfirmationMessage function.
     *
     * Desc...
     *
     * @param selectedAccount
     *   The selected bank account.
     */
    displayPendingBankAccountConfirmationMessage: function(selectedAccount) {
      if (!document.body.classList.contains('webform-submission-data-preview-page')) return;

      // Create the list item.
      const newListItem = document.createElement('li');
      const newListItemDesc = Drupal.t("Confirmation file for @account", {'@account': selectedAccount});
      const newListItemStatus = Drupal.t("Upload pending")
      newListItem.innerHTML = newListItemDesc + "<br>" + newListItemStatus;

      // Find or create the "Other attachments" listing.
      const otherAttachments = document.querySelector('.form-item-muu-liite');
      const otherAttachmentsList = otherAttachments.querySelector('ul') || document.createElement('ul');

      // Append the new item.
      otherAttachmentsList.appendChild(newListItem);
      otherAttachments.appendChild(otherAttachmentsList);
    },

  };

})(jQuery, Drupal, drupalSettings);
