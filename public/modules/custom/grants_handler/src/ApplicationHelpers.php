<?php

namespace Drupal\grants_handler;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Database\Database;
use Drupal\grants_mandate\CompanySelectException;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use Ramsey\Uuid\Uuid;

/**
 * Handle all things related to applications & submission objects themselves.
 */
abstract class ApplicationHelpers {

  /**
   * Name of the table where log entries are stored.
   */
  const TABLE = 'grants_handler_saveids';

  /**
   * Name of the navigation handler.
   */
  const HANDLER_ID = 'application_handler';

  /**
   * Generate application number from submission id.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $submission
   *   Webform data.
   * @param bool $useOldFormat
   *   Generate application number in old format.
   *
   * @return string
   *   Generated number.
   */
  public static function createApplicationNumber(WebformSubmission &$submission, $useOldFormat = FALSE): string {
    $appParam = Helpers::getAppEnv();

    $serial = $submission->serial();
    $applicationType = $submission->getWebform()
      ->getThirdPartySetting('grants_metadata', 'applicationType');

    $applicationTypeId = $submission->getWebform()
      ->getThirdPartySetting('grants_metadata', 'applicationTypeID');

    if ($useOldFormat) {
      return self::getApplicationNumberInEnvFormatOldFormat($appParam, $applicationType, $serial);
    }

    return self::getApplicationNumberInEnvFormat($appParam, $applicationTypeId, $serial);

  }

  /**
   * Generate next available application number for the submission.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $submission
   *   Webform data.
   *
   * @return string
   *   Generated number.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\helfi_atv\AtvUnexpectedResponseException
   */
  public static function getAvailableApplicationNumber(WebformSubmission &$submission): string {

    $appParam = Helpers::getAppEnv();
    $serial = $submission->serial();
    $webform_id = $submission->getWebform()->id();
    $applicationTypeId = $submission->getWebform()
      ->getThirdPartySetting('grants_metadata', 'applicationTypeID');

    $lastSerialKey = $applicationTypeId . '_' . $appParam;
    $kvService = \Drupal::service('keyvalue.database');
    $kvStorage = $kvService->get('application_numbers');
    $savedSerial = $kvStorage->get($lastSerialKey);

    if (!empty($submission->getData())) {
      return self::createApplicationNumber($submission);
    }

    if ($savedSerial && $savedSerial > $serial) {
      $serial = $savedSerial;
    }

    /** @var \Drupal\helfi_atv\AtvService $atvService */
    $atvService = \Drupal::service('helfi_atv.atv_service');

    $check = TRUE;

    while ($check) {
      $applicationNumber = self::getApplicationNumberInEnvFormat($appParam, $applicationTypeId, $serial);
      $applNumberIsAvailable = $atvService->checkDocumentExistsByTransactionId($applicationNumber);
      if ($applNumberIsAvailable) {
        // Check that there is no local submission with given serial.
        $query = \Drupal::entityQuery('webform_submission')
          ->condition('webform_id', $webform_id)
          ->condition('serial', $serial)
          ->accessCheck(FALSE);
        $results = $query->execute();
        if (empty($results)) {
          $check = FALSE;
        }
        else {
          // Increase serial because we found local a submission.
          $serial++;
        }
      }
      else {
        // No luck, let's check another one.
        $serial++;
      }
    }

    $submission->set('serial', $serial);
    $kvStorage->set($lastSerialKey, $serial);
    return $applicationNumber;
  }

  /**
   * Format application number based by the enviroment.
   */
  private static function getApplicationNumberInEnvFormat($appParam, $typeId, $serial): string {
    $applicationNumber = $appParam . '-' .
      str_pad($typeId, 3, '0', STR_PAD_LEFT) . '-' .
      str_pad($serial, 7, '0', STR_PAD_LEFT);

    if ($appParam == 'PROD') {
      $applicationNumber = str_pad($typeId, 3, '0', STR_PAD_LEFT) . '-' .
        str_pad($serial, 7, '0', STR_PAD_LEFT);
    }

    return $applicationNumber;
  }

  /**
   * Format application number based by the enviroment in old format.
   */
  private static function getApplicationNumberInEnvFormatOldFormat($appParam, $typeId, $serial): string {
    $applicationNumber = 'GRANTS-' . $appParam . '-' . $typeId . '-' . sprintf('%08d', $serial);

    if ($appParam == 'PROD') {
      $applicationNumber = 'GRANTS-' . $typeId . '-' . sprintf('%08d', $serial);
    }

    return $applicationNumber;
  }

