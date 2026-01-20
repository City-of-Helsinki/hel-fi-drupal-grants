<?php

declare(strict_types=1);

namespace Drupal\grants_handler;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_mandate\CompanySelectException;
use Drupal\grants_metadata\DocumentContentMapper;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Class to get things related to applications.
 */
final class ApplicationGetterService implements ApplicationGetterServiceInterface {

  /**
   * Access to profile data.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Log errors.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Loaded submissions in array to prevent multiple loads.
   *
   * @var array
   */
  protected array $submissions = [];

  public function __construct(
    private readonly AtvService $helfiAtvAtvService,
    private readonly HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata,
    private readonly ApplicationStatusService $grantsHandlerApplicationStatusService,
    private readonly MessageService $grantsHandlerMessageService,
    private readonly LoggerChannelFactoryInterface $loggerChannelFactory,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly ModuleHandlerInterface $moduleHandler,
  ) {
    $this->logger = $loggerChannelFactory->get('application_getter_service');
  }

  /**
   * {@inheritdoc}
   */
  public function setGrantsProfileService(GrantsProfileService $grantsProfileService): void {
    $this->grantsProfileService = $grantsProfileService;
  }

  /**
   * {@inheritdoc}
   */
  public function getAtvDocument(string $transactionId, bool $refetch = FALSE): ?AtvDocument {
    $sParams = [
      'transaction_id' => $transactionId,
      'lookfor' => 'appenv:' . Helpers::getAppEnv(),
    ];

    try {
      $result = $this->helfiAtvAtvService->searchDocuments($sParams, $refetch);
    }
    catch (AtvDocumentNotFoundException | AtvFailedToConnectException | TokenExpiredException | GuzzleException $e) {
      $this->logger->error(
        'Failed to get document from ATV. Error: @error',
        ['@error' => $e->getMessage()]
      );
      return NULL;
    }
    return reset($result);
  }

