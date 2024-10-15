<?php

declare(strict_types=1);

namespace Drupal\grants_handler;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\grants_attachments\AttachmentFixerService;
use Drupal\grants_metadata\AtvSchema;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use GuzzleHttp\Client as HttpClient;

/**
 * Class to handle application uploads.
 */
final class ApplicationUploaderService {

  use DebuggableTrait;
  use StringTranslationTrait;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

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
   * AtvDocument object.
   *
   * @var \Drupal\helfi_atv\AtvDocument
   */
  protected AtvDocument $atvDocument;

  /**
   * Constructs an ApplicationUploaderService object.
   */
  public function __construct(
    private readonly AtvService $helfiAtvAtvService,
    private readonly AtvSchema $helfiAtvAtvSchema,
    private readonly ApplicationStatusService $grantsHandlerApplicationStatusService,
    private readonly MessageService $grantsHandlerMessageService,
    private readonly HttpClient $httpClient,
    private readonly LoggerChannelFactoryInterface $loggerChannelFactory,
    private readonly LanguageManagerInterface $languageManager,
    private readonly MessengerInterface $messenger,
    private readonly ApplicationGetterService $applicationGetterService,
    private readonly HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata,
    private readonly AttachmentFixerService $attachmentFixerService,
  ) {
    $this->logger = $this->loggerChannelFactory->get('application_uploader_service');

    if ($schema = getenv('ATV_SCHEMA_PATH')) {
      $this->helfiAtvAtvSchema->setSchema($schema);
    }

    $this->endpoint = getenv('AVUSTUS2_ENDPOINT') ?: '';
    $this->username = getenv('AVUSTUS2_USERNAME') ?: '';
    $this->password = getenv('AVUSTUS2_PASSWORD') ?: '';
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
    array $submittedFormData,
  ): AtvDocument|bool|null {
    $webform_submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($applicationNumber);
    $appDocumentContent =
      $this->helfiAtvAtvSchema->typedDataToDocumentContent(
        $applicationData,
        $webform_submission,
        $submittedFormData);

    // Make sure we have most recent version of the document.
    $atvDocument = $this->applicationGetterService->getAtvDocument($applicationNumber, TRUE);
    // Set language for the application.
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $atvDocument->addMetadata('language', $language);
    try {
      $userData = $this->helfiHelsinkiProfiiliUserdata->getUserData();
      $saveId = ApplicationHelpers::logSubmissionSaveid(NULL, $applicationNumber, $userData);
      $atvDocument->addMetadata('saveid', $saveId);
    }
    catch (\Exception $e) {
    }

    $atvDocument->setContent($appDocumentContent);

    // Try to fix all possibly missing items in attachments.
    $atvDocument = $this->attachmentFixerService->fixAttachmentsOnApplication($atvDocument);

    $newHeader = $this->grantsHandlerApplicationStatusService->getNewStatusHeader();

    if ($newHeader && $newHeader != '') {
      $atvDocument->setStatus($newHeader);
    }

    $updatedDocument = $this->helfiAtvAtvService->patchDocument(
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
    array $submittedFormData,
  ): bool {
    $tOpts = ['context' => 'grants_handler'];

    /*
     * Save application data once more to ATV to make sure we have
     * the most recent version available even if integration fails
     * for some reason.
     */
    $updatedDocumentFromAtv = $this->handleApplicationUploadToAtv($applicationData, $applicationNumber, $submittedFormData);
    $myJSON = Json::encode($updatedDocumentFromAtv->getContent());

    // No matter what the debug value is, we do NOT log json in PROD.
    if ($this->isDebug() && Helpers::getAppEnv() !== 'PROD') {
      $t_args = [
        '%endpoint' => $this->endpoint,
      ];
      $this->logger
        ->debug('DEBUG: Endpoint: %endpoint', $t_args);

      $t_args = [
        '%myJSON' => $myJSON,
      ];

      $this->logger
        ->debug('DEBUG: Sent JSON: %myJSON', $t_args);
    }

    try {
      $headers = [];

      $headers['X-Case-Status'] = $updatedDocumentFromAtv->getStatus();

      // We set the data source for integration to be used in controlling
      // application testing in problematic cases.
      $headers['X-hki-UpdateSource'] = 'USER';

      // Current environment as a header to be added to meta -fields.
      $headers['X-hki-appEnv'] = Helpers::getAppEnv();
      // Set application number to meta as well to enable better searches.
      $headers['X-hki-applicationNumber'] = $applicationNumber;

      // Set new saveid and save it to db.
      $headers['X-hki-saveId'] = ApplicationHelpers::logSubmissionSaveid(
        NULL,
        $applicationNumber,
        $this->helfiHelsinkiProfiiliUserdata->getUserData()
      );

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
        $this->clearCache($applicationNumber);
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
   * Access method to clear cache in atv service.
   *
   * @param string $applicationNumber
   *   Application number.
   */
  public function clearCache(string $applicationNumber): void {
    $this->helfiAtvAtvService->clearCache($applicationNumber);
  }

}
