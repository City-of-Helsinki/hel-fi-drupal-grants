<?php

namespace Drupal\grants_handler;

use Drupal\Core\Session\AccountInterface;
use Drupal\grants_attachments\AttachmentHandler;

/**
 * Helper functions for grants handler.
 */
class Helpers {

  /**
   * Application environment.
   *
   * @var string
   */
  private static string $appEnv;

  /**
   * Applicationtypes.
   *
   * @var array
   */
  private static array $applicationTypes;

  /**
   * All app envs in array.
   *
   * @return array
   *   Unique environments.
   */
  public static function getAppEnvs(): array {
    $envs = [
      'DEV',
      'PROD',
      'TEST',
      'STAGE',
      'LOCAL',
      'LOCALJ',
      'LOCALP',
      self::getAppEnv(),
    ];

    return array_unique($envs);
  }

  /**
   * Return Application environment shortcode.
   *
   * If environment is one of the set ones, use those. But if not, use one in
   * .env file.
   *
   * @return string
   *   Shortcode from current environment.
   */
  public static function getAppEnv(): string {
    if (isset(self::$appEnv) && !empty(self::$appEnv)) {
      return self::$appEnv;
    }

    $appEnv = getenv('APP_ENV');

    if ($appEnv == 'development') {
      self::$appEnv = 'DEV';
    }
    else {
      if ($appEnv == 'production') {
        self::$appEnv = 'PROD';
      }
      else {
        if ($appEnv == 'testing') {
          self::$appEnv = 'TEST';
        }
        else {
          if ($appEnv == 'staging') {
            self::$appEnv = 'STAGE';
          }
          else {
            self::$appEnv = strtoupper($appEnv);
          }
        }
      }
    }
    return self::$appEnv;
  }

  /**
   * Easier method to check if we're in production.
   *
   * @param string $appEnv
   *   App env from handler.
   *
   * @return bool
   *   Is production env?
   */
  public static function isProduction(string $appEnv): bool {
    $proenvs = [
      'production',
      'PRODUCTION',
      'PROD',
    ];
    return in_array($appEnv, $proenvs);
  }

  /**
   * Get application types from config.
   *
   * @return array
   *   Application types parsed from active config.
   */
  public static function getApplicationTypes(): array {
    if (!isset(self::$applicationTypes)) {
      $config = \Drupal::config('grants_metadata.settings');
      $thirdPartyOpts = $config->get('third_party_options');
      $applicationTypes = [];
      foreach ((array) $thirdPartyOpts['application_types'] as $applicationTypeId => $config) {
        $tempConfig = $config;
        foreach ($config['labels'] as $lang => $label) {
          $tempConfig[$lang] = $label;
        }
        $tempConfig['applicationTypeId'] = $applicationTypeId;
        $applicationTypes[$config['id']] = $tempConfig;
      }
      self::$applicationTypes = $applicationTypes;
    }

    return self::$applicationTypes;
  }

  /**
   * Set application types from config.
   *
   * This is for test cases.
   */
  public static function setApplicationTypes($applicationTypes): void {
    self::$applicationTypes = $applicationTypes;
  }

  /**
   * Clear application data for noncopyable elements.
   *
   * @param array $data
   *   Data to copy from.
   *
   * @return array
   *   Cleaned values.
   */
  public static function clearDataForCopying(array $data): array {
    unset($data["sender_firstname"]);
    unset($data["sender_lastname"]);
    unset($data["sender_person_id"]);
    unset($data["sender_user_id"]);
    unset($data["sender_email"]);
    unset($data["metadata"]);
    unset($data["attachments"]);
    unset($data["form_timestamp_submitted"]);
    unset($data["form_timestamp_created"]);

    $data['events'] = [];
    $data['messages'] = [];
    $data['status_updates'] = [];

    // Clear uploaded files..
    foreach (AttachmentHandler::getAttachmentFieldNames($data["application_number"]) as $fieldName) {
      unset($data[$fieldName]);
    }
    unset($data["application_number"]);

    return $data;
  }

  /**
   * Helper function to checks, if user has grants_admin role rights.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   *
   * @return bool
   *   Does user have grant_admin rights.
   */
  public static function isGrantAdmin(AccountInterface $account): bool {
    $currentRoles = $account->getRoles();
    return in_array('grants_admin', $currentRoles) || $account->id() === '1';
  }

  /**
   * Extract field value from array.
   *
   * @param array $attachmentInfo
   *   Attachment info.
   * @param string $fieldId
   *   Field id.
   *
   * @return string|null
   *   String or null.
   */
  public static function extractFieldValue(array $attachmentInfo, string $fieldId): ?string {
    foreach ($attachmentInfo as $innerArray) {
      foreach ($innerArray as $item) {
        if ($item === $fieldId) {
          return $innerArray['value'];
        }
      }
    }
    // Return null if no match is found.
    return NULL;
  }

  /**
   * Find by filename from attachments.
   *
   * @param array $attachment
   *   Attachment.
   * @param array $attachmentInfo
   *   Attachment info.
   *
   * @return array|null
   *   Array or null.
   */
  public static function findByFilename(array $attachment, array $attachmentInfo): ?array {
    foreach ($attachmentInfo as $innerArray) {
      foreach ($innerArray as $item) {
        if ($item['ID'] === 'fileName' && $item['value'] === $attachment['filename']) {
          return $innerArray;
        }
      }
    }
    // Return empty if no match is found.
    return [];
  }

  /**
   * Is recursive array empty.
   *
   * @param array $value
   *   Array to check.
   *
   * @return bool
   *   Empty or not?
   */
  public static function emptyRecursive(array $value): bool {
    $empty = TRUE;
    array_walk_recursive($value, function ($item) use (&$empty) {
      $empty = $empty && empty($item);
    });
    return $empty;
  }

}
