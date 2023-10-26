<?php

namespace Drupal\grants_profile;

use Drupal\Component\Utility\Html;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_metadata\AtvSchema;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_audit_log\AuditLogService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Handle all profile functionality.
 */
class GrantsProfileService {

  use StringTranslationTrait;

  const DOCUMENT_STATUS_NEW = 'DRAFT';

  const DOCUMENT_STATUS_SAVED = 'READY';

  const DOCUMENT_TRANSACTION_ID_INITIAL = 'initialSave';

  /**
   * The helfi_atv service.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Request stack for session access.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The Helsinki profiili and Yjhd connector.
   *
   * @var \Drupal\grants_profile\ProfileConnector
   */
  protected ProfileConnector $profileConnector;

  /**
   * ATV Schema mapper.
   *
   * @var \Drupal\grants_metadata\AtvSchema
   */
  protected AtvSchema $atvSchema;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Audit logger.
   *
   * @var \Drupal\helfi_audit_log\AuditLogService
   */
  protected AuditLogService $auditLogService;

  /**
   * Session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected Session $session;

  /**
   * Variable for translation context.
   *
   * @var array|string[] Translation context for class
   */
  private array $tOpts = ['context' => 'grants_profile'];

  /**
   * Constructs a GrantsProfileService object.
   *
   * @param \Drupal\helfi_atv\AtvService $helfi_atv
   *   The helfi_atv service.
   * @param \Drupal\Core\Http\RequestStack $requestStack
   *   Storage factory for temp store.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Show messages to user.
   * @param Drupal\grants_profile\ProfiiliConnector $profileConnector
   *   Access to profile data.
   * @param \Drupal\grants_metadata\AtvSchema $atv_schema
   *   Atv schema mapper.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger service.
   * @param \Drupal\helfi_audit_log\AuditLogService $auditLogService
   *   Audit log.
   */
  public function __construct(
    AtvService $helfiAtv,
    RequestStack $requestStack,
    MessengerInterface $messenger,
    ProfileConnector $profileConnector,
    AtvSchema $atvSchema,
    LoggerChannelFactory $loggerFactory,
    AuditLogService $auditLogService,
  ) {
    $this->atvService = $helfiAtv;
    $this->requestStack = $requestStack;
    $this->messenger = $messenger;
    $this->profileConnector = $profileConnector;
    $this->atvSchema = $atvSchema;
    $this->logger = $loggerFactory->get('helfi_atv');
    $this->auditLogService = $auditLogService;
  }

  /**
   * Set session instance for the service.
   *
   * This is used for tests .
   *
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   Session object.
   */
  public function setSession(Session $session): void {
    $this->session = $session;
  }

  /**
   * Get session.
   *
   * @return \Symfony\Component\HttpFoundation\Session\Session
   *   Session object
   */
  public function getSession() {
    if (isset($this->session)) {
      return $this->session;
    }
    $session = $this->requestStack->getCurrentRequest()->getSession();
    $this->session = $session;
    return $this->session;
  }

  /**
   * Create new profile to be saved to ATV.
   *
   * @param array $data
   *   Data for the new profile document.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   New profile
   */
  protected function newProfileDocument(array $data): AtvDocument {

    $newProfileData = [];
    $selectedCompanyArray = $this->getSelectedRoleData();
    $selectedCompany = $selectedCompanyArray['identifier'];
    //$userData = $this->helsinkiProfiili->getUserData();

    // If data is already in profile format, use that as is.
    if (isset($data['content'])) {
      $newProfileData = $data;
    }
    else {
      // Or create new content field.
      $newProfileData['content'] = $data;
    }

    $newProfileData['type'] = 'grants_profile';

    if (strlen($selectedCompany) < 10) {
      $newProfileData['business_id'] = $selectedCompany;
    }

    $newProfileData['user_id'] = $userData["sub"];
    $newProfileData['status'] = self::DOCUMENT_STATUS_NEW;
    $newProfileData['deletable'] = TRUE;

    $newProfileData['tos_record_id'] = $this->newProfileTosRecordId();
    $newProfileData['tos_function_id'] = $this->newProfileTosFunctionId();

    $newProfileData['transaction_id'] = 'initialSave';

    $newProfileData['metadata'] = [
      'profile_type' => $selectedCompanyArray['type'],
      'profile_id' => $selectedCompany,
      'appenv' => ApplicationHandler::getAppEnv(),
    ];

    return $this->atvService->createDocument($newProfileData);
  }