  /**
   * Extract serial numbor from application number string.
   *
   * @param string $applicationNumber
   *   Application number.
   *
   * @return string
   *   Webform submission serial.
   */
  public static function getSerialFromApplicationNumber(string $applicationNumber): string {
    $exploded = explode('-', $applicationNumber);
    $number = end($exploded);
    return ltrim($number, '0');
  }

  /**
   * Check for breaking changes in newer webform versions.
   *
   * In this context, breaking changes means all Avus2 changes that
   * will cause the submission of the older webform to fail.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform id.
   *
   * @return bool
   *   If there is any breaking changes.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function hasBreakingChangesInNewerVersion(Webform $webform): bool {
    static $map = [];

    $uuid = $webform->uuid();

    if (isset($map[$uuid])) {
      return $map[$uuid];
    }

    $applicationType = $webform->getThirdPartySetting('grants_metadata', 'applicationType');

    $latestApplicationForm = self::getLatestApplicationForm($applicationType);

    // If no latest form, then no breaking changes.
    if (!$latestApplicationForm) {
      return FALSE;
    }

    $parent = $latestApplicationForm->getThirdPartySetting('grants_metadata', 'parent');
    $hasBreakingChanges = $latestApplicationForm->getThirdPartySetting('grants_metadata', 'avus2BreakingChange');

    while (!empty($parent)) {

      $map[$parent] = $hasBreakingChanges;

      $loaded_webform = \Drupal::entityTypeManager()
        ->getStorage('webform')
        ->loadByProperties([
          'uuid' => $parent,
        ]);

      $wf = reset($loaded_webform);
      $parent = $wf->getThirdPartySetting('grants_metadata', 'parent');

      // No need to check the flag,
      // if we already have a newer version with breaking changes.
      if (!$hasBreakingChanges) {
        $hasBreakingChanges = $wf->getThirdPartySetting('grants_metadata', 'avus2BreakingChange');
      }
    }

    return $map[$uuid] ?? FALSE;

  }

  /**
   * Extract webform id from application number string.
   *
   * @param string $applicationNumber
   *   Application number.
   * @param bool $all
   *   Should all matching webforms be returned?
   *
   * @return \Drupal\webform\Entity\Webform
   *   Webform object.
   */
  public static function getWebformFromApplicationNumber(string $applicationNumber, $all = FALSE): bool|Webform|array {
    $isOldFormat = FALSE;
    if (strpos($applicationNumber, 'GRANTS') !== FALSE) {
      $isOldFormat = TRUE;
    }

    $fieldToCheck = $isOldFormat ? 'code' : 'applicationTypeId';

    // Explode number.
    $exploded = explode('-', $applicationNumber);
    // Get serial.
    array_pop($exploded);
    // Get application id.
    $webformTypeId = array_pop($exploded);
    // Load webforms.
    $wids = \Drupal::entityQuery('webform')
      ->execute();
    $webforms = Webform::loadMultiple(array_keys($wids));

    $applicationTypes = Helpers::getApplicationTypes();

    // Look for for application type and return if found.
    $webform = array_filter($webforms, function ($wf) use ($webformTypeId, $applicationTypes, $fieldToCheck) {

      $thirdPartySettings = $wf->getThirdPartySettings('grants_metadata');
      $thisApplicationTypeConfig = array_filter($applicationTypes, function ($appType) use ($thirdPartySettings) {
        if (isset($thirdPartySettings["applicationTypeID"]) &&
          $thirdPartySettings["applicationTypeID"] ===
          (string) $appType["applicationTypeId"]) {
          return TRUE;
        }
        return FALSE;
      });
      $thisApplicationTypeConfig = reset($thisApplicationTypeConfig);
      if (isset($thisApplicationTypeConfig[$fieldToCheck]) && $thisApplicationTypeConfig[$fieldToCheck] == $webformTypeId) {
        return TRUE;
      }
      return FALSE;
    });

    if (!$webform) {
      return FALSE;
    }

    if ($all) {
      return $webform;
    }

    return reset($webform);
  }

  /**
   * Get Webform object by UUID.
   *
   * @param string $uuid
   *   Uuid of the webform.
   * @param string $application_number
   *   The application number.
   *
   * @return \Drupal\webform\Entity\Webform
   *   Webform object.
   */
  public static function getWebformByUuid(string $uuid, string $application_number): Webform|bool|array {

    $wids = \Drupal::entityQuery('webform')
      ->condition('uuid', $uuid)
      ->execute();

    // Fallback to original method, if webform for some reason is not found.
    if (empty($wids)) {
      return self::getWebformFromApplicationNumber($application_number);
    }

    return Webform::load(reset($wids));
  }

