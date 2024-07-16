<?php

namespace Drupal\grants_handler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\grants_attachments\AttachmentHandler;
use Drupal\grants_metadata\ApplicationDataService;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_metadata\DocumentContentMapper;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\ProfileDataException;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Init applications service.
 */
class ApplicationInitService {

  /**
   * The helfi_helsinki_profiili.userdata service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  private HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata;

  /**
   * The grants_profile.service service.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  private GrantsProfileService $grantsProfileService;

  /**
   * The current language service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  private LanguageManagerInterface $languageManager;

  /**
   * The grants_metadata.application_data service.
   *
   * @var \Drupal\grants_metadata\ApplicationDataService
   */
  private ApplicationDataService $applicationDataService;

  /**
   * The application status service.
   *
   * @var \Drupal\grants_handler\ApplicationStatusService
   */
  private ApplicationStatusService $applicationStatusService;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * Atv Schema.
   *
   * @var \Drupal\grants_metadata\AtvSchema
   */
  private AtvSchema $atvSchema;

  /**
   * Atv access.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  private AtvService $atvService;

  /**
   * Attachment handler class.
   *
   * @var \Drupal\grants_attachments\AttachmentHandler
   */
  protected AttachmentHandler $attachmentHandler;

  /**
   * Constructor.
   *
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata
   *   The helfi_helsinki_profiili.userdata service.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   The grants_profile.service service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The current language service.
   * @param \Drupal\grants_metadata\ApplicationDataService $applicationDataService
   *   The grants_metadata.application_data service.
   * @param \Drupal\grants_handler\ApplicationStatusService $applicationStatusService
   *   The application status service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\grants_metadata\AtvSchema $atvSchema
   *   The atv schema.
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   The atv service.
   */
  public function __construct(
    HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata,
    GrantsProfileService $grantsProfileService,
    LanguageManagerInterface $languageManager,
    ApplicationDataService $applicationDataService,
    ApplicationStatusService $applicationStatusService,
    LoggerChannelFactoryInterface $loggerChannelFactory,
    ConfigFactoryInterface $configFactory,
    AtvSchema $atvSchema,
    AtvService $atvService
  ) {
    $this->helfiHelsinkiProfiiliUserdata = $helfiHelsinkiProfiiliUserdata;
    $this->grantsProfileService = $grantsProfileService;
    $this->languageManager = $languageManager;
    $this->applicationDataService = $applicationDataService;
    $this->applicationStatusService = $applicationStatusService;
    $this->logger = $loggerChannelFactory->get('application_init_service');
    $this->configFactory = $configFactory;
    $this->atvSchema = $atvSchema;
    $this->atvService = $atvService;
  }

  /**
   * Set attachment handler.
   *
   * @param \Drupal\grants_attachments\AttachmentHandler $attachmentHandler
   *   Attachment handler.
   */
  public function setAttachmentHandler(AttachmentHandler $attachmentHandler): void {
    $this->attachmentHandler = $attachmentHandler;
  }

