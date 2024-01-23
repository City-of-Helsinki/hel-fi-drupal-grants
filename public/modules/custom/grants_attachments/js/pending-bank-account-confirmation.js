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
      const selectedAccount = settings.grants_attachments.selectedAccountNumber;
      if (typeof selectedAccount !== 'undefined') {
        this.displayPendingBankAccountConfirmationMessage(selectedAccount);
      }
    },

    /**
     * The displayPendingBankAccountConfirmationMessage function.
     *
     * This function displays an "Upload pending" message under the
     * "Other attachments" section on the confirmation page of an application.
     * The message is displayed in the scenario where a bank account number
     * has been selected, but the accounts confirmation file has not been
     * uploaded to ATV.
     *
     * @param selectedAccountNumber
     *   The selected account number.
     */
    displayPendingBankAccountConfirmationMessage: function(selectedAccountNumber) {
      // Make sure we are on the "preview" page.
      if (!document.body.classList.contains('webform-submission-data-preview-page')) return;

      // Create the list item.
      const newListItem = document.createElement('li');
      const newListItemDesc = Drupal.t("Confirmation for account @accountNumber", {'@accountNumber': selectedAccountNumber}, {context: "grants_attachments"});
      const newListItemStatus = Drupal.t("Upload pending / File missing", {}, {context: "grants_attachments"});
      newListItem.innerHTML = newListItemDesc + "<br>" + newListItemStatus;

      // Find the section for "Other attachments" and clear it of any text (usually a dash).
      const otherAttachments = document.querySelector('.form-item-muu-liite');
      if (!otherAttachments) return;
      otherAttachments.childNodes.forEach(c => c.nodeType === Node.TEXT_NODE && c.remove());

      // Find or create the "Other attachments" listing.
      const otherAttachmentsList = otherAttachments.querySelector('ul') || document.createElement('ul');

      // Append the new item.
      otherAttachmentsList.appendChild(newListItem);
      otherAttachments.appendChild(otherAttachmentsList);
    },

  };

})(jQuery, Drupal, drupalSettings);
