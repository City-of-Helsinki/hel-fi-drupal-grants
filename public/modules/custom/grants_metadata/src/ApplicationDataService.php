<?php

namespace Drupal\grants_metadata;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\grants_attachments\AttachmentHandler;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\DebuggableTrait;
use Drupal\grants_handler\EventsService;
use Drupal\grants_handler\Helpers;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\GuzzleException;

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
   * Name of the navigation handler.
   */
  const HANDLER_ID = 'application_handler';

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Events service.
   *
   * @var \Drupal\grants_handler\EventsService
   */
  protected EventsService $eventsService;

  /**
   * Application getter service.
   *
   * @var \Drupal\grants_handler\ApplicationGetterService
   */
  protected ApplicationGetterService $applicationGetterService;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger channel factory.
   * @param \Drupal\Core\Database\Connection $datababse
   *   Database connection.
   * @param \Drupal\grants_handler\EventsService $eventsService
   *   Events service.
   * @param \Drupal\grants_handler\ApplicationGetterService $applicationGetterService
   *   Application getter service.
   */
  public function __construct(
    LoggerChannelFactoryInterface $loggerChannelFactory,
    Connection $datababse,
    EventsService $eventsService
  ) {
    $this->logger = $loggerChannelFactory->get('grants_application_helpers');
    $this->database = $datababse;
    $this->eventsService = $eventsService;
  }

  /**
   * Set the getter service.
   *
   * @param \Drupal\grants_handler\ApplicationGetterService $applicationGetterService
   */
  public function setApplicationGetterService(ApplicationGetterService $applicationGetterService): void {
    $this->applicationGetterService = $applicationGetterService;
  }

  /**
   * Validate submission data integrity.
   *
   * Validates file uploads as well, we can't allow other updates to data
   * before all attachment related things are done properly with integration.
   *
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   Webform submission object, if known. If this is not set,
   *   submission data must be provided.
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
    ?WebformSubmissionInterface $webform_submission,
    ?array $submissionData,
    string $applicationNumber,
    string $saveIdToValidate): string {

    $submissionData = $this->getSubmissionData($webform_submission, $submissionData, $applicationNumber);
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
   * Get submission data.
   *
   * @param \Drupal\webform\WebformSubmissionInterface|null $webform_submission
   *   Webform submission object.
   * @param array|null $submissionData
   *   Submission data.
   * @param string $applicationNumber
   *   Application number.
   *
   * @return array|null
   *   Submission data.
   */
  private function getSubmissionData(
    ?WebformSubmissionInterface $webform_submission,
    ?array $submissionData,
    string $applicationNumber
  ): ?array {
    if (empty($submissionData)) {
      if ($webform_submission == NULL) {
        try {
          $webform_submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($applicationNumber);
        }
        catch (\Exception | GuzzleException $e) {
        }
      }
      return $webform_submission->getData();
    }
    return $submissionData;
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
  private function getLatestSaveid(string $applicationNumber): string {
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
    string $latestSaveid
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
    string $saveIdToValidate
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
    array $submittedFormData
  ): TypedDataInterface {

    $dataDefinitionKeys = $this->getDataDefinitionClass($submittedFormData['application_type']);

    $dataDefinition = $dataDefinitionKeys['definitionClass']::create($dataDefinitionKeys['definitionId']);

    $typeManager = $dataDefinition->getTypedDataManager();
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
