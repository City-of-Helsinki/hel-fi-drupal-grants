<?php

namespace Drupal\grants_metadata;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\grants_attachments\AttachmentHandler;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_handler\DebuggableTrait;
use Drupal\grants_handler\EventsService;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\GuzzleException;

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
   * Constructor.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   Logger channel factory.
   */
  public function __construct(
    LoggerChannelFactoryInterface $loggerChannelFactory,
    Connection $datababse,
    EventsService $eventsService
  ) {
    $this->logger = $loggerChannelFactory->get('grants_application_handler');
    $this->database = $datababse;
    $this->eventsService = $eventsService;
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Exception
   */
  public function validateDataIntegrity(
    ?WebformSubmissionInterface $webform_submission,
    ?array $submissionData,
    string $applicationNumber,
    string $saveIdToValidate): string {

    if (empty($submissionData)) {
      if ($webform_submission == NULL) {
        try {
          $webform_submission = ApplicationHandler::submissionObjectFromApplicationNumber($applicationNumber);
        }
        catch (\Exception|GuzzleException $e) {}
      }
      $submissionData = $webform_submission->getData();
    }
    if (empty($submissionData)) {
      $this->logger->log('info', 'No submissiondata when trying to validate saveid: %application_number @saveid', [
        '%application_number' => $applicationNumber,
        '@saveid' => $saveIdToValidate,
      ]);
      return 'NO_SUBMISSION_DATA';
    }

    $appEnv = ApplicationHandler::getAppEnv();
    $isProduction = ApplicationHandler::isProduction($appEnv);

    // Skip integrity check for non-prod envs while handling DRAFTs.
    if (!$isProduction && isset($submissionData['status']) && $submissionData['status'] === 'DRAFT') {
      return 'OK';
    }

    $query = $this->database->select(self::TABLE, 'l');
    $query->condition('application_number', $applicationNumber);
    $query->fields('l', [
      'lid',
      'saveid',
    ]);
    $query->orderBy('l.lid', 'DESC');
    $query->range(0, 1);

    $saveid_log = $query->execute()->fetch();
    $latestSaveid = !empty($saveid_log->saveid) ? $saveid_log->saveid : '';

    // initialSave or copied save no datavalidation.
    if ($saveIdToValidate == 'copiedSave' || $saveIdToValidate == 'initialSave') {
      return 'OK';
    }

    if ($saveIdToValidate !== $latestSaveid) {
      $this->logger->log('info', 'Save ids not matching  %application_number ATV:@saveid, Local: %local_save_id', [
        '%application_number' => $applicationNumber,
        '%local_save_id' => $latestSaveid,
        '@saveid' => $saveIdToValidate,
      ]);
      return 'DATA_NOT_SAVED_ATV';
    }

    $applicationEvents = $this->eventsService->filterEvents($submissionData['events'] ?? [], 'INTEGRATION_INFO_APP_OK');

    if (!in_array($saveIdToValidate, $applicationEvents['event_targets']) &&
      isset($submissionData['status']) && $submissionData['status'] != 'DRAFT') {
      $this->logger->log('info', 'Data not saved to Avus. %application_number ATV:@saveid, Local: %local_save_id', [
        '%application_number' => $applicationNumber,
        '%local_save_id' => $latestSaveid,
        '@saveid' => $saveIdToValidate,
      ]);
      return 'DATA_NOT_SAVED_AVUS2';
    }

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
        !in_array($fileField['fileName'], $attachmentEvents["event_targets"])
      ) {
        $nonUploaded++;
      }
    }

    if ($nonUploaded !== 0) {
      $this->logger->log('info', 'File upload not finished.  %application_number ATV:@saveid, Local: %local_save_id', [
        '%application_number' => $applicationNumber,
        '%local_save_id' => $latestSaveid,
        '@saveid' => $saveIdToValidate,
      ]);
      return 'FILE_UPLOAD_PENDING';
    }

    return 'OK';

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

}
