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
 *
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
   * @param \Drupal\grants_attachments\AttachmentHandler $attachmentHandler
   */
  public function setAttachmentHandler(AttachmentHandler $attachmentHandler): void {
    $this->attachmentHandler = $attachmentHandler;
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
   * @throws \Drupal\grants_profile\GrantsProfileException
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
    $submissionData['status'] = $this->applicationStatusService->getApplicationStatuses()['DRAFT'];
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
      $submissionData = array_merge($submissionData, $this->parseSenderDetails());
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
    array $submissionData): AtvDocument {

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

}
