<?php

namespace Drupal\grants_handler;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Language\LanguageManager;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\grants_attachments\AttachmentHandler;
use Drupal\grants_mandate\CompanySelectException;
use Drupal\grants_metadata\ApplicationDataService;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_metadata\DocumentContentMapper;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\ProfileDataException;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;

/**
 * Handle all things related to applications & submission objects themselves.
 */
class ApplicationHandler {

  use StringTranslationTrait;
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
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * The helfi_helsinki_profiili.userdata service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata;

  /**
   * Atv access.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Atv data mapper.
   *
   * @var \Drupal\grants_metadata\AtvSchema
   */
  protected AtvSchema $atvSchema;

  /**
   * Grants profile access.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Holds document fetched from ATV for checks.
   *
   * @var \Drupal\helfi_atv\AtvDocument
   */
  protected AtvDocument $atvDocument;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Show messages.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Handle events with applications.
   *
   * @var \Drupal\grants_handler\EventsService
   */
  protected EventsService $eventsService;

  /**
   * Attachment handler class.
   *
   * @var \Drupal\grants_attachments\AttachmentHandler
   */
  protected AttachmentHandler $attachmentHandler;

  /**
   * Endpoint used for integration.
   *
   * @var string
   */
  protected string $endpoint;

  /**
   * Username for REST endpoint.
   *
   * @var string
   */
  protected string $username;

  /**
   * Password for endpoint.
   *
   * @var string
   */
  protected string $password;

  /**
   * New status header text for integration.
   *
   * @var string
   */
  protected string $newStatusHeader;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManager
   */
  protected LanguageManager $languageManager;

  /**
   * Application statuses.
   *
   * @var array
   */
  protected array $applicationStatuses;

  /**
   * Access form errors.
   *
   * @var \Drupal\grants_handler\GrantsHandlerNavigationHelper
   */
  protected GrantsHandlerNavigationHelper $grantsHandlerNavigationHelper;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   *   The current_user service.
   */
  protected AccountProxyInterface $currentUser;


  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * Validation logic in separate class.
   *
   * @var \Drupal\grants_handler\ApplicationValidator
   */
  protected ApplicationValidator $applicationValidator;

  /**
   * Application status service.
   *
   * @var \Drupal\grants_handler\ApplicationStatusService
   */
  protected ApplicationStatusService $applicationStatusService;

  /**
   * Application data service.
   *
   * @var \Drupal\grants_metadata\ApplicationDataService
   */
  protected ApplicationDataService $applicationDataService;

  /**
   * Application data service.
   *
   * @var \Drupal\grants_metadata\ApplicationInitService
   */
  protected ApplicationInitService $applicationInitService;

  /**
   * Constructs an ApplicationUploader object.
   *
   * @param \GuzzleHttp\Client $http_client
   *   The HTTP client.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helfi_helsinki_profiili_userdata
   *   The helfi_helsinki_profiili.userdata service.
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   Access to ATV.
   * @param \Drupal\grants_metadata\AtvSchema $atvSchema
   *   ATV schema mapper.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Access grants profile data.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   Logger.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Messenger.
   * @param \Drupal\grants_handler\EventsService $eventsService
   *   Access to events.
   * @param \Drupal\Core\Database\Connection $datababse
   *   Database connection.
   * @param \Drupal\Core\Language\LanguageManager $languageManager
   *   Language manager.
   * @param \Drupal\grants_handler\GrantsHandlerNavigationHelper $grantsFormNavigationHelper
   *   Access error messages.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current_user service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\grants_handler\ApplicationValidator $applicationValidator
   *   Validate Application data.
   * @param \Drupal\grants_handler\ApplicationStatusService $applicationStatusService
   *   Handle Application statuses.
   */
  public function __construct(
    Client $http_client,
    HelsinkiProfiiliUserData $helfi_helsinki_profiili_userdata,
    AtvService $atvService,
    AtvSchema $atvSchema,
    GrantsProfileService $grantsProfileService,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    Messenger $messenger,
    EventsService $eventsService,
    Connection $datababse,
    LanguageManager $languageManager,
    GrantsHandlerNavigationHelper $grantsFormNavigationHelper,
    ConfigFactoryInterface $configFactory,
    AccountProxyInterface $currentUser,
    TimeInterface $time,
    ApplicationValidator $applicationValidator,
    ApplicationStatusService $applicationStatusService,
    ApplicationDataService $applicationDataService,
    ApplicationInitService $applicationInitService
  ) {

    $this->httpClient = $http_client;
    $this->helfiHelsinkiProfiiliUserdata = $helfi_helsinki_profiili_userdata;
    $this->atvService = $atvService;
    $this->atvSchema = $atvSchema;
    $this->grantsProfileService = $grantsProfileService;

    $this->atvSchema->setSchema(getenv('ATV_SCHEMA_PATH'));

    $this->messenger = $messenger;
    $this->logger = $loggerChannelFactory->get('grants_application_handler');
    $this->eventsService = $eventsService;

    $this->endpoint = getenv('AVUSTUS2_ENDPOINT');
    $this->username = getenv('AVUSTUS2_USERNAME');
    $this->password = getenv('AVUSTUS2_PASSWORD');

    $this->newStatusHeader = '';
    $this->database = $datababse;
    $this->languageManager = $languageManager;
    $this->grantsHandlerNavigationHelper = $grantsFormNavigationHelper;
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->time = $time;

    $this->applicationValidator = $applicationValidator;
    $this->applicationStatusService = $applicationStatusService;
    $this->applicationStatuses = $this->applicationStatusService->getApplicationStatuses();
    $this->applicationDataService = $applicationDataService;
    $this->applicationInitService = $applicationInitService;
  }

