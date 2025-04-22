<?php

namespace Drupal\grants_profile;

use Drupal\Component\Utility\Html;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Error;
use Drupal\file\FileInterface;
use Drupal\grants_handler\Helpers;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_audit_log\AuditLogServiceInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Handle all profile functionality.
 */
class GrantsProfileService {

  use StringTranslationTrait;

  const DOCUMENT_STATUS_NEW = 'DRAFT';

  const DOCUMENT_STATUS_SAVED = 'READY';

  const DOCUMENT_TRANSACTION_ID_INITIAL = 'initialSave';

  /**
   * Variable for translation context.
   *
   * @var array|string[] Translation context for class
   */
  private array $tOpts = ['context' => 'grants_profile'];

  /**
   * Constructs a GrantsProfileService object.
   *
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   The helfi_atv service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Show messages to user.
   * @param \Drupal\grants_profile\ProfileConnector $profileConnector
   *   Access to profile data.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger service.
   * @param \Drupal\grants_profile\GrantsProfileCache $grantsProfileCache
   *   Cache.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   Uuid generator.
   * @param \Drupal\helfi_audit_log\AuditLogServiceInterface $auditLogService
   *   The audit log service.
   */
  public function __construct(
    #[Autowire(service: 'helfi_atv.atv_service')]
    private readonly AtvService $atvService,
    private readonly MessengerInterface $messenger,
    private readonly ProfileConnector $profileConnector,
    #[Autowire(service: 'logger.channel.grants_profile')]
    private readonly LoggerInterface $logger,
    private readonly GrantsProfileCache $grantsProfileCache,
    private readonly UuidInterface $uuid,
    #[Autowire(service: 'helfi_audit_log.audit_log')]
    private readonly AuditLogServiceInterface $auditLogService,
  ) {
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

    $newProfileData['user_id'] = $this->profileConnector->getUserId();
    $newProfileData['status'] = self::DOCUMENT_STATUS_NEW;
    $newProfileData['deletable'] = TRUE;

    $newProfileData['tos_record_id'] = $this->newProfileTosRecordId();
    $newProfileData['tos_function_id'] = $this->newProfileTosFunctionId();

    $newProfileData['transaction_id'] = 'initialSave';

    $newProfileData['metadata'] = [
      'profile_type' => $selectedCompanyArray['type'],
      'profile_id' => $selectedCompany,
      'appenv' => Helpers::getAppEnv(),
      'notification_shown' => (string) time(),
    ];

    return $this->atvService->createDocument($newProfileData);
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
   * @param array $documentContent
   *   Document content.
   * @param array $updatedMetadata
   *   Updated metadata.
   * @param bool $cleanAttachments
   *   If true, removes attachments not included in $documentContent.
   *
   * @return bool|AtvDocument
   *   Did save succeed?
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function saveGrantsProfile(array $documentContent, array $updatedMetadata = [], bool $cleanAttachments = FALSE): bool|AtvDocument {
    // Get selected company.
    $selectedCompany = $this->getSelectedRoleData();
    // Get grants profile.
    $grantsProfileDocument = $this->getGrantsProfile($selectedCompany, TRUE);

    // If there is no document content.
    if (empty($documentContent) && $grantsProfileDocument != NULL) {
      $documentContent = $grantsProfileDocument->getContent();
    }

    // Make sure business id is saved.
    $documentContent['businessId'] = $selectedCompany['identifier'];

    $transactionId = $this->uuid->generate();

    // Check if grantsProfile exists.
    if ($grantsProfileDocument == NULL) {
      $newGrantsProfileDocument = $this->newProfileDocument($documentContent);
      $newGrantsProfileDocument->setStatus(self::DOCUMENT_STATUS_SAVED);
      $newGrantsProfileDocument->setTransactionId(self::DOCUMENT_TRANSACTION_ID_INITIAL);
      try {
        $this->logger->info('Grants profile POSTed, transactionID: %transId', ['%transId' => $transactionId]);
        return $this->atvService->postDocument($newGrantsProfileDocument);
      }
      catch (\Exception $e) {
        throw new GrantsProfileException('ATV connection error');
      }
    }

    foreach ($documentContent['bankAccounts'] as $key => $bank_account) {
      unset($documentContent['bankAccounts'][$key]['confirmationFileName']);
    }

    // Get existing metadata from document.
    $metadata = $grantsProfileDocument->getMetadata();

    // If we have updated metadata fields, merge them.
    if (!empty($updatedMetadata)) {
      // Merge existing values with new ones.
      $metadata = array_merge($metadata, $updatedMetadata);
    }

    $payloadData = [
      'content' => $documentContent,
      'metadata' => $metadata,
      'transaction_id' => $transactionId,
    ];
    $this->logger->info('Grants profile PATCHed, transactionID: %transactionId',
      ['%transactionId' => $transactionId]);
    try {
      $result = $this->atvService->patchDocument($grantsProfileDocument->getId(), $payloadData);
    }
    catch (\Exception $e) {
      throw new GrantsProfileException('ATV connection error');
    }

    if ($cleanAttachments && $result instanceof AtvDocument) {
      $keep = array_map(static fn (array $account) => $account['confirmationFile'], $documentContent['bankAccounts']);
      $remove = array_filter($result->getAttachments(), static fn (array $attachment) => !in_array($attachment['filename'], $keep));

      try {
        $message = [
          "operation" => "GRANTS_APPLICATION_ATTACHMENT_DELETE",
          "status" => "SUCCESS",
          "target" => [
            "id" => $result->getId(),
            "type" => $result->getType(),
            "name" => $result->getTransactionId(),
          ],
        ];

        foreach ($remove as $attachment) {
          $this->atvService->deleteAttachment($result->getId(), $attachment['id']);
          $this->auditLogService->dispatchEvent($message);
        }
      }
      catch (\Exception $e) {
        // Failed to clean all attachments.
        Error::logException($this->logger, $e);

        $message['status'] = 'FAILURE';
        $this->auditLogService->dispatchEvent($message);
      }
    }

    return $result;
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
    mixed $selectedRoleData,
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
        ]);