  /**
   * Get budgetInfo keys, that should be copied.
   *
   * @param array $submissionData
   *   Submission data.
   *
   * @return array
   *   Array containing the the keys to be copied.
   */
  private function getBudgetInfoKeysForCopying(array $submissionData): array {
    try {
      $typeData = $this->applicationDataService->webformToTypedData($submissionData);

      /** @var \Drupal\Core\TypedData\ComplexDataDefinitionBase $dataDefinition */
      $dataDefinition = $typeData->getDataDefinition();
      $propertyDefinitions = $dataDefinition->getPropertyDefinitions();

      /** @var \Drupal\grants_budget_components\TypedData\Definition\GrantsBudgetInfoDefinition $budgetInfoDefinition */
      $budgetInfoDefinition = $propertyDefinitions['budgetInfo'] ?? NULL;
      if ($budgetInfoDefinition) {
        return array_keys($budgetInfoDefinition->getPropertyDefinitions()) ?? [];
      }
    }
    catch (\Exception $e) {
      return [];
    }

    return [];
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
   * Get webform title based on id and language code.
   */
  private function getWebformTitle($webform_id, $langCode) {
    // Get the target language object.
    $language = $this->languageManager->getLanguage($langCode);

    // Remember original language before this operation.
    $originalLanguage = $this->languageManager->getConfigOverrideLanguage();

    // Set the translation target language on the configuration factory.
    $this->languageManager->setConfigOverrideLanguage($language);
    $translatedLabel = $this->configFactory->get("webform.webform.{$webform_id}")
      ->get('title');
    $this->languageManager->setConfigOverrideLanguage($originalLanguage);
    return $translatedLabel;
  }

  /**
   * The handleBankAccountCopying method.
   *
   * This method handles the copying of a bank
   * account confirmation file when a grants application
   * is copied. This will:
   *
   * 1. Call handleBankAccountConfirmation() which modifies
   * $submissionData so that it contains a bank account
   * confirmation file that is fetched from the selected account
   * on the copied application.
   *
   * 2. Convert $submissionData into document content.
   *
   * 3. Patch the already existing AtvDocument with
   * the newly added bank account confirmation file.
   *
   * @param \Drupal\helfi_atv\AtvDocument $newDocument
   *   The newly created AtvDocument we are patching.
   * @param \Drupal\webform\Entity\WebformSubmission $submissionObject
   *   A webform submission object based on the copied application.
   * @param array $submissionData
   *   The submission data from the copied application.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   Either the unmodified AtvDocument that already exists,
   *   or one that has been patched with a bank account
   *   confirmation file.
   */
  protected function handleBankAccountCopying(
    AtvDocument $newDocument,
    WebformSubmission $submissionObject,
    array $submissionData
  ): AtvDocument {
    $newDocumentId = $newDocument->getId();
    $applicationNumber = $newDocument->getTransactionId();
    $bankAccountNumber = $submissionData['bank_account']["account_number"] ?? FALSE;

    if (!$newDocumentId || !$applicationNumber || !$bankAccountNumber) {
      return $newDocument;
    }

    try {
      $this->attachmentHandler->handleBankAccountConfirmation(
        $bankAccountNumber,
        $applicationNumber,
        $submissionData,
        TRUE
      );

      $typeData = $this->applicationDataService->webformToTypedData($submissionData);
      $appDocumentContent = $this->atvSchema->typedDataToDocumentContent(
        $typeData,
        $submissionObject,
        $submissionData);

      $newDocument->setContent($appDocumentContent);
      $newDocument = $this->atvService->patchDocument($newDocumentId, $newDocument->toArray());
    }
    catch (AtvDocumentNotFoundException | AtvFailedToConnectException | EventException | GuzzleException $e) {
      $this->logger->error('Error: %msg', ['%msg' => $e->getMessage()]);
    }
    return $newDocument;
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
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException|\Drupal\helfi_helsinki_profiili\ProfileDataException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \Drupal\grants_profile\GrantsProfileException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function initApplication(string $webform_id, array $submissionData = []): WebformSubmission {
    $webform = Webform::load($webform_id);
    $userData = $this->helfiHelsinkiProfiiliUserdata->getUserData();
    $userProfileData = $this->helfiHelsinkiProfiiliUserdata->getUserProfileData();

    if ($userData == NULL || $webform == NULL) {
      throw new ProfileDataException('No Helsinki profile data found');
    }

    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    $companyData = $this->grantsProfileService->getGrantsProfileContent($selectedCompany);

    // Before initialization, check if we are copying an application.
    $copy = !empty($submissionData);

    // Initialize submission data.
    $submissionData = $this->setInitialSubmissionData(
      $webform,
      $selectedCompany,
      $companyData,
      $userData,
      $userProfileData,
      $submissionData);

    // If we are copying an application, we need to clear some data.
    if ($copy) {
      $submissionData = Helpers::clearDataForCopying($submissionData);
      $submissionData = $this->copyBudgetComponentFields($submissionData);
    }

    // We need to map community address fields to submission data.
    $submissionData = $this->mapCommunityAddress($submissionData);

    // Set sender details.
    $submissionData = array_merge($submissionData, $this->parseSenderDetailsSafely());

    // Set timestamps.
    $this->setFormTimestamps($submissionData);

    $submissionObject = $this->createSubmissionObject($webform);
    $submissionData['application_number'] = ApplicationHandler::createApplicationNumber($submissionObject);

    $atvDocument = $this->createAtvDocument($submissionData, $selectedCompany, $webform_id, $userData, $copy);
    $typeData = $this->applicationDataService->webformToTypedData($submissionData);
    $appDocumentContent = $this->atvSchema->typedDataToDocumentContent($typeData, $submissionObject, $submissionData);
    $atvDocument->setContent($appDocumentContent);
    $newDocument = $this->atvService->postDocument($atvDocument);

    if ($copy) {
      $newDocument = $this->handleBankAccountCopying($newDocument, $submissionObject, $submissionData);
    }

    $dataDefinition = $this->getDataDefinition($submissionData['application_type']);
    $submissionObject->setData(DocumentContentMapper::documentContentToTypedData($newDocument->getContent(), $dataDefinition));

    return $submissionObject;
  }

  /**
   * Set initial submission data.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform object.
   * @param array $selectedCompany
   *   Selected company data.
   * @param array $companyData
   *   Company data.
   * @param array $userData
   *   User data.
   * @param array $userProfileData
   *   User profile data.
   * @param array $submissionData
   *   Submission data.
   *
   * @return array
   *   Submission data.
   */
  private function setInitialSubmissionData(
    Webform $webform,
    array $selectedCompany,
    array $companyData,
    array $userData,
    array $userProfileData,
    array $submissionData
  ): array {
    $submissionData['application_type_id'] = $webform->getThirdPartySetting('grants_metadata', 'applicationTypeID');
    $submissionData['application_type'] = $webform->getThirdPartySetting('grants_metadata', 'applicationType');
    $submissionData['applicant_type'] = $this->grantsProfileService->getApplicantType();
    $submissionData['status'] = $this->applicationStatusService->getApplicationStatuses()['DRAFT'];
    $submissionData['company_number'] = $selectedCompany['identifier'];
    $submissionData['business_purpose'] = $companyData['businessPurpose'] ?? '';

    $submissionData['hakijan_tiedot'] = $this->getHakijanTiedot($selectedCompany, $companyData, $userData, $userProfileData);

    return $submissionData;
  }

  /**
   * Get hakijan tiedot.
   *
   * @param array $selectedCompany
   *   Selected company data.
   * @param array $companyData
   *   Company data.
   * @param array $userData
   *   User data.
   * @param array $userProfileData
   *   User profile data.
   *
   * @return array
   *   Hakijan tiedot.
   */
  private function getHakijanTiedot($selectedCompany, $companyData, $userData, $userProfileData): array {
    return match ($selectedCompany["type"]) {
      'registered_community' => [
        'applicantType' => $selectedCompany["type"],
        'applicant_type' => $selectedCompany["type"],
        'communityOfficialName' => $selectedCompany["name"],
        'companyNumber' => $selectedCompany["identifier"],
        'registrationDate' => $companyData["registrationDate"],
        'home' => $companyData["companyHome"],
        'communityOfficialNameShort' => $companyData["companyNameShort"],
        'foundingYear' => $companyData["foundingYear"],
        'homePage' => $companyData["companyHomePage"],
      ],
      'unregistered_community' => [
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
      ],
      'private_person' => [
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
      ],
      default => [],
    };
  }

  /**
   * Map community address fields to submission data.
   *
   * @param array $submissionData
   *   Submission data.
   *
   * @return array
   *   Submission data with community address fields mapped.
   */
  private function mapCommunityAddress(array $submissionData): array {
    if (!empty($submissionData["community_address"]["community_street"])) {
      $submissionData["community_street"] = $submissionData["community_address"]["community_street"];
    }
    if (!empty($submissionData["community_address"]["community_city"])) {
      $submissionData["community_city"] = $submissionData["community_address"]["community_city"];
    }
    if (!empty($submissionData["community_address"]["community_post_code"])) {
      $submissionData["community_post_code"] = $submissionData["community_address"]["community_post_code"];
    }
    if (
      !empty($submissionData["community_address"]["community_country"])) {
      $submissionData["community_country"] = $submissionData["community_address"]["community_country"];
    }
    return $submissionData;
  }

  /**
   * Copy budget component fields to budgetInfo.
   *
   * @param array $submissionData
   *   Submission data.
   *
   * @return array
   *   Submission data with budgetInfo fields copied.
   */
  private function copyBudgetComponentFields(array $submissionData): array {
    $budgetInfoKeys = $this->getBudgetInfoKeysForCopying($submissionData);
    foreach ($budgetInfoKeys as $budgetKey) {
      if (isset($submissionData[$budgetKey])) {
        $submissionData['budgetInfo'][$budgetKey] = $submissionData[$budgetKey];
      }
    }
    return $submissionData;
  }

  /**
   * Parse sender details safely.
   *
   * @return array
   *   Sender details.
   */
  private function parseSenderDetailsSafely(): array {
    try {
      return $this->parseSenderDetails();
    }
    catch (\Exception $e) {
      $this->logger->error('Sender details parsing threw error: @error', ['@error' => $e->getMessage()]);
      return [];
    }
  }

  /**
   * Set form timestamps.
   *
   * @param array $submissionData
   *   Submission data.
   */
  private function setFormTimestamps(array &$submissionData): void {
    $dt = new \DateTime();
    $dt->setTimezone(new \DateTimeZone('Europe/Helsinki'));
    $submissionData['form_timestamp'] = $dt->format('Y-m-d\TH:i:s');
    $submissionData['form_timestamp_created'] = $dt->format('Y-m-d\TH:i:s');
  }

  /**
   * Create a new submission object.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform object.
   *
   * @return \Drupal\webform\Entity\WebformSubmission
   *   Submission object
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createSubmissionObject(Webform $webform): WebformSubmission {
    $submissionObject = WebformSubmission::create([
      'webform_id' => $webform->id(),
      'draft' => TRUE,
    ]);
    $submissionObject->set('in_draft', TRUE);
    $submissionObject->save();
    return $submissionObject;
  }

  /**
   * Create AtvDocument object for new application.
   *
   * @param array $submissionData
   *   Submissin data.
   * @param array $selectedCompany
   *   Selected company data.
   * @param string $webform_id
   *   Webform ID.
   * @param array $userData
   *   User data.
   * @param bool $copy
   *   Is this a copy operation.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   AtvDocument object.
   */
  private function createAtvDocument(
    array $submissionData,
    array $selectedCompany,
    string $webform_id,
    array $userData,
    bool $copy
  ): AtvDocument {
    $webform = Webform::load($webform_id);

    $atvDocument = AtvDocument::create([]);
    $atvDocument->setTransactionId($submissionData['application_number']);
    $atvDocument->setStatus($this->applicationStatusService->getApplicationStatuses()['DRAFT']);
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
      'saveid' => $copy ? 'copiedSave' : 'initialSave',
      'applicationnumber' => $submissionData['application_number'],
      'language' => $this->languageManager->getCurrentLanguage()->getId(),
      'applicant_type' => $selectedCompany['type'],
      'applicant_id' => $selectedCompany['identifier'],
      'form_uuid' => $webform->uuid(),
    ]);

    return $atvDocument;
  }

  /**
   * Get data definition for given application type.
   *
   * @param string $applicationType
   *   Application type.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   Data definition.
   */
  private function getDataDefinition(string $applicationType) {
    $dataDefinitionKeys = $this->applicationDataService->getDataDefinitionClass($applicationType);
    return $dataDefinitionKeys['definitionClass']::create($dataDefinitionKeys['definitionId']);
  }

}
