<?php

namespace Drupal\grants_metadata;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\grants_attachments\AttachmentHandler;
use Drupal\grants_events\EventsService;
use Drupal\grants_handler\ApplicationException;
use Drupal\grants_handler\DebuggableTrait;
use Drupal\grants_handler\Helpers;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;

/**
 * Application data service.
 */
final class ApplicationDataService {

  use DebuggableTrait;

  /**
   * Name of the table where log entries are stored.
   */
  const TABLE = 'grants_handler_saveids';

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Events service.
   *
   * @var \Drupal\grants_events\EventsService
   */
  protected EventsService $eventsService;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger channel factory.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData
   *   Helsinki profiili user data.
   */
  public function __construct(
    LoggerChannelFactoryInterface $loggerChannelFactory,
    private readonly Connection $database,
    private readonly HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
  ) {
    $this->logger = $loggerChannelFactory->get('grants_application_helpers');
  }

  /**
   * Set the getter service.
   *
   * @param \Drupal\grants_events\EventsService $eventsService
   *   Events service.
   */
  public function setEventsService(EventsService $eventsService): void {
    $this->eventsService = $eventsService;
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
  public function parseSenderDetails(): array {
    // Set sender information after save so no accidental saving of data.
    $userProfileData = $this->helsinkiProfiiliUserData->getUserProfileData();
    $userData = $this->helsinkiProfiiliUserData->getUserData();

    // If no userprofile data, we cannot proceed.
    if (!$userProfileData || !$userData) {
      throw new ApplicationException('No profile data found for user.');
    }

    $senderDetails = [];

    if (isset($userProfileData["myProfile"])) {
      $data = $userProfileData["myProfile"];
    }
    else {
      $data = $userProfileData;
    }

    $senderDetails['sender_firstname'] = $data["verifiedPersonalInformation"]["firstName"];
    $senderDetails['sender_lastname'] = $data["verifiedPersonalInformation"]["lastName"];
    $senderDetails['sender_person_id'] = $data["verifiedPersonalInformation"]["nationalIdentificationNumber"];
    $senderDetails['sender_user_id'] = $userData["sub"];
    $senderDetails['sender_email'] = $data["primaryEmail"]["email"];

    return $senderDetails;
  }

  /**
   * Validate submission data integrity.
   *
   * Validates file uploads as well, we can't allow other updates to data
   * before all attachment related things are done properly with integration.
   *
   *   Webform submission object, if known. If this is not set,
   *   submission data must be provided.
   *
   * @param array|null $submissionData
   *   Submission data. If no submission object, this is required.
   * @param string $applicationNumber
   *   Application number.
   * @param string $saveIdToValidate
   *   Save uuid to validate data integrity against.
   *
   * @return string
   *   Data integrity status.
   *
   * @throws \Exception
   */
  public function validateDataIntegrity(
    ?array $submissionData,
    string $applicationNumber,
    string $saveIdToValidate,
  ): string {

    if (empty($submissionData)) {
      $this->logNoSubmissionData($applicationNumber, $saveIdToValidate);
      return 'NO_SUBMISSION_DATA';
    }

    if ($this->shouldSkipIntegrityCheck($submissionData)) {
      return 'OK';
    }

    $latestSaveid = $this->getLatestSaveid($applicationNumber);
    if ($this->isInitialOrCopiedSave($saveIdToValidate)) {
      return 'OK';
    }

    if ($this->isSaveIdMismatch($saveIdToValidate, $latestSaveid, $applicationNumber)) {
      return 'DATA_NOT_SAVED_ATV';
    }

    if ($this->isDataNotSavedToAvus($saveIdToValidate, $submissionData, $applicationNumber, $latestSaveid)) {
      return 'DATA_NOT_SAVED_AVUS2';
    }

    if ($this->hasPendingFileUploads($submissionData, $applicationNumber, $latestSaveid, $saveIdToValidate)) {
      return 'FILE_UPLOAD_PENDING';
    }

    return 'OK';
  }

  /**
   * Log when no submission data is found.
   *
   * @param string $applicationNumber
   *   Application number.
   * @param string $saveIdToValidate
   *   Save id to validate.
   */
  private function logNoSubmissionData(string $applicationNumber, string $saveIdToValidate): void {
    $this->logger->log('info', 'No submissiondata when trying to validate saveid: %application_number @saveid', [
      '%application_number' => $applicationNumber,
      '@saveid' => $saveIdToValidate,
    ]);
  }

  /**
   * Check if integrity check should be skipped.
   *
   * @param array $submissionData
   *   Submission data.
   *
   * @return bool
   *   Should skip integrity check.
   */
  private function shouldSkipIntegrityCheck(array $submissionData): bool {
    $appEnv = Helpers::getAppEnv();
    $isProduction = Helpers::isProduction($appEnv);
    return !$isProduction && isset($submissionData['status']) && $submissionData['status'] === 'DRAFT';
  }

  /**
   * Get the latest save id for the application.
   *
   * @param string $applicationNumber
   *   Application number.
   *
   * @return string
   *   Latest save id.
   *
   * @throws \Exception
   */
  protected function getLatestSaveid(string $applicationNumber): string {
    $query = $this->database->select(self::TABLE, 'l');
    $query->condition('application_number', $applicationNumber);
    $query->fields('l', ['lid', 'saveid']);
    $query->orderBy('l.lid', 'DESC');
    $query->range(0, 1);

    $saveid_log = $query->execute()->fetch();
    return !empty($saveid_log->saveid) ? $saveid_log->saveid : '';
  }

  /**
   * Check if the save id is initial or copied.
   *
   * @param string $saveIdToValidate
   *   Save id to validate.
   *
   * @return bool
   *   Is initial or copied save.
   */
  private function isInitialOrCopiedSave(string $saveIdToValidate): bool {
    return $saveIdToValidate == 'copiedSave' || $saveIdToValidate == 'initialSave';
  }

  /**
   * Check if the save id is mismatching.
   *
   * @param string $saveIdToValidate
   *   Save id to validate.
   * @param string $latestSaveid
   *   Latest save id.
   * @param string $applicationNumber
   *   Application number.
   *
   * @return bool
   *   Is save id mismatching.
   */
  private function isSaveIdMismatch(string $saveIdToValidate, string $latestSaveid, string $applicationNumber): bool {
    if ($saveIdToValidate !== $latestSaveid) {
      $this->logger->log('info', 'Save ids not matching  %application_number ATV:@saveid, Local: %local_save_id', [
        '%application_number' => $applicationNumber,
        '%local_save_id' => $latestSaveid,
        '@saveid' => $saveIdToValidate,
      ]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if data is not saved to Avustus2.
   *
   * @param string $saveIdToValidate
   *   Save id to validate.
   * @param array $submissionData
   *   Submission data.
   * @param string $applicationNumber
   *   Application number.
   * @param string $latestSaveid
   *   Latest save id.
   *
   * @return bool
   *   Is data not saved to Avus.
   */
  private function isDataNotSavedToAvus(
    string $saveIdToValidate,
    array $submissionData,
    string $applicationNumber,
    string $latestSaveid,
  ): bool {
    $applicationEvents = $this->eventsService->filterEvents($submissionData['events'] ?? [], 'INTEGRATION_INFO_APP_OK');

    if (!in_array($saveIdToValidate, $applicationEvents['event_targets']) &&
      isset($submissionData['status']) && $submissionData['status'] != 'DRAFT') {
      $this->logger->log('info', 'Data not saved to Avus. %application_number ATV:@saveid, Local: %local_save_id', [
        '%application_number' => $applicationNumber,
        '%local_save_id' => $latestSaveid,
        '@saveid' => $saveIdToValidate,
      ]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if there are pending file uploads.
   *
   * @param array $submissionData
   *   Submission data.
   * @param string $applicationNumber
   *   Application number.
   * @param string $latestSaveid
   *   Latest save id.
   * @param string $saveIdToValidate
   *   Save id to validate.
   *
   * @return bool
   *   Are there pending file uploads.
   */
  private function hasPendingFileUploads(
    array $submissionData,
    string $applicationNumber,
    string $latestSaveid,
    string $saveIdToValidate,
  ): bool {
    $attachmentEvents = $this->eventsService->filterEvents($submissionData['events'] ?? [], 'HANDLER_ATT_OK');

    $fileFieldNames = AttachmentHandler::getAttachmentFieldNames($submissionData["application_number"]);

    $nonUploaded = 0;
    foreach ($fileFieldNames as $fieldName) {
      $fileField = $submissionData[$fieldName] ?? NULL;
      if ($fileField == NULL) {
        continue;
      }
      if (!$this->isMulti($fileField) && !empty($fileField['fileName']) &&
        (isset($fileField['fileStatus']) && $fileField['fileStatus'] !== 'justUploaded') &&
        !in_array($fileField['fileName'], $attachmentEvents["event_targets"])) {
        $nonUploaded++;
      }
    }

    if ($nonUploaded !== 0) {
      $this->logger->log('info', 'File upload not finished.  %application_number ATV:@saveid, Local: %local_save_id', [
        '%application_number' => $applicationNumber,
        '%local_save_id' => $latestSaveid,
        '@saveid' => $saveIdToValidate,
      ]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Is array multidimensional.
   *
   * @param array $arr
   *   Array to be inspected.
   *
   * @return bool
   *   True or false.
   */
  public function isMulti(array $arr): bool {
    foreach ($arr as $v) {
      if (is_array($v)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get typed data object for webform data.
   *
   * @param array $submittedFormData
   *   Form data.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   Typed data with values set.
   */
  public function webformToTypedData(
    array $submittedFormData,
  ): TypedDataInterface {

    $dataDefinitionKeys = $this->getDataDefinitionClass($submittedFormData['application_type']);

    $dataDefinition = $dataDefinitionKeys['definitionClass']::create($dataDefinitionKeys['definitionId']);

    $typeManager = $dataDefinition->getTypedDataManager();

    /** @var \Drupal\Core\TypedData\TypedDataInterface $applicationData */
    $applicationData = $typeManager->create($dataDefinition);

    $applicationData->setValue($submittedFormData);

    return $applicationData;
  }

  /**
   * Get data definition class from application type.
   *
   * @param string $type
   *   Type of the application.
   */
  public function getDataDefinitionClass(string $type) {
    return Helpers::getApplicationTypes()[$type]['dataDefinition'];
  }

  /**
   * Get data definition class from application type.
   *
   * @param string $type
   *   Type of the application.
   */
  public function getDataDefinition(string $type) {
    $defClass = Helpers::getApplicationTypes()[$type]['dataDefinition']['definitionClass'];
    $defId = Helpers::getApplicationTypes()[$type]['dataDefinition']['definitionId'];
    return $defClass::create($defId);
  }

}