      \Sentry\captureException($e);
    }
    return $newProfile;
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
   * @throws \Drupal\grants_profile\GrantsProfileException
   */
  public function getGrantsProfileContent(
    mixed $business,
    bool $refetch = FALSE,
  ): array {
    $profileData = $this->getGrantsProfile($business, $refetch);

    if ($profileData == NULL) {
      return [];
    }

    return $profileData->getContent();

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
   * @throws \Drupal\grants_profile\GrantsProfileException
   */
  public function getGrantsProfile(
    array $profileIdentifier,
    bool $refetch = FALSE,
  ): AtvDocument|null {
    if ($refetch === FALSE && $this->grantsProfileCache->isCached($profileIdentifier['identifier'])) {
      return $this->grantsProfileCache->getFromCache($profileIdentifier['identifier']);
    }

    // Get profile document from ATV.
    try {
      $profileDocument = $this->getGrantsProfileFromAtv($profileIdentifier, $refetch);

      $profileDocument = $this->decodeProfileContent($profileDocument);
      $this->grantsProfileCache->setToCache($profileIdentifier['identifier'], $profileDocument);
      return $profileDocument;
    }
    catch (AtvDocumentNotFoundException $e) {
      return NULL;
    }
    catch (\Exception $e) {
      // We end up here only if ATV data is malformed.
      throw new GrantsProfileException('Error while handling ATV data.');
    }
  }

  /**
   * Upload file to ATV.
   *
   * Purpose of this method is to hide ATV logic
   * inside this class.
   *
   * @param string $id
   *   Profile id.
   * @param \Drupal\file\FileInterface $file
   *   Actual file to be uploaded.
   *
   * @return mixed
   *   File data or success.
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function uploadAttachment(string $id, FileInterface $file): mixed {
    try {
      return $this->atvService->uploadAttachment(
        $id,
        $file->getFilename(),
        $file
      );
    }
    catch (\Exception $e) {
      throw new GrantsProfileException('ATV connection error');
    }
  }

  /**
   * Get profile data from ATV.
   *
   * @param array $profileIdentifier
   *   Id to be fetched.
   * @param bool $refetch
   *   Force refetching and bypass caching.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   Profile data
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   */
  private function getGrantsProfileFromAtv(array $profileIdentifier, $refetch = FALSE): AtvDocument {

    // Registered communities we can fetch by the business id.
    if ($profileIdentifier["type"] === 'registered_community') {
      $searchParams = [
        'type' => 'grants_profile',
        'business_id' => $profileIdentifier['identifier'],
        'lookfor' => 'appenv:' . Helpers::getAppEnv(),
      ];
    }
    else {
      // Others, cannot.
      $searchParams = [
        'type' => 'grants_profile',
        'lookfor' => 'appenv:' . Helpers::getAppEnv() .
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
      throw new AtvDocumentNotFoundException('Not found');
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
    if ($this->grantsProfileCache->isCached('selected_company')) {
      return $this->grantsProfileCache->getFromCache('selected_company');
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
    return $this->grantsProfileCache->setToCache('selected_company', $companyData);
  }

  /**
   * Get selected company id.
   *
   * @return string|null
   *   Selected company
   */
  public function getApplicantType(): ?string {
    if ($this->grantsProfileCache->isCached('applicant_type')) {
      $data = $this->grantsProfileCache->getFromCache('applicant_type');
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
    return $this->grantsProfileCache->setToCache('applicant_type', ['selected_type' => $selected_type]);
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
   */
  public function getUsersGrantsProfiles(string $userId, string $profileType): array {

    // Others, cannot.
    $searchParams = [
      'type' => 'grants_profile',
      'user_id' => $userId,
      'lookfor' => 'appenv:' . Helpers::getAppEnv() . ',profile_type:' . $profileType,
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

  /**
   * The getNotificationShown method.
   *
   * This method returns timestamp of the time
   * a notification was shown.
   *
   * @return int|string
   *   Timestamp of last time notification was shown.
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   */
  public function getNotificationShown(): int|string {
    // Get selected company.
    $selectedCompany = $this->getSelectedRoleData();
    // Get grants profile.
    $grantsProfileDocument = $this->getGrantsProfile($selectedCompany);

    $profileMetadata = $grantsProfileDocument?->getMetadata();
    return $profileMetadata['notification_shown'] ?? 0;
  }

  /**
   * The setNotificationShown method.
   *
   * This method sets a timestamp of the time
   * a notification was shown.*
   *
   * @param int $timestamp
   *   Timestamp of last time notification was shown.
   *
   * @return bool|AtvDocument
   *   Was the notification shown?
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function setNotificationShown(int $timestamp): bool|AtvDocument {
    $profileMetadata['notification_shown'] = $timestamp;

    return $this->saveGrantsProfile([], $profileMetadata);
  }

  /**
   * Delete the given grants profile document.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The document to be deleted.
   *
   * @return bool
   *   Was the document deleted?
   */
  public function removeGrantsProfileDocument(AtvDocument $document): bool {
    try {
      $this->atvService->deleteDocument($document);
      return TRUE;
    }
    catch (\Throwable $e) {
      $id = $document->getId();
      $this->logger->error('Error removing empty profile (id: @id) from ATV: @e',
        ['@e' => $e->getMessage(), '@id' => $id],
      );
      return FALSE;
    }
  }

}