  /**
   * Extract serial numbor from application number string.
   *
   * @param string $applicationNumber
   *   Application number.
   * @param bool $refetch
   *   Force refetch from ATV.
   *
   * @return array|\Drupal\helfi_atv\AtvDocument
   *   ATV Document
   *
   * @throws \Drupal\grants_mandate\CompanySelectException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   */
  public static function atvDocumentFromApplicationNumber(
    string $applicationNumber,
    bool $refetch = FALSE,
  ): array|AtvDocument {

    /** @var \Drupal\helfi_atv\AtvService $atvService */
    $atvService = \Drupal::service('helfi_atv.atv_service');

    $grantsProfileService = \Drupal::service('grants_profile.service');
    $selectedCompany = $grantsProfileService->getSelectedRoleData();

    // If no company selected, no mandates no access.
    if ($selectedCompany == NULL) {
      throw new CompanySelectException('User not authorised');
    }
    try {
      $sParams = [
        'transaction_id' => $applicationNumber,
        'lookfor' => 'appenv:' . Helpers::getAppEnv(),
      ];

      /** @var \Drupal\helfi_atv\AtvDocument[] $document */
      $document = $atvService->searchDocuments(
        $sParams,
        $refetch
      );
    }
    catch (\Throwable $e) {
    }

    if (empty($document)) {
      throw new AtvDocumentNotFoundException('Document not found');
    }
    $document = reset($document);
    return $document;
  }

  /**
   * Set up sender details from helsinkiprofiili data.
   *
   * @return array
   *   Sender details.
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \Drupal\grants_handler\ApplicationException
   */
  public static function parseSenderDetails(): array {

    $helfiHelsinkiProfiiliUserdata = \Drupal::service('helfi_helsinki_profiili.userdata');

    // Set sender information after save so no accidental saving of data.
    $userProfileData = $helfiHelsinkiProfiiliUserdata->getUserProfileData();
    $userData = $helfiHelsinkiProfiiliUserdata->getUserData();

    $senderDetails = [];

    if (isset($userProfileData["myProfile"])) {
      $data = $userProfileData["myProfile"];
    }
    else {
      $data = $userProfileData;
    }

    // If no userprofile data, we need to hardcode these values.
    if ($userProfileData == NULL || $userData == NULL) {
      throw new ApplicationException('No profile data found for user.');
    }
    else {
      $senderDetails['sender_firstname'] = $data["verifiedPersonalInformation"]["firstName"];
      $senderDetails['sender_lastname'] = $data["verifiedPersonalInformation"]["lastName"];
      $senderDetails['sender_person_id'] = $data["verifiedPersonalInformation"]["nationalIdentificationNumber"];
      $senderDetails['sender_user_id'] = $userData["sub"];
      $senderDetails['sender_email'] = $data["primaryEmail"]["email"];
    }

    return $senderDetails;
  }

  /**
   * Get data definition class from application type.
   *
   * @param string $type
   *   Type of the application.
   */
  public static function getDataDefinition(string $type) {
    $defClass = Helpers::getApplicationTypes()[$type]['dataDefinition']['definitionClass'];
    $defId = Helpers::getApplicationTypes()[$type]['dataDefinition']['definitionId'];
    return $defClass::create($defId);
  }

  /**
   * The getSubmissionIdWithSerialAndWebformId method.
   *
   * This method queries the database in an attempt to
   * find a webform submission ID with the help of a
   * submission serial and a webform ID. If one is not
   * found, then we create a submission.
   *
   * @param string $serial
   *   A webform submission serial.
   * @param string $webformId
   *   A webform ID.
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   An ATV document.
   *
   * @return string
   *   A webform submission ID.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Exception on EntityStorageException.
   */
  public static function getSubmissionIdWithSerialAndWebformId(
    string $serial,
    string $webformId,
    AtvDocument $document,
  ): string {
    $database = Database::getConnection();
    $query = $database->select('webform_submission', 'ws')
      ->fields('ws', ['sid'])
      ->condition('ws.serial', $serial)
      ->condition('ws.webform_id', $webformId);
    $result = $query->execute();
    $sid = $result->fetchField();

    // If a submission ID is found, return it.
    if ($sid) {
      return $sid;
    }

    // If we can't find a submission, then create one.
    $webformSubmission = self::createWebformSubmissionWithSerialAndWebformId($serial, $document);
    return $webformSubmission->id();
  }

