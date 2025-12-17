<?php

namespace Drupal\grants_application;

/**
 * Random functionalities.
 */
class Helper {

  /**
   * Get current environment name.
   *
   * @return string
   *   Environment name.
   */
  public static function getAppEnv(): string {
    return match(getenv('APP_ENV')) {
      'development' => 'DEV',
      'testing' => 'TEST',
      'staging' => 'STAGE',
      'production' => 'PROD',
      default => getenv('APP_ENV'),
    };
  }

  /**
   * Find the file related to user bank selection.
   *
   * On page 1, user selects a bank account which should have been previously
   * added to the user profile (hakuprofiili). All bank accounts have a
   * proof of ownership file attached to them.
   *
   * The bankAccounts has the name of the file.
   * The profileAttachments has the files attached to them.
   * Compare the two to find correct file to add to the
   * form submission.
   *
   * Profile bank accounts and attachments can be found from
   * UserInformationService::getGrantsProfileContent.
   *
   * @param string $selectedBankAccountNumber
   *   The selected bank account number.
   * @param array $bankAccounts
   *   All bank accounts from the user profile.
   * @param array $profileAttachments
   *   The profile attachment files.
   *
   * @return array|null
   *   The normal attachment array from ATV.
   */
  public static function findMatchingBankConfirmationFile(
    string $selectedBankAccountNumber,
    array $bankAccounts,
    array $profileAttachments,
  ): array|null {
    $selected_account = array_find($bankAccounts, fn(array $account) => $account['bankAccount'] === $selectedBankAccountNumber);
    if (!$selected_account) {
      // This should never happen unless user has removed a bank account.
      throw new \Exception("Unknown bank account");
    }
    return array_find($profileAttachments, fn(array $attachment) => $attachment['filename'] === $selected_account['confirmationFile']);
  }

}