  /**
   * {@inheritdoc}
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
    $missing_delete_after = FALSE;
    $submitted_missing_delete_after = FALSE;

    // Create rows for table.
    foreach ($applicationDocuments as $document) {
      $submission_entity = NULL;
      $applicationNumber = $document->getTransactionId();

      // Check if any of the drafts are missing delete after.
      if (
        !$missing_delete_after &&
        $document->getStatus() === 'DRAFT' &&
        $document->getDeleteAfter() === NULL
      ) {
        $missing_delete_after = TRUE;
      }

      // Also check the submitted application for missing delete_after.
      if (
        !$missing_delete_after &&
        $document->getStatus() != 'DRAFT' &&
        $document->getDeleteAfter() === NULL
      ) {
        $submitted_missing_delete_after = TRUE;
      }

      if (array_key_exists($document->getType(), Helpers::getApplicationTypes())) {
        // Must check both react form and the webform submission.
        try {
          $submission = NULL;
          if ($this->moduleHandler->moduleExists('grants_application')) {
            $submission = $this->getReactFormApplicationSubmission($applicationNumber);
          }
          if ($submission) {
            $submission_entity = $submission;
          }
          else {
            $submission = $this->submissionObjectFromApplicationNumber($applicationNumber, $document, FALSE, TRUE);
          }
        }
        catch (\Throwable $e) {
          $this->logger->error(
            'Failed to get submission object from application number. Submission skipped in application listing. ID: @id Error: @error',
            [
              '@error' => $e->getMessage(),
              '@id' => $document->getTransactionId(),
            ]
          );
          continue;
        }

        $submissionData = $submission->getData();

        // Add value for oma-asiointi listing.
        if ($submission_entity) {
          $submissionData['status'] = $document->getStatus();
          // $submissionData['messages'] = $document->getMessages();
        }

        $webform = $submission->getWebform();

        // There's old applications w/o form_uuid, let's add it here
        // Since we've already loaded webform for submission object the old way,
        // we should have it here anyways. Just make sure it's in the metadata
        // as well.
        if ($webform && !isset($submissionData["metadata"]["form_uuid"])) {
          $submissionData["metadata"]["form_uuid"] = $webform->uuid();
        }

        if ($webform || $submission_entity) {
          $submissionData['messages'] = $this->grantsHandlerMessageService->parseMessages($submissionData);
        }

        $submission = [
          '#theme' => $themeHook,
          '#submission' => $submissionData,
          '#document' => $document,
          '#webform' => $webform,
          '#submission_id' => $submission->id(),
          '#submission_entity' => $submission_entity,
        ];

        if ($submission_entity) {
          $submissionData['status'] = $document->getStatus();
        }

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
      $applicationsSorted['missing_delete_after'] = $missing_delete_after;
      $applicationsSorted['submitted_missing_delete_after'] = $submitted_missing_delete_after;
      return $applicationsSorted;
    }
    else {
      ksort($applications);
      return $applications;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submissionObjectFromApplicationNumber(
    string $applicationNumber,
    ?AtvDocument $document = NULL,
    bool $refetch = FALSE,
    bool $skipAccessCheck = FALSE,
  ): ?WebformSubmission {
    if (isset($this->submissions[$applicationNumber])) {
      return $this->submissions[$applicationNumber];
    }

    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();

    // If no company selected, no mandates no access.
    if ($selectedCompany == NULL && !$skipAccessCheck) {
      throw new CompanySelectException('User not authorised');
    }

    // We need the ATV document to get the form uuid.
    if ($document == NULL) {
      $document = $this->getAtvDocument($applicationNumber, $refetch);
    }

    // Get WebFrom from application number.
    $webform = $this->getWebformFromApplicationNumber($applicationNumber);

    // Should we throw an error here?
    if (!$webform) {
      throw new WebformException('Webform not found');
    }
    // Get serial from application number.
    $submissionSerial = ApplicationHelpers::getSerialFromApplicationNumber($applicationNumber);

    try {
      $result = $this->entityTypeManager->getStorage('webform_submission')
        ->loadByProperties([
          'serial' => $submissionSerial,
          'webform_id' => $webform->id(),
        ]);
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      throw new WebformException('Failed to load submission object with ATV data');
    }

    $submissionObject = NULL;

    // If there's no local submission with given serial
    // we can actually create that object on the fly and use that for editing.
    if (empty($result)) {
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
    else {
      /** @var \Drupal\webform\Entity\WebformSubmission $submissionObject */
      $submissionObject = reset($result);
    }

    if (!$submissionObject) {
      throw new WebformException('Failed to load submission object with ATV data');
    }

    // Load definition.
    $dataDefinition = $this->getDataDefinition($document->getType());

    // Build data.
    $sData = DocumentContentMapper::documentContentToTypedData(
      $document->getContent(),
      $dataDefinition,
      $document->getMetadata()
    );

    // Parse messages separately.
    $sData['messages'] = $this->grantsHandlerMessageService->parseMessages($sData);

    // Set submission data from parsed mapper.
    $submissionObject->setData($sData);

    // Set caching, as we don't want to load this again.
    $this->submissions[$applicationNumber] = $submissionObject;

    return $submissionObject;
  }

  /**
   * {@inheritdoc}
   */
  private function getReactFormApplicationSubmission(
    string $applicationNumber,
  ): ?ApplicationSubmission {
    $submissions = $this->entityTypeManager->getStorage('application_submission')
      ->loadByProperties(['application_number' => $applicationNumber]);

    if (!$submissions) {
      return NULL;
    }
    $submission = reset($submissions);
    assert($submission instanceof ApplicationSubmission);
    return $submission;
  }

  /**
   * {@inheritdoc}
   */
  public function getDataDefinition(string $type): mixed {
    $defClass = Helpers::getApplicationTypes()[$type]['dataDefinition']['definitionClass'];
    $defId = Helpers::getApplicationTypes()[$type]['dataDefinition']['definitionId'];
    return $defClass::create($defId);
  }

  /**
   * {@inheritdoc}
   */
  public function getWebformFromApplicationNumber(string $applicationNumber): Webform {
    // We need the ATV document to get the form uuid.
    $document = $this->getAtvDocument($applicationNumber);

    if (!$document) {
      // No document, throw error.
      throw new AtvDocumentNotFoundException('Document not found');
    }

    $uuid = $document->getMetadata()['form_uuid'] ?? NULL;

    if (!$uuid) {
      // And return webform loaded the old way.
      return ApplicationHelpers::getWebformFromApplicationNumber($applicationNumber);
    }

    try {
      // Try to load webform via UUID.
      $webform_ids = $this->entityTypeManager->getStorage('webform')
        ->getQuery()
        ->condition('uuid', $uuid)
        ->execute();

      // Return the webform if it was found.
      if (!empty($webform_ids)) {
        return Webform::load(reset($webform_ids));
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      // Log failure.
      $this->logger->error(
        'Failed to load webform with uuid: @uuid. Error: @error',
        [
          '@uuid' => $uuid,
          '@error' => $e->getMessage(),
        ]
      );
    }
    // And return webform loaded the old way.
    return ApplicationHelpers::getWebformFromApplicationNumber($applicationNumber);
  }

}