  /**
   * The createWebformSubmissionWithSerialAndWebformId method.
   *
   * This method creates a webform submission and sets the
   * webform ID, serial and draft state if needed.
   *
   * @param string $serial
   *   A webform submission serial.
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   An ATV document.
   *
   * @return \Drupal\webform\Entity\WebformSubmission
   *   A webform submission.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Exception on EntityStorageException.
   */
  protected static function createWebformSubmissionWithSerialAndWebformId(
    string $serial,
    AtvDocument $document,
  ): WebformSubmission {

    $metaData = $document->getMetadata();
    $webformUuidExists = isset($metaData['form_uuid']) && !empty($metaData['form_uuid']);

    $webform = $webformUuidExists
    ? self::getWebformByUuid($metaData['form_uuid'], $document->getTransactionId())
    : self::getWebformFromApplicationNumber($document->getTransactionId());

    $webformId = $webform->id();

    $submissionObject = WebformSubmission::create(['webform_id' => $webformId]);
    $submissionObject->set('serial', $serial);

    // Mark that we don't want to generate new application
    // number, as we just assigned the serial from ATV application id.
    // Check GrantsHandler@preSave.
    WebformSubmissionNotesHelper::setValue(
      $submissionObject,
      'skip_available_number_check',
      TRUE
    );
    if ($document->getStatus() == 'DRAFT') {
      $submissionObject->set('in_draft', TRUE);
    }
    $submissionObject->save();
    return $submissionObject;
  }

  /**
   * Logs the current submission page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   A webform submission entity.
   * @param string $applicationNumber
   *   The page to log.
   * @param array $userData
   *   User data.
   * @param string $saveId
   *   Submission save id.
   *
   * @return string
   *   The save ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Drupal\grants_mandate\CompanySelectException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Exception
   */
  public static function logSubmissionSaveid(
    ?WebformSubmissionInterface $webform_submission,
    string $applicationNumber,
    array $userData,
    string $saveId = '',
  ): string {
    if (!$userData) {
      throw new \Exception('User data is required');
    }

    if (empty($saveId)) {
      $saveId = Uuid::uuid4()->toString();
    }

    if ($webform_submission == NULL) {
      /** @var \Drupal\grants_handler\ApplicationGetterService $applicationGetterService */
      $applicationGetterService = \Drupal::service('grants_handler.application_getter_service');
      $webform_submission = $applicationGetterService->submissionObjectFromApplicationNumber($applicationNumber);
    }

    $currentUser = \Drupal::currentUser();
    $database = \Drupal::database();

    $fields = [
      'webform_id' => ($webform_submission) ? $webform_submission->getWebform()
        ->id() : '',
      'sid' => ($webform_submission) ? $webform_submission->id() : 0,
      'handler_id' => self::HANDLER_ID,
      'application_number' => $applicationNumber,
      'saveid' => $saveId,
      'uid' => $currentUser->id(),
      'user_uuid' => $userData['sub'] ?? '',
      'timestamp' => (string) (new Time)->getRequestTime(),
    ];

    $query = $database->insert(self::TABLE, $fields);
    $query->fields($fields)->execute();

    return $saveId;

  }

  /**
   * Tries to find latest webform for given application ID.
   *
   * @param mixed $id
   *   Application id (eg. KASKOIPLISA)
   *
   * @return \Drupal\webform\Entity\Webform|null
   *   Return webform object if found, else null.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getLatestApplicationForm($id): Webform|NULL {

    $webforms = \Drupal::entityTypeManager()
      ->getStorage('webform')
      ->loadByProperties([
        'third_party_settings.grants_metadata.applicationType' => $id,
        'archive' => FALSE,
        'third_party_settings.grants_metadata.status' => 'released',
      ]);

    $webform = reset($webforms);
    if ($webform) {
      return $webform;
    }

    return NULL;
  }

  /**
   * Get all Webform objects for given application id.
   *
   * @param string $id
   *   Application ID.
   */
  public static function getActiveApplicationWebforms(string $id): array {
    $webforms = \Drupal::entityTypeManager()
      ->getStorage('webform')
      ->loadByProperties([
        'third_party_settings.grants_metadata.applicationType' => $id,
        'archive' => FALSE,
      ]);

    $result = [
      'released' => [],
      'development' => [],
    ];

    foreach ($webforms as $webform) {
      $webformStatus = $webform->getThirdPartySetting('grants_metadata', 'status');
      if (empty($webformStatus)) {
        $webformStatus = 'released';
      }
      $result[$webformStatus][] = $webform;
    }

    return $result;
  }

  /**
   * Checks if webform configuration can duplicated with given Application ID.
   *
   * General rule is that one application type ID can have maximum number of 1
   * Production & In development versions.
   *
   * @param string $id
   *   Application ID.
   *
   * @return bool
   *   Can the webform be duplicated.
   */
  public static function isApplicationWebformDuplicatable(string $id) {
    $applicationForms = self::getActiveApplicationWebforms($id);
    return count($applicationForms['released']) <= 1 && count($applicationForms['development']) === 0;
  }

}