  /**
   * Transaction ID for new profile.
   *
   * @return string
   *   Transaction ID
   */
  protected function newTransactionId(): string {
    return Uuid::uuid4()->toString();
  }

  /**
   * Fetch the New Profile TOS record ID.
   *
   * @return string
   *   TOS id
   */
  protected function newProfileTosRecordId(): string {
    /*
     * At the moment this is a placeholder.
     *
     * When we change from placeholders to actual following the TOS records,
     * this should become dynamic.
     */
    return 'eb30af1d9d654ebc98287ca25f231bf6';
  }

  /**
   * Function ID.
   *
   * @return string
   *   New function ID.
   */
  protected function newProfileTosFunctionId(): string {
    /*
     * At the moment this is a placeholder.
     *
     * When we change from placeholders to actual following the TOS records,
     * this should become dynamic.
     */
    return 'eb30af1d9d654ebc98287ca25f231bf6';
  }

  /**
   * Format data from tempstore & save document back to ATV.
   *
   * @return bool|AtvDocument
   *   Did save succeed?
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function saveGrantsProfile(array $documentContent): bool|AtvDocument {
    // Get selected company.
    $selectedCompany = $this->getSelectedRoleData();
    // Get grants profile.
    $grantsProfileDocument = $this->getGrantsProfile($selectedCompany, TRUE);

    // Make sure business id is saved.
    $documentContent['businessId'] = $selectedCompany['identifier'];

    $transactionId = $this->newTransactionId();

    if ($grantsProfileDocument == NULL) {
      $newGrantsProfileDocument = $this->newProfileDocument($documentContent);
      $newGrantsProfileDocument->setStatus(self::DOCUMENT_STATUS_SAVED);
      $newGrantsProfileDocument->setTransactionId(self::DOCUMENT_TRANSACTION_ID_INITIAL);

      $this->logger->info('Grants profile POSTed, transactionID: %transId', ['%transId' => $transactionId]);
      return $this->atvService->postDocument($newGrantsProfileDocument);
    }
    else {

      foreach ($documentContent['bankAccounts'] as $key => $bank_account) {
        unset($documentContent['bankAccounts'][$key]['confirmationFileName']);
      }

      $payloadData = [
        'content' => $documentContent,
        'metadata' => $grantsProfileDocument->getMetadata(),
        'transaction_id' => $transactionId,
      ];
      $this->logger->info('Grants profile PATCHed, transactionID: %transactionId',
        ['%transactionId' => $transactionId]);
      return $this->atvService->patchDocument($grantsProfileDocument->getId(), $payloadData);
    }
  }

  /**
   * Check if a given string is a valid UUID.
   *
   * @param string $uuid
   *   The string to check.
   *
   * @return bool
   *   Is valid or not?
   */
  public function isValidUuid($uuid): bool {

    if (!is_string($uuid) ||
      (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Create new profile object.
   *
   * @param mixed $selectedRoleData
   *   Customers' selected role data.
   *
   * @return bool|\Drupal\helfi_atv\AtvDocument
   *   New profle.
   */
  public function createNewProfile(
    mixed $selectedRoleData
  ): bool|AtvDocument {

    try {
      $grantsProfileContent = $this->profileConnector->initGrantsProfile($selectedRoleData['type'], $selectedRoleData);

      if ($grantsProfileContent !== NULL) {
        // Initial save of the new profile, so we can add files to it.
        $newProfile = $this->saveGrantsProfile($grantsProfileContent);
      }
      else {
        $newProfile = FALSE;
      }
    }
    catch (\Throwable $e) {
      $newProfile = FALSE;
      // If no company data is found, we cannot continue.
      $this->messenger
        ->addError($this->t('Community details not found in registries. Please contact customer service',
          [], $this->tOpts));
      $this->logger
        ->error('Error fetching community data. Error: %error', [
          '%error' => $e->getMessage(),
        ]
            );
    }
    return $newProfile;
  }

  /**
   * Remove unregistered community.
   *
   * @param array $companyData
   *   Company to remove.
   *
   * @return array
   *   Was the removal successful
   */
  public function removeProfile(array $companyData): array {
    if ($companyData['type'] !== 'unregistered_community') {
      return [
        'reason' => $this->t('You can not remove this profile', [], $this->tOpts),
        'success' => FALSE,
      ];
    }
    /** @var \Drupal\helfi_atv\AtvDocument $atvDocument */
    $atvDocument = $this->getGrantsProfile($companyData);
    if (!$atvDocument->isDeletable()) {
      return [
        'reason' => $this->t('You can not remove this profile', [], $this->tOpts),
        'success' => FALSE,
      ];
    }

    $appEnv = ApplicationHandler::getAppEnv();

    try {
      // Get applications from ATV.
      $applications = ApplicationHandler::getCompanyApplications(
        $companyData,
        $appEnv,
        FALSE,
        TRUE,
        'application_list_item'
      );
      $drafts = [];
      if (isset($applications['DRAFT'])) {
        $drafts = $applications['DRAFT'];
        unset($applications['DRAFT']);
      }
      if (!empty($applications)) {
        return [
          'reason' => $this->t('Community has applications in progress.', [], $this->tOpts),
          'success' => FALSE,
        ];
      }
    }
    catch (\Throwable $e) {
      $this->logger->error('Error fetching data from ATV: @e', ['@e' => $e->getMessage()]);
      return [
        'reason' => $this->t('Connection error', [], $this->tOpts),
        'success' => FALSE,
      ];
    }
    try {
      foreach ($drafts as $draft) {
        $this->atvService->deleteDocument($draft['#document']);
      }
      $this->atvService->deleteDocument($atvDocument);
    }
    catch (\Throwable $e) {
      $id = $atvDocument->getId();
      $this->logger->error('Error removing profile (id: @id) from ATV: @e',
        ['@e' => $e->getMessage(), '@id' => $id],
      );
      return [
        'reason' => $this->t('Connection error', [], $this->tOpts),
        'success' => FALSE,
      ];
    }
    return [
      'reason' => '',
      'success' => TRUE,
    ];
  }

  /**
   * Get "content" array from document in ATV.
   *
   * @param mixed $business
   *   Business id OR full business object.
   * @param bool $refetch
   *   If true, data is fetched always.
   *
   * @return array
   *   Content
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getGrantsProfileContent(
    mixed $business,
    bool $refetch = FALSE
  ): array {

    if ($refetch === FALSE && $this->isCached($business['identifier'])) {
      $profileData = $this->getFromCache($business['identifier']);
      return $profileData->getContent();
    }

    $profileData = $this->getGrantsProfile($business, $refetch);

    if ($profileData == NULL) {
      return [];
    }

    return $profileData->getContent();

  }

  /**
   * Get "content" array from document in ATV.
   *
   * @param string $businessId
   *   What business data is fetched.
   * @param bool $refetch
   *   If true, data is fetched always.
   *
   * @return array
   *   Content
   */
  public function getGrantsProfileAttachments(
    string $businessId,
    bool $refetch = FALSE
  ): array {

    if ($refetch === FALSE && $this->isCached($businessId)) {
      $profileData = $this->getFromCache($businessId);
      return $profileData->getAttachments();
    }
    else {
      $profileData = $this->getGrantsProfile($businessId, $refetch);
    }

    return $profileData->getAttachments();

  }

  /**
   * Get profile Document.
   *
   * @param array $profileIdentifier
   *   Business id for profile.
   * @param bool $refetch
   *   Force refetching of the data.
   *
   * @return \Drupal\helfi_atv\AtvDocument|null
   *   Profiledata
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getGrantsProfile(
    array $profileIdentifier,
    bool $refetch = FALSE
  ): AtvDocument|null {
    if ($refetch === FALSE && $this->isCached($profileIdentifier['identifier'])) {
      return $this->getFromCache($profileIdentifier['identifier']);
    }

    // Get profile document from ATV.
    try {
      $profileDocument = $this->getGrantsProfileFromAtv($profileIdentifier, $refetch);

      if ($profileDocument) {
        $profileDocument = $this->decodeProfileContent($profileDocument);
        $this->setToCache($profileIdentifier['identifier'], $profileDocument);
        return $profileDocument;
      }
    }
    catch (AtvDocumentNotFoundException $e) {
      return NULL;
    }

    return NULL;
  }

  /**
   * Get profile data from ATV.
   *
   * @param array $profileIdentifier
   *   Id to be fetched.
   * @param bool $refetch
   *   Force refetching and bypass caching.
   *
   * @return \Drupal\helfi_atv\AtvDocument|bool
   *   Profile data
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function getGrantsProfileFromAtv(array $profileIdentifier, $refetch = FALSE): AtvDocument|bool {

    // Registered communities we can fetch by the business id.
    if ($profileIdentifier["type"] === 'registered_community') {
      $searchParams = [
        'type' => 'grants_profile',
        'business_id' => $profileIdentifier['identifier'],
        'lookfor' => 'appenv:' . ApplicationHandler::getAppEnv(),
      ];
    }
    else {
      // Others, cannot.
      $searchParams = [
        'type' => 'grants_profile',
        'lookfor' => 'appenv:' . ApplicationHandler::getAppEnv() .
        ',profile_id:' . $profileIdentifier['identifier'] .
        ',profile_type:' . $profileIdentifier['type'],
      ];
    }

    try {
      $searchDocuments = $this->atvService->searchDocuments($searchParams, $refetch);
    }
    catch (\Exception $e) {
      throw new AtvDocumentNotFoundException('Not found');
    }

    if (empty($searchDocuments)) {
      return FALSE;
    }
    return reset($searchDocuments);
  }

  /**
   * Get selected company id.
   *
   * @return array|null
   *   Selected company
   */
  public function getSelectedRoleData(): ?array {
    if ($this->isCached('selected_company')) {
      return $this->getFromCache('selected_company');
    }
    return NULL;
  }

  /**
   * Set selected role data to store.
   *
   * Data structure needs to be same what we set with mandates.
   *
   * [
   * name => ''
   * identifier => ''
   * complete => true
   * roles => []
   * ]
   *
   * @param array $companyData
   *   Company details.
   *
   * @return bool
   *   Success.
   */
  public function setSelectedRoleData(array $companyData): bool {
    return $this->setToCache('selected_company', $companyData);
  }

  /**
   * Get selected company id.
   *
   * @return string|null
   *   Selected company
   */
  public function getApplicantType(): ?string {
    if ($this->isCached('applicant_type')) {
      $data = $this->getFromCache('applicant_type');
      return $data['selected_type'];
    }
    return '';
  }

  /**
   * Set selected business id to store.
   *
   * @param string $selected_type
   *   Type to be saved.
   */
  public function setApplicantType(string $selected_type): bool {
    return $this->setToCache('applicant_type', ['selected_type' => $selected_type]);
  }

  /**
   * Whether we have made this query?
   *
   * @param string|null $key
   *   Used key for caching.
   *
   * @return bool
   *   Is this cached?
   */
  private function isCached(?string $key): bool {
    $session = $this->getSession();

    $cacheData = $session->get($key);
    return !is_null($cacheData);
  }

  /**
   * Get item from cache.
   *
   * @param string $key
   *   Key to fetch from tempstore.
   *
   * @return array|\Drupal\helfi_atv\AtvDocument|null
   *   Data in cache or null
   */
  private function getFromCache(string $key): array|AtvDocument|null {
    $session = $this->getSession();
    return !empty($session->get($key)) ? $session->get($key) : NULL;
  }

  /**
   * Add item to cache.
   *
   * @param string $key
   *   Used key for caching.
   * @param array|\Drupal\helfi_atv\AtvDocument $data
   *   Cached data.
   *
   * @return bool
   *   Did save succeed?
   */
  private function setToCache(string $key, array|AtvDocument $data): bool {

    $session = $this->getSession();

    if (gettype($data) == 'object') {
      $session->set($key, $data);
      return TRUE;
    }

    if (
      isset($data['content']) ||
      $key == 'selected_company' ||
      $key == 'applicant_type'
    ) {
      $session->set($key, $data);
      return TRUE;
    }
    else {
      try {
        $grantsProfile = $this->getGrantsProfile($key);
        $grantsProfile->setContent($data);
        $session->set($key, $grantsProfile);
        return TRUE;
      }
      catch (\Throwable $e) {
        $this->logger->error('Error getting profile from ATV: @e', ['@e' => $e->getMessage()]);
        return FALSE;
      }
    }
  }

  /**
   * Clean up any attachments from profile.
   *
   * Sometimes deleting of attachment fails and document is left with some
   * attachments that are not in any bank accounts.
   * These need to be cleared out.
   *
   * Also this seems not to work as expected, for some reason it does not remove
   * correct items, and results vary somewhat often. No time to fix this now.
   *
   * @todo https://helsinkisolutionoffice.atlassian.net/browse/AU-860
   * Fix clearing of attachments.
   *
   * @param \Drupal\helfi_atv\AtvDocument $grantsProfile
   *   Profile to be cleared.
   * @param array|null $triggeringElement
   *   Element triggering event.
   */
  public function clearAttachments(AtvDocument &$grantsProfile, ?array $triggeringElement): void {

    if ($triggeringElement !== NULL) {
      return;
    }

    $profileContent = $grantsProfile->getContent();
    foreach ($grantsProfile->getAttachments() as $key => $attachment) {
      $bankAccountAttachment = array_filter($profileContent['bankAccounts'], function ($item) use ($attachment) {
        return $item['confirmationFile'] === $attachment['filename'];
      });

      if (empty($bankAccountAttachment)) {
        try {
          $this->atvService->deleteAttachmentByUrl($attachment['href']);

          $message = [
            "operation" => "GRANTS_APPLICATION_ATTACHMENT_DELETE",
            "status" => "SUCCESS",
            "target" => [
              "id" => $grantsProfile->getId(),
              "type" => $grantsProfile->getType(),
              "name" => $grantsProfile->getTransactionId(),
            ],
          ];

          unset($grantsProfile['attachments'][$key]);

        }
        catch (\Throwable $e) {
          $message = [
            "operation" => "GRANTS_APPLICATION_ATTACHMENT_DELETE",
            "status" => "FAILURE",
            "target" => [
              "id" => $grantsProfile->getId(),
              "type" => $grantsProfile->getType(),
              "name" => $grantsProfile->getTransactionId(),
            ],
          ];
        }
        $this->auditLogService->dispatchEvent($message);
      }
    }
  }

  /**
   * Get users profiles.
   *
   * @param string $userId
   *   User id.
   * @param string $profileType
   *   Profile type.
   *
   * @return array
   *   Users profiles
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getUsersGrantsProfiles(string $userId, string $profileType): array {

    // Others, cannot.
    $searchParams = [
      'type' => 'grants_profile',
      'user_id' => $userId,
      'lookfor' => 'appenv:' . ApplicationHandler::getAppEnv() . ',profile_type:' . $profileType,
    ];

    try {
      $searchDocuments = $this->atvService->searchDocuments($searchParams);
    }
    catch (\Exception $e) {
      throw new AtvDocumentNotFoundException('Not found');
    }

    return $searchDocuments;
  }

  /**
   * Get new UUID string.
   *
   * @return string
   *   Unique UUID
   */
  public function getUuid(): string {
    return Uuid::uuid4()->toString();
  }

  /**
   * The decodeProfileContent method.
   *
   * This method calls decodeProfileContentRecursive
   * in order to handle decoding of the profile document
   * recursively.
   *
   * @param \Drupal\helfi_atv\AtvDocument $profileDocument
   *   An ATV document whose content we want to decode.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   An ATV document whose content has been decoded.
   */
  private function decodeProfileContent(AtvDocument $profileDocument): AtvDocument {
    $profileDocumentContent = $profileDocument->getContent();
    $profileDocumentContent = $this->decodeProfileContentRecursive($profileDocumentContent);
    $profileDocument->setContent($profileDocumentContent);
    return $profileDocument;
  }

  /**
   * The decodeProfileContentRecursive method.
   *
   * This method recursively walks through an associative array
   * and decodes all the string values in it. The method is used by
   * decodeProfileContent() to decode the profile content from ATV.
   *
   * @param array $profileDocumentContent
   *   An array of profile document content.
   *
   * @return array
   *   A decoded array of profile document content.
   */
  private function decodeProfileContentRecursive(array $profileDocumentContent): array {
    foreach ($profileDocumentContent as &$item) {
      if (is_array($item)) {
        $item = $this->decodeProfileContentRecursive($item);
      }
      if (is_string($item)) {
        $item = Html::decodeEntities(strip_tags($item));
      }
    }
    return $profileDocumentContent;
  }

}
