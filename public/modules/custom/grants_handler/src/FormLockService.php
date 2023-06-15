<?php

namespace Drupal\grants_handler;

use Drupal\Core\Database\Connection;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;

/**
 * Form lock service.
 */
class FormLockService {

  protected const TABLE = 'grants_handler_locks';

  protected const LOCK_TYPE_APPLICATION = 0;
  protected const LOCK_TYPE_PROFILE     = 1;

  /**
   * Constructs the FormLockService.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The Database connection.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData
   *   Helsinki Profiili service.
   */
  public function __construct(
    private Connection $database,
    private HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
  ) {}

  /**
   * Public method to check if application form is locked for user.
   *
   * @param string $application_number
   *  Application number.
   */
  public function isApplicationFormLocked(string $application_number) {
    return $this->isFormLocked($application_number, self::LOCK_TYPE_APPLICATION);
  }

  /**
   * Public method to check if profile form is locked for user.
   *
   * @param string $profile_id
   *   Profile id.
   */
  public function isProfileFormLocked(string $profile_id) {
    return $this->isFormLocked($profile_id, self::LOCK_TYPE_PROFILE);
  }

  /**
   * Public method to create lock for application form.
   *
   * @param string $application_number
   *   Application number.
   */
  public function createOrRefreshApplicationLock(string $application_number) {
    return $this->createOrRefreshLock($application_number, self::LOCK_TYPE_APPLICATION);
  }

  /**
   * Public method to create lock for profile form.
   *
   * @param string $profile_id
   *   Profile id.
   */
  public function createOrRefreshProfileFormLock(string $profile_id) {
    return $this->createOrRefreshLock($profile_id, self::LOCK_TYPE_PROFILE);
  }

  /**
   * Public method to release application form lock.
   *
   * @param string $application_id
   *  Application id.
   */
  public function releaseApplicationLock(string $application_id) {
    return $this->releaseLock($application_id, self::LOCK_TYPE_APPLICATION);
  }

  /**
   * Public method to release profile form lock.
   *
   * @param string $profile_id
   *  Profile .d
   */
  public function releaseProfileFormLock(string $profile_id) {
    return $this->releaseLock($profile_id, self::LOCK_TYPE_PROFILE);
  }

  /**
   * Checks if the form is locked for current user.
   */
  private function isFormLocked(string $formId, int $lockType):bool {
    $lock = $this->getLock($formId, $lockType);
    // No lock found.
    if (!$lock) {
      return FALSE;
    }

    $userProfile = $this->helsinkiProfiiliUserData->getUserData();

    // If lock owner is same as the current user.
    if ($userProfile['sub'] === $lock->user_uuid) {
      return FALSE;
    }
    else {
      return TRUE;
    }
  }

  /**
   * Tries to find lock for given application number and type.
   *
   * @return object|false
   *   Lock object or false if not found.
   */
  private function getLock($id, $lockType) {

    $dt = new \DateTime();
    $timeStamp = $dt->getTimestamp();

    $query = $this->database->select(self::TABLE, 'l')
      ->fields('l')
      ->condition('application_number', $id)
      ->condition('form_type', $lockType)
      ->condition('expire', $timeStamp, '>=');

    $result = $query->execute()->fetch();

    return $result;
  }

  /**
   * Creates a lock for form or updates expire time of existing one.
   */
  private function createOrRefreshLock(string $formId, int $lockType) {
    $userProfile = $this->helsinkiProfiiliUserData->getUserData();
    $existingLock = $this->getLock($formId, $lockType);

    if (!$existingLock) {
      $expirationPeriod = $this->getExpirationPeriod($lockType);
      $expire = new \DateTime($expirationPeriod);
      $lockValues = [
        'user_uuid'          => $userProfile['sub'],
        'application_number' => $formId,
        'form_type'          => $lockType,
        'expire'             => $expire->getTimestamp(),
      ];

      $this->database->insert(self::TABLE)
        ->fields($lockValues)
        ->execute();
    }

    // Update lock.
  }

  /**
   * Release the lock of given form.
   */
  public function releaseLock(string $formId, $lockType) {
    $userProfile = $this->helsinkiProfiiliUserData->getUserData();

    $this->database->delete(self::TABLE)
      ->condition('form_type', $lockType)
      ->condition('application_number', $formId)
      ->condition('user_uuid', $userProfile['sub'])
      ->execute();
  }

  /**
   * Clears all locks which have expired.
   */
  public function clearExpiredLocks() {
    $dt = new \DateTime();
    $currentTime = $dt->getTimestamp();

    $this->database->delete(self::TABLE)
      ->condition('expire', $currentTime, '<')
      ->execute();
  }

  /**
   * Gets string addition string for date time.
   *
   * @param mixed $type
   *   Form type.
   *
   * @return string
   *   String to be used with DateTime object.
   */
  private function getExpirationPeriod($type = NULL) {
    $expiration = NULL;
    switch ($type) {
      case self::LOCK_TYPE_APPLICATION:
        $expiration = '+30 minutes';
        break;

      case self::LOCK_TYPE_PROFILE:
        $expiration = '15 minutes';
        break;

      default:
        $expiration = '+30 minutes';
        break;
    }

    return $expiration;
  }

}
