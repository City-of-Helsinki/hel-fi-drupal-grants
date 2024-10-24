<?php

declare(strict_types=1);

namespace Drupal\grants_handler;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\grants_mandate\CompanySelectException;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_metadata\DocumentContentMapper;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Class to get things related to applications.
 */
final class ApplicationGetterService {

  /**
   * Access to profile data.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Atv document holding this application.
   *
   * @var \Drupal\helfi_atv\AtvDocument
   */
  protected AtvDocument $atvDocument;

  /**
   * Log errors.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected EntityStorageInterface $storage;

  /**
   * Constructs an ApplicationGetterService object.
   */
  public function __construct(
    private readonly AtvService $helfiAtvAtvService,
    private readonly HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata,
    private readonly ApplicationStatusService $grantsHandlerApplicationStatusService,
    private readonly MessageService $grantsHandlerMessageService,
    private readonly LoggerChannelFactoryInterface $loggerChannelFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->logger = $loggerChannelFactory->get('application_getter_service');
    try {
      $this->storage = $entityTypeManager->getStorage('webform_submission');
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
    }
  }

  /**
   * Set grants profile service.
   *
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Grants profile service.
   */
  public function setGrantsProfileService(GrantsProfileService $grantsProfileService): void {
    $this->grantsProfileService = $grantsProfileService;
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

      $res = $this->helfiAtvAtvService->searchDocuments($sParams);
      $this->atvDocument = reset($res);
    }

    return $this->atvDocument;
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
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getCompanyApplications(
    array $selectedCompany,
    string $appEnv,
    bool $sortByFinished = FALSE,
    bool $sortByStatus = FALSE,
    string $themeHook = '',
  ): array {

    $userData = $this->helfiHelsinkiProfiiliUserdata->getUserData();

    $applications = [];
    $finished = [];
    $unfinished = [];

    $selectedRoleData = $this->grantsProfileService->getSelectedRoleData();

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

    $applicationDocuments = $this->helfiAtvAtvService->searchDocuments($searchParams);

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
          $dataDefinition = ApplicationHelpers::getDataDefinition($document->getType());
          $submissionData = DocumentContentMapper::documentContentToTypedData(
            $document->getContent(),
            $dataDefinition,
            $document->getMetadata()
          );

          $metaData = $document->getMetadata();

          // Load the webform submission ID.
          $applicationNumber = $submissionData['application_number'];
          $serial = ApplicationHelpers::getSerialFromApplicationNumber($applicationNumber);

          $webformUuidExists = isset($metaData['form_uuid']) && !empty($metaData['form_uuid']);
          $webform = $webformUuidExists
            ? ApplicationHelpers::getWebformByUuid($metaData['form_uuid'], $applicationNumber)
            : ApplicationHelpers::getWebformFromApplicationNumber($applicationNumber);

          if (!$webform || !$serial) {
            continue;
          }

          $submissionId = ApplicationHelpers::getSubmissionIdWithSerialAndWebformId($serial, $webform->id(), $document);
        }
        catch (\Throwable $e) {
          $this->logger->error(
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

        $submissionData['messages'] = $this->grantsHandlerMessageService->parseMessages($submissionData);
        $submission = [
          '#theme' => $themeHook,
          '#submission' => $submissionData,
          '#document' => $document,
          '#webform' => $webform,
          '#submission_id' => $submissionId,
        ];

        $ts = strtotime($submissionData['form_timestamp_created'] ?? '');
        if ($sortByFinished === TRUE) {
          if ($this->grantsHandlerApplicationStatusService->isSubmissionFinished($submission)) {
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
  public function submissionObjectFromApplicationNumber(
    string $applicationNumber,
    AtvDocument $document = NULL,
    bool $refetch = FALSE,
    bool $skipAccessCheck = FALSE,
  ): ?WebformSubmission {

    $submissionSerial = ApplicationHelpers::getSerialFromApplicationNumber($applicationNumber);
    $webform = ApplicationHelpers::getWebformFromApplicationNumber($applicationNumber, TRUE);

    if (!$webform) {
      return NULL;
    }

    $webformIds = array_map(function ($element) {
      return $element->id();
    }, $webform);

    $result = $this->storage
      ->loadByProperties([
        'serial' => $submissionSerial,
        'webform_id' => $webformIds,
      ]);

    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();

    // If no company selected, no mandates no access.
    if ($selectedCompany == NULL && !$skipAccessCheck) {
      throw new CompanySelectException('User not authorised');
    }

    if ($document == NULL) {
      $sParams = [
        'transaction_id' => $applicationNumber,
        'lookfor' => 'appenv:' . Helpers::getAppEnv(),
      ];

      $document = $this->helfiAtvAtvService->searchDocuments(
        $sParams,
        $refetch
      );
      if (empty($document)) {
        throw new AtvDocumentNotFoundException('Document not found');
      }
      $document = reset($document);
    }

    $submissionObject = NULL;

    // If there's no local submission with given serial
    // we can actually create that object on the fly and use that for editing.
    if (empty($result)) {
      $webform = ApplicationHelpers::getWebformFromApplicationNumber($applicationNumber);
      if ($webform) {
        /** @var \Drupal\webform\Entity\WebformSubmission $submissionObject */
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
      /** @var \Drupal\webform\Entity\WebformSubmission $submissionObject */
      $submissionObject = reset($result);
    }
    if ($submissionObject) {

      $dataDefinition = ApplicationHelpers::getDataDefinition($document->getType());

      $sData = DocumentContentMapper::documentContentToTypedData(
        $document->getContent(),
        $dataDefinition,
        $document->getMetadata()
      );

      $sData['messages'] = $this->grantsHandlerMessageService->parseMessages($sData);

      // Set submission data from parsed mapper.
      $submissionObject->setData($sData);

      return $submissionObject;
    }
    return NULL;
  }

}