  /**
   * @param \Drupal\grants_attachments\AttachmentHandler $attachmentHandler
   */
  public function setAttachmentHandler(AttachmentHandler $attachmentHandler): void {
    $this->attachmentHandler = $attachmentHandler;
  }

  /*
   * Static methods
   */

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
   * Checks if there is breaking changes in newer webform versions.
   *
   * Breakin changes in this context means any Avus2 changes, that makes
   * submitting older webform to fail.
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
    $parent = $latestApplicationForm->getThirdPartySetting('grants_metadata', 'parent');
    $hasBreakingChanges = $latestApplicationForm->getThirdPartySetting('grants_metadata', 'avus2BreakingChange');

    while (!empty($parent)) {

      $map[$parent] = $hasBreakingChanges;

      $res = \Drupal::entityTypeManager()
        ->getStorage('webform')
        ->loadByProperties([
          'uuid' => $parent,
        ]);

      $wf = reset($res);

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
   * Get submission object from local database & fill form data from ATV.
   *
   * Or if local submission is not found, create new and set data.
   *
   * @param string $applicationNumber
   *   String to try and parse submission id from. Ie GRANTS-DEV-00000098.
   * @param \Drupal\helfi_atv\AtvDocument|null $document
   *   Document to extract values from.
   * @param bool $refetch
   *   Force refetch from ATV.
   * @param bool $skipAccessCheck
   *   Should the access checks be skipped (For example, when using Admin UI).
   *
   * @return \Drupal\webform\Entity\WebformSubmission|null
   *   Webform submission.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Drupal\grants_mandate\CompanySelectException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public static function submissionObjectFromApplicationNumber(
    string $applicationNumber,
    AtvDocument $document = NULL,
    bool $refetch = FALSE,
    bool $skipAccessCheck = FALSE,
  ): ?WebformSubmission {

    $submissionSerial = self::getSerialFromApplicationNumber($applicationNumber);
    $webform = self::getWebformFromApplicationNumber($applicationNumber, TRUE);

    if (!$webform) {
      return NULL;
    }

    $webformIds = array_map(function ($element) {
      return $element->id();
    }, $webform);

    $result = \Drupal::entityTypeManager()
      ->getStorage('webform_submission')
      ->loadByProperties([
        'serial' => $submissionSerial,
        'webform_id' => $webformIds,
      ]);

    /** @var \Drupal\helfi_atv\AtvService $atvService */
    $atvService = \Drupal::service('helfi_atv.atv_service');

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');
    $selectedCompany = $grantsProfileService->getSelectedRoleData();

    /** @var \Drupal\grants_handler\MessageService $messageService */
    $messageService = \Drupal::service('grants_handler.message_service');

    // If no company selected, no mandates no access.
    if ($selectedCompany == NULL && !$skipAccessCheck) {
      throw new CompanySelectException('User not authorised');
    }

    if ($document == NULL) {
      $sParams = [
        'transaction_id' => $applicationNumber,
        'lookfor' => 'appenv:' . Helpers::getAppEnv(),
      ];

      $document = $atvService->searchDocuments(
        $sParams,
        $refetch
      );
      if (empty($document)) {
        throw new AtvDocumentNotFoundException('Document not found');
      }
      $document = reset($document);
    }

    // If there's no local submission with given serial
    // we can actually create that object on the fly and use that for editing.
    if (empty($result)) {
      $webform = self::getWebformFromApplicationNumber($applicationNumber);
      if ($webform) {
        $submissionObject = WebformSubmission::create(['webform_id' => $webform->id()]);
        $submissionObject->set('serial', $submissionSerial);

        // Lets mark that we don't want to generate new application
        // number, as we just assigned the serial from ATV application id.
        // check GrantsHandler@preSave.
        WebformSubmissionNotesHelper::setValue(
          $submissionObject,
          'skip_available_number_check',
          TRUE
        );
        if ($document->getStatus() == 'DRAFT') {
          $submissionObject->set('in_draft', TRUE);
        }
        $submissionObject->save();
      }
    }
    else {
      $submissionObject = reset($result);
    }
    if (!empty($submissionObject)) {

      // @todo update to normal method or fix other way
      $dataDefinition = self::getDataDefinition($document->getType());

      $sData = DocumentContentMapper::documentContentToTypedData(
        $document->getContent(),
        $dataDefinition,
        $document->getMetadata()
      );

      $sData['messages'] = $messageService->parseMessages($sData);

      // Set submission data from parsed mapper.
      $submissionObject->setData($sData);

      return $submissionObject;
    }
    return NULL;
  }

  /**
   * Extract serial numbor from application number string.
   *
   * @param string $applicationNumber
   *   Application number.
   * @param bool $refetch
   *   Force refetch from ATV.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   ATV Document
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   */
  public static function atvDocumentFromApplicationNumber(
    string $applicationNumber,
    bool $refetch = FALSE
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
   * Atv document holding this application.
   *
   * @param string $transactionId
   *   Id of the transaction.
   * @param bool $refetch
   *   Force atv document fetch.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   FEtched document.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getAtvDocument(string $transactionId, bool $refetch = FALSE): AtvDocument {

    if (!isset($this->atvDocument) || $refetch === TRUE) {
      $sParams = [
        'transaction_id' => $transactionId,
        'lookfor' => 'appenv:' . Helpers::getAppEnv(),
      ];

      $res = $this->atvService->searchDocuments($sParams);
      $this->atvDocument = reset($res);
    }

    return $this->atvDocument;
  }

  /**
   * Method to initialise application document in ATV. Create & save.
   *
   * If data is given, use that data to copy things to new application.
   *
   * @param string $webform_id
   *   Id of a webform of created application.
   * @param array $submissionData
   *   If we want to pass any initial data for new application, do it with
   *   this.
   *   Must be like webform data.
   *
   * @return \Drupal\webform\Entity\WebformSubmission
   *   Newly created application content.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException|\Drupal\helfi_helsinki_profiili\ProfileDataException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function initApplication(string $webform_id, array $submissionData = []): WebformSubmission {

    $webform = Webform::load($webform_id);
    $userData = $this->helfiHelsinkiProfiiliUserdata->getUserData();
    $userProfileData = $this->helfiHelsinkiProfiiliUserdata->getUserProfileData();

    if ($userData == NULL) {
      // We absolutely cannot create new application without user data.
      throw new ProfileDataException('No Helsinki profile data found');
    }
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    $companyData = $this->grantsProfileService->getGrantsProfileContent($selectedCompany);

    // If we've given data to work with, clear it for copying.
    if (empty($submissionData)) {
      $copy = FALSE;
    }
    else {
      $copy = TRUE;
      $submissionData = Helpers::clearDataForCopying($submissionData);
      $budgetInfoKeys = $this->getBudgetInfoKeysForCopying($submissionData);
    }

    // Set.
    $submissionData['application_type_id'] = $webform->getThirdPartySetting('grants_metadata', 'applicationTypeID');
    $submissionData['application_type'] = $webform->getThirdPartySetting('grants_metadata', 'applicationType');
    $submissionData['applicant_type'] = $this->grantsProfileService->getApplicantType();
    $submissionData['status'] = $this->applicationStatuses['DRAFT'];
    $submissionData['company_number'] = $selectedCompany['identifier'];
    $submissionData['business_purpose'] = $companyData['businessPurpose'] ?? '';

    if ($selectedCompany["type"] === 'registered_community') {
      $submissionData['hakijan_tiedot'] = [
        'applicantType' => $selectedCompany["type"],
        'applicant_type' => $selectedCompany["type"],
        'communityOfficialName' => $selectedCompany["name"],
        'companyNumber' => $selectedCompany["identifier"],
        'registrationDate' => $companyData["registrationDate"],
        'home' => $companyData["companyHome"],
        'communityOfficialNameShort' => $companyData["companyNameShort"],
        'foundingYear' => $companyData["foundingYear"],
        'homePage' => $companyData["companyHomePage"],
      ];
    }
    if ($selectedCompany["type"] === 'unregistered_community') {
      $submissionData['hakijan_tiedot'] = [
        'applicantType' => $selectedCompany["type"],
        'applicant_type' => $selectedCompany["type"],
        'communityOfficialName' => $companyData["companyName"],
        'firstname' => $userData["given_name"],
        'lastname' => $userData["family_name"],
        'socialSecurityNumber' => $userProfileData["myProfile"]["verifiedPersonalInformation"]["nationalIdentificationNumber"],
        'email' => $userData["email"],
        'street' => $companyData["addresses"][0]["street"],
        'city' => $companyData["addresses"][0]["city"],
        'postCode' => $companyData["addresses"][0]["postCode"],
        'country' => $companyData["addresses"][0]["country"],
      ];
    }
    if ($selectedCompany["type"] === 'private_person') {
      $submissionData['hakijan_tiedot'] = [
        'applicantType' => $selectedCompany["type"],
        'applicant_type' => $selectedCompany["type"],
        'firstname' => $userData["given_name"],
        'lastname' => $userData["family_name"],
        'socialSecurityNumber' => $userProfileData["myProfile"]["verifiedPersonalInformation"]["nationalIdentificationNumber"] ?? '',
        'email' => $userData["email"],
        'street' => $companyData["addresses"][0]["street"] ?? '',
        'city' => $companyData["addresses"][0]["city"] ?? '',
        'postCode' => $companyData["addresses"][0]["postCode"] ?? '',
        'country' => $companyData["addresses"][0]["country"] ?? '',
      ];
    }
    // Data must match the format of typed data, not the webform format.
    // Community address data defined in
    // grants_metadata/src/TypedData/Definition/ApplicationDefinitionTrait.
    if (isset($submissionData["community_address"]["community_street"]) &&
      !empty($submissionData["community_address"]["community_street"])) {
      $submissionData["community_street"] = $submissionData["community_address"]["community_street"];
    }
    if (isset($submissionData["community_address"]["community_city"]) && !empty($submissionData["community_address"]["community_city"])) {
      $submissionData["community_city"] = $submissionData["community_address"]["community_city"];
    }
    if (isset($submissionData["community_address"]["community_post_code"]) &&
      !empty($submissionData["community_address"]["community_post_code"])) {
      $submissionData["community_post_code"] = $submissionData["community_address"]["community_post_code"];
    }
    if (isset($submissionData["community_address"]["community_country"]) &&
      !empty($submissionData["community_address"]["community_country"])) {
      $submissionData["community_country"] = $submissionData["community_address"]["community_country"];
    }

    // Copy budget component fields into budgetInfo.
    if ($copy && isset($budgetInfoKeys)) {
      foreach ($budgetInfoKeys as $budgetKey) {
        if (isset($submissionData[$budgetKey])) {
          $submissionData['budgetInfo'][$budgetKey] = $submissionData[$budgetKey];
        }
      }
    }

    try {
      // Merge sender details to new stuff.
      $submissionData = array_merge($submissionData, $this->applicationInitService->parseSenderDetails());
    }
    catch (ApplicationException $e) {
      $this->logger->error('Sender details parsing threw error: @error', ['@error' => $e->getMessage()]);
    }

    // Set form timestamp to current time.
    // apparently this is always set to latest submission.
    $dt = new \DateTime();
    $dt->setTimezone(new \DateTimeZone('Europe/Helsinki'));
    $submissionData['form_timestamp'] = $dt->format('Y-m-d\TH:i:s');
    $submissionData['form_timestamp_created'] = $dt->format('Y-m-d\TH:i:s');

    $submissionObject = WebformSubmission::create([
      'webform_id' => $webform->id(),
      'draft' => TRUE,
    ]);
    $submissionObject->set('in_draft', TRUE);
    $submissionObject->save();

    $applicationNumber = ApplicationHandler::createApplicationNumber($submissionObject);
    $submissionData['application_number'] = $applicationNumber;

    $atvDocument = AtvDocument::create([]);
    $atvDocument->setTransactionId($applicationNumber);
    $atvDocument->setStatus($this->applicationStatuses['DRAFT']);
    $atvDocument->setType($submissionData['application_type']);
    $atvDocument->setService(getenv('ATV_SERVICE'));
    $atvDocument->setUserId($userData['sub']);
    $atvDocument->setTosFunctionId(getenv('ATV_TOS_FUNCTION_ID'));
    $atvDocument->setTosRecordId(getenv('ATV_TOS_RECORD_ID'));
    if ($submissionData['applicant_type'] == 'registered_community') {
      $atvDocument->setBusinessId($selectedCompany['identifier']);
    }
    $atvDocument->setDraft(TRUE);
    $atvDocument->setDeletable(FALSE);

    $humanReadableTypes = [
      'en' => $this->getWebformTitle($webform_id, 'en'),
      'fi' => $this->getWebformTitle($webform_id, 'fi'),
      'sv' => $this->getWebformTitle($webform_id, 'sv'),
    ];

    $atvDocument->setHumanReadableType($humanReadableTypes);

    $atvDocument->setMetadata([
      'appenv' => Helpers::getAppEnv(),
      // Hmm, maybe no save id at this point?
      'saveid' => $copy ? 'copiedSave' : 'initialSave',
      'applicationnumber' => $applicationNumber,
      'language' => $this->languageManager->getCurrentLanguage()->getId(),
      'applicant_type' => $selectedCompany['type'],
      'applicant_id' => $selectedCompany['identifier'],
      'form_uuid' => $webform->uuid(),
    ]);

    // Do data conversion.
    $typeData = $this->applicationDataService->webformToTypedData($submissionData);

    $appDocumentContent = $this->atvSchema->typedDataToDocumentContent(
      $typeData,
      $submissionObject,
      $submissionData);

    $atvDocument->setContent($appDocumentContent);

    // Post the initial version of the document to ATV.
    $newDocument = $this->atvService->postDocument($atvDocument);

    // If we are copying an application, then call handleBankAccountCopying().
    // This will patch the already existing $newDocument with a bank account
    // confirmation file.
    if ($copy) {
      $newDocument = $this->handleBankAccountCopying(
        $newDocument,
        $submissionObject,
        $submissionData
      );
    }

    $dataDefinitionKeys = $this->applicationDataService->getDataDefinitionClass($submissionData['application_type']);
    $dataDefinition = $dataDefinitionKeys['definitionClass']::create($dataDefinitionKeys['definitionId']);

    $submissionObject->setData(DocumentContentMapper::documentContentToTypedData($newDocument->getContent(), $dataDefinition));
    return $submissionObject;
  }

  /**
   * Handle application upload directly to ATV.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $applicationData
   *   Application data in typed data object.
   * @param string $applicationNumber
   *   Application number.
   * @param array $submittedFormData
   *   Actual form data from submission.
   *
   * @return \Drupal\helfi_atv\AtvDocument|bool|null
   *   Result of the upload.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Drupal\grants_mandate\CompanySelectException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException|\Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function handleApplicationUploadToAtv(
    TypedDataInterface $applicationData,
    string $applicationNumber,
    array $submittedFormData
  ): AtvDocument|bool|null {
    $webform_submission = ApplicationHandler::submissionObjectFromApplicationNumber($applicationNumber);
    $appDocumentContent =
      $this->atvSchema->typedDataToDocumentContent(
        $applicationData,
        $webform_submission,
        $submittedFormData);

    $atvDocument = $this->getAtvDocument($applicationNumber, TRUE);
    // Set language for the application.
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $atvDocument->addMetadata('language', $language);
    try {
      $saveId = $this->logSubmissionSaveid(NULL, $applicationNumber);
      $atvDocument->addMetadata('saveid', $saveId);
    }
    catch (\Exception $e) {
    }

    $atvDocument->setContent($appDocumentContent);

    if ($this->newStatusHeader && $this->newStatusHeader != '') {
      $atvDocument->setStatus($this->newStatusHeader);
    }

    $updatedDocument = $this->atvService->patchDocument(
      $atvDocument->getId(),
      $atvDocument->toArray()
    );

    $this->atvDocument = $updatedDocument;

    return $updatedDocument;

  }

  /**
   * Take in typed data object, export to Avus2 document structure & upload.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $applicationData
   *   Typed data object.
   * @param string $applicationNumber
   *   Used application number.
   * @param array $submittedFormData
   *   Data from form.
   *
   * @return bool
   *   Result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Drupal\grants_mandate\CompanySelectException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function handleApplicationUploadViaIntegration(
    TypedDataInterface $applicationData,
    string $applicationNumber,
    array $submittedFormData
  ): bool {
    $tOpts = ['context' => 'grants_handler'];

    /*
     * Save application data once more as a DRAFT to ATV to make sure we have
     * the most recent version available even if integration fails
     * for some reason.
     */
    $this->handleApplicationUploadToAtv($applicationData, $applicationNumber, $submittedFormData);

    /*
     * I'm not sure we need to do anything else, but I'll leave this comment
     * here when we come debugging weird behavior
     */

    $webformSubmission = ApplicationHandler::submissionObjectFromApplicationNumber($applicationNumber);
    $appDocument = $this->atvSchema->typedDataToDocumentContent($applicationData, $webformSubmission, $submittedFormData);
    $myJSON = Json::encode($appDocument);

    if ($this->isDebug()) {
      $t_args = [
        '%endpoint' => $this->endpoint,
      ];
      $this->logger
        ->debug('DEBUG: Endpoint: %endpoint', $t_args);

      $t_args = [
        '%myJSON' => $myJSON,
      ];
      if (Helpers::getAppEnv() !== 'PROD') {
        $this->logger
          ->debug('DEBUG: Sent JSON: %myJSON', $t_args);
      }
    }

    try {

      $headers = [];
      if ($this->newStatusHeader && $this->newStatusHeader != '') {
        $headers['X-Case-Status'] = $this->newStatusHeader;
      }

      // Current environment as a header to be added to meta -fields.
      $headers['X-hki-appEnv'] = Helpers::getAppEnv();
      // Set application number to meta as well to enable better searches.
      $headers['X-hki-applicationNumber'] = $applicationNumber;
      // Set new saveid and save it to db.
      $headers['X-hki-saveId'] = $this->logSubmissionSaveid(NULL, $applicationNumber);

      $res = $this->httpClient->post($this->endpoint, [
        'auth' => [
          $this->username,
          $this->password,
          "Basic",
        ],
        'body' => $myJSON,
        'headers' => $headers,
      ]);

      $status = $res->getStatusCode();

      if ($this->isDebug()) {
        $t_args = [
          '@status' => $status,
        ];
        $this->logger
          ->debug('Data sent to integration, response status: @status', $t_args);
      }

      if ($status === 200) {
        $this->atvService->clearCache($applicationNumber);
        return TRUE;
      }
      else {
        return FALSE;
      }
    }
    catch (\Exception $e) {
      $this->messenger->addError($this->t('Application saving failed, error has been logged.', [], $tOpts));
      $this->logger->error('Error saving application: %msg', ['%msg' => $e->getMessage()]);
      return FALSE;
    }
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
    $userProfileData = $this->helfiHelsinkiProfiiliUserdata->getUserProfileData();
    $userData = $this->helfiHelsinkiProfiiliUserdata->getUserData();

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
   * Access method to clear cache in atv service.
   *
   * @param string $applicationNumber
   *   Application number.
   */
  public function clearCache(string $applicationNumber): void {
    $this->atvService->clearCache($applicationNumber);
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
   * Get company applications, either sorted by finished or all in one array.
   *
   * @param array $selectedCompany
   *   Company data.
   * @param string $appEnv
   *   Environment.
   * @param bool $sortByFinished
   *   When true, results will be sorted by finished status.
   * @param bool $sortByStatus
   *   Sort by application status.
   * @param string $themeHook
   *   Use theme hook to render content. Set this to theme hook wanted to use,
   *   and sen #submission to webform submission.
   *
   * @return array
   *   Submissions in array.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Drupal\grants_mandate\CompanySelectException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public static function getCompanyApplications(
    array $selectedCompany,
    string $appEnv,
    bool $sortByFinished = FALSE,
    bool $sortByStatus = FALSE,
    string $themeHook = ''): array {

    /** @var \Drupal\helfi_atv\AtvService $atvService */
    $atvService = \Drupal::service('helfi_atv.atv_service');

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');

    /** @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliService */
    $helsinkiProfiiliService = \Drupal::service('helfi_helsinki_profiili.userdata');
    $userData = $helsinkiProfiiliService->getUserData();

    /** @var \Drupal\grants_handler\ApplicationStatusService $applicationStatusService */
    $applicationStatusService = \Drupal::service('grants_handler.application_status_service');

    /** @var \Drupal\grants_handler\MessageService $messageService */
    $messageService = \Drupal::service('grants_handler.message_service');

    $applications = [];
    $finished = [];
    $unfinished = [];

    $selectedRoleData = $grantsProfileService->getSelectedRoleData();

    $lookForAppEnv = 'appenv:' . $appEnv;

    if ($selectedRoleData['type'] == 'private_person') {
      $searchParams = [
        'service' => 'AvustushakemusIntegraatio',
        'user_id' => $userData['sub'],
        'lookfor' => $lookForAppEnv . ',applicant_type:' . $selectedRoleData['type'],
      ];
    }
    elseif ($selectedRoleData['type'] == 'unregistered_community') {
      $searchParams = [
        'service' => 'AvustushakemusIntegraatio',
        'user_id' => $userData['sub'],
        'lookfor' => $lookForAppEnv . ',applicant_type:' . $selectedRoleData['type'] .
        ',applicant_id:' . $selectedRoleData['identifier'],
      ];
    }
    else {
      $searchParams = [
        'service' => 'AvustushakemusIntegraatio',
        'business_id' => $selectedCompany['identifier'],
        'lookfor' => $lookForAppEnv . ',applicant_type:' . $selectedRoleData['type'],
      ];
    }

    $applicationDocuments = $atvService->searchDocuments($searchParams);

    /**
     * Create rows for table.
     *
     * @var  \Drupal\helfi_atv\AtvDocument $document
     */
    foreach ($applicationDocuments as $document) {
      // Make sure the type is acceptable one.
      $docArray = $document->toArray();
      $id = AtvSchema::extractDataForWebForm(
        $docArray['content'], ['applicationNumber']
      );

      if (empty($id['applicationNumber'])) {
        continue;
      }

      if (array_key_exists($document->getType(), Helpers::getApplicationTypes())) {
        try {

          // Convert the data.
          // @todo fix static method.
          $dataDefinition = self::getDataDefinition($document->getType());
          $submissionData = DocumentContentMapper::documentContentToTypedData(
            $document->getContent(),
            $dataDefinition,
            $document->getMetadata()
          );

          $metaData = $document->getMetadata();

          // Load the webform submission ID.
          $applicationNumber = $submissionData['application_number'];
          $serial = self::getSerialFromApplicationNumber($applicationNumber);

          $webformUuidExists = isset($metaData['form_uuid']) && !empty($metaData['form_uuid']);
          $webform = $webformUuidExists
            ? self::getWebformByUuid($metaData['form_uuid'], $applicationNumber)
            : self::getWebformFromApplicationNumber($applicationNumber);

          if (!$webform || !$serial) {
            continue;
          }

          $submissionId = self::getSubmissionIdWithSerialAndWebformId($serial, $webform->id(), $document);
        }
        catch (\Throwable $e) {
          \Drupal::logger('application_handler')->error(
            'Failed to get submission object from application number. Submission skipped in application listing. ID: @id Error: @error',
            [
              '@error' => $e->getMessage(),
              '@id'    => $document->getTransactionId(),
            ]
          );
          continue;
        }

        if (!$submissionData || !$submissionId) {
          continue;
        }

        $submissionData['messages'] = $messageService->parseMessages($submissionData);
        $submission = [
          '#theme' => $themeHook,
          '#submission' => $submissionData,
          '#document' => $document,
          '#webform' => $webform,
          '#submission_id' => $submissionId,
        ];

        $ts = strtotime($submissionData['form_timestamp_created'] ?? '');
        if ($sortByFinished === TRUE) {
          if ($applicationStatusService->isSubmissionFinished($submission)) {
            $finished[$ts] = $submission;
          }
          else {
            $unfinished[$ts] = $submission;
          }
        }
        elseif ($sortByStatus === TRUE) {
          $applications[$submissionData['status']][$ts] = $submission;
        }
        else {
          $applications[$ts] = $submission;
        }
      }
    }

    if ($sortByFinished === TRUE) {
      ksort($finished);
      ksort($unfinished);
      return [
        'finished' => $finished,
        'unifinished' => $unfinished,
      ];
    }
    elseif ($sortByStatus === TRUE) {
      $applicationsSorted = [];
      foreach ($applications as $key => $value) {
        krsort($value);
        $applicationsSorted[$key] = $value;
      }
      ksort($applicationsSorted);
      return $applicationsSorted;
    }
    else {
      ksort($applications);
      return $applications;
    }
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
  protected static function getSubmissionIdWithSerialAndWebformId(
    string $serial,
    string $webformId,
    AtvDocument $document): string {
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
    AtvDocument $document): WebformSubmission {

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
   * @param string $saveId
   *   Submission save id.
   *
   * @throws \Exception
   */
  public function logSubmissionSaveid(
    ?WebformSubmissionInterface $webform_submission,
    string $applicationNumber,
    string $saveId = ''
  ): string {

    if (empty($saveId)) {
      $saveId = Uuid::uuid4()->toString();
    }

    if ($webform_submission == NULL) {
      $webform_submission = ApplicationHandler::submissionObjectFromApplicationNumber($applicationNumber);
    }

    $userData = $this->helfiHelsinkiProfiiliUserdata->getUserData();
    $fields = [
      'webform_id' => ($webform_submission) ? $webform_submission->getWebform()
        ->id() : '',
      'sid' => ($webform_submission) ? $webform_submission->id() : 0,
      'handler_id' => self::HANDLER_ID,
      'application_number' => $applicationNumber,
      'saveid' => $saveId,
      'uid' => $this->currentUser->id(),
      'user_uuid' => $userData['sub'] ?? '',
      'timestamp' => (string) $this->time->getRequestTime(),
    ];

    $query = $this->database->insert(self::TABLE, $fields);
    $query->fields($fields)->execute();

    return $saveId;

  }

  /**
   * Gets webform & submission with data and determines access.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Submission object.
   *
   * @return bool
   *   Access status
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function singleSubmissionAccess(WebformSubmission $webform_submission): bool {

    // If we have account number, load details.
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    if (empty($selectedCompany)) {
      throw new CompanySelectException('User not authorised');
    }
    $grantsProfileDocument = $this->grantsProfileService->getGrantsProfile($selectedCompany);
    $profileContent = $grantsProfileDocument->getContent();
    $webformData = $webform_submission->getData();
    $companyType = $selectedCompany['type'] ?? NULL;
    if (!$companyType || !$webformData) {
      return FALSE;
    }

    if (!isset($webformData['application_number'])) {
      return FALSE;
    }

    try {
      $atvDoc = ApplicationHandler::atvDocumentFromApplicationNumber($webformData['application_number']);
    }
    catch (AtvDocumentNotFoundException $e) {
      return FALSE;
    }
    $atvMetadata = $atvDoc->getMetadata();
    // Mismatch between profile and application applicant type.
    if ($companyType !== $webformData['hakijan_tiedot']['applicantType']) {
      return FALSE;
    }
    elseif ($companyType == "registered_community" && $profileContent['businessId'] !== $atvDoc->getBusinessId()) {
      return FALSE;
    }
    elseif ($companyType === "private_person" && $profileContent['businessId'] !== $atvDoc->getUserId()) {
      return FALSE;
    }
    elseif ($companyType === "unregistered_community" && $profileContent['businessId'] !== $atvMetadata['applicant_id']) {
      return FALSE;
    }

    return TRUE;
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
