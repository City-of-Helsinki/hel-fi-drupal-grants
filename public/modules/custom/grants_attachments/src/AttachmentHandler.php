<?php

namespace Drupal\grants_attachments;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\grants_attachments\Plugin\WebformElement\GrantsAttachments;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_handler\EventException;
use Drupal\grants_handler\EventsService;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_profile\GrantsProfileException;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_audit_log\AuditLogService;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Handle attachment related things.
 */
class AttachmentHandler {

  use StringTranslationTrait;

  /**
   * The grants_attachments.attachment_remover service.
   *
   * @var \Drupal\grants_attachments\AttachmentRemover
   */
  protected AttachmentRemover $attachmentRemover;

  /**
   * Field names for attachments.
   *
   * @var string[]
   *
   * @todo get field names from form where field type is attachment.
   */
  protected static array $attachmentFieldNames = [];


  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * Show messages messages.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * ATV access.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Grants profile access.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Atv schema service.
   *
   * @var \Drupal\grants_metadata\AtvSchema
   */
  protected AtvSchema $atvSchema;

  /**
   * Atv schema service.
   *
   * @var \Drupal\grants_handler\EventsService
   */
  protected EventsService $eventService;

  /**
   * Audit logger.
   *
   * @var \Drupal\helfi_audit_log\AuditLogService
   */
  protected AuditLogService $auditLogService;

  /**
   * The storage handler class for files.
   *
   * @var \Drupal\file\FileStorage
   */
  private $fileStorage;

  /**
   * Attached file id's.
   *
   * @var array
   */
  protected array $attachmentFileIds;

  /**
   * Debug status.
   *
   * @var bool
   */
  protected bool $debug;

  /**
   * Constructs an AttachmentHandler object.
   *
   * @param \Drupal\grants_attachments\AttachmentRemover $grants_attachments_attachment_remover
   *   Remover.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   Messenger.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerChannelFactory
   *   Logger.
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   Atv access.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Profile service.
   * @param \Drupal\grants_metadata\AtvSchema $atvSchema
   *   ATV schema.
   * @param \Drupal\grants_metadata\AtvSchema $eventService
   *   Events service.
   * @param \Drupal\helfi_audit_log\AuditLogService $auditLogService
   *   Audit log mandate errors.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(
    AttachmentRemover $grants_attachments_attachment_remover,
    Messenger $messenger,
    LoggerChannelFactory $loggerChannelFactory,
    AtvService $atvService,
    GrantsProfileService $grantsProfileService,
    AtvSchema $atvSchema,
    EventsService $eventService,
    AuditLogService $auditLogService,
    EntityTypeManagerInterface $entityTypeManager,
  ) {

    $this->attachmentRemover = $grants_attachments_attachment_remover;

    $this->messenger = $messenger;
    $this->logger = $loggerChannelFactory->get('grants_attachments_handler');

    $this->atvService = $atvService;
    $this->grantsProfileService = $grantsProfileService;

    $this->attachmentFileIds = [];

    $this->atvSchema = $atvSchema;
    $this->eventService = $eventService;
    $this->auditLogService = $auditLogService;
    $this->fileStorage = $entityTypeManager->getStorage('file');

    $this->debug = getenv('debug') ?? FALSE;

  }

  /**
   * If debug is on or not.
   *
   * @return bool
   *   TRue or false depending on if debug is on or not.
   */
  public function isDebug(): bool {
    if ($this->debug === TRUE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Set debug.
   *
   * @param bool $debug
   *   True or false.
   */
  public function setDebug(bool $debug): void {
    $this->debug = $debug;
  }

  /**
   * Get file fields.
   *
   * @return string[]
   *   Attachment fields.
   */
  public static function getAttachmentFieldNames(string $applicationNumber, $preventKeys = FALSE): array {

    // Load application type from webform.
    // This could probably be done just by parsing the application number,
    // however this more futureproof.
    $webform = ApplicationHandler::getWebformFromApplicationNumber($applicationNumber);
    $thirdPartySettings = $webform->getThirdPartySettings('grants_metadata');
    $applicationType = $thirdPartySettings["applicationType"];

    // If no fieldnames are.
    if (!isset(self::$attachmentFieldNames[$applicationType])) {
      $attachmentElements = array_filter(
        $webform->getElementsDecodedAndFlattened(),
        fn($item) => $item['#type'] === 'grants_attachments'
      );

      $applicationTypeAttachmentFieldNames = [];
      foreach ($attachmentElements as $attachmentFieldName => $item) {
        $applicationTypeAttachmentFieldNames[$attachmentFieldName] = (int) $item['#filetype'];
      }
      self::$attachmentFieldNames[$applicationType] = $applicationTypeAttachmentFieldNames;
    }

    if ($preventKeys) {
      return self::$attachmentFieldNames[$applicationType];
    }
    return array_keys(self::$attachmentFieldNames[$applicationType]);
  }

  /**
   * Delete attachments that user removed from ATV.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $submittedFormData
   *   User submitted form data.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function deleteRemovedAttachmentsFromAtv(FormStateInterface $form_state, array &$submittedFormData): void {
    $storage = $form_state->getStorage();
    $auditLogService = $this->auditLogService;

    // Early exit in case no remove is found.
    if (!isset($storage['deleted_attachments']) || !is_array($storage['deleted_attachments'])) {
      return;
    }

    $removeAttachmentFromData = function ($deletedAttachmentInfo) use (&$submittedFormData) {

      // Remove attachment from submitted data.
      $attachmentFieldKeys = ['muu_liite', 'attachments'];

      foreach ($attachmentFieldKeys as $fieldKey) {
        foreach ($submittedFormData[$fieldKey] as $key => $attachment) {
          if (
            (isset($attachment["integrationID"]) &&
              $attachment["integrationID"] != NULL) &&
            $attachment["integrationID"] == $deletedAttachmentInfo['integrationID']
          ) {
            unset($submittedFormData['attachments'][$key]);
          }
        }
      }
    };

    // Loop records and delete them from ATV.
    foreach ($storage['deleted_attachments'] as $deletedAttachment) {

      if (empty($deletedAttachment['integrationID'])) {
        continue;
      }

      $cleanIntegrationId = AttachmentHandler::cleanIntegrationId(
        $deletedAttachment['integrationID']
      );

      try {

        $this->atvService->deleteAttachmentViaIntegrationId(
          $cleanIntegrationId
        );

        $attachmentHeaders = GrantsAttachments::$fileTypes;
        $attachmentFieldDescription = $attachmentHeaders[$deletedAttachment['fileType']];

        // Create event for deletion.
        $event = EventsService::getEventData(
          'HANDLER_ATT_DELETED',
          $submittedFormData['application_number'],
          $this->t('Attachment deleted from the field: @field.',
            ['@field' => $attachmentFieldDescription]
          ),
          $cleanIntegrationId
        );
        // Add event.
        $submittedFormData['events'][] = $event;

        $removeAttachmentFromData($deletedAttachment);

        $message = [
          "operation" => "GRANTS_APPLICATION_ATTACHMENT_DELETE",
          "status" => "SUCCESS",
          "target" => [
            "id" => '',
            "type" => $deletedAttachment['fileType'],
            "name" => $cleanIntegrationId,
          ],
        ];
        $auditLogService->dispatchEvent($message);

      }
      catch (AtvDocumentNotFoundException $e) {
        $this->logger->error('Tried to delete an attachment which was not found in ATV (id: %id document: $doc): %msg', [
          '%msg' => $e->getMessage(),
          '%id' => $cleanIntegrationId,
          '%document' => $submittedFormData['application_number'],
        ]);
        $removeAttachmentFromData($deletedAttachment);
      }
      catch (\Exception $e) {
        $this->logger->error('Failed to remove attachment (id: %id document: $doc): %msg', [
          '%msg' => $e->getMessage(),
          '%id' => $cleanIntegrationId,
          '%document' => $submittedFormData['application_number'],
        ]);

        $message = [
          "operation" => "GRANTS_APPLICATION_ATTACHMENT_DELETE",
          "status" => "FAILED",
          "target" => [
            "id" => '',
            "type" => $deletedAttachment['fileType'],
            "name" => $cleanIntegrationId,
          ],
        ];
        $auditLogService->dispatchEvent($message);
      }
    }
  }

  /**
   * Parse attachments from submitted data and create schema structured data.
   *
   * @param array $form
   *   Form in question.
   * @param array $submittedFormData
   *   Submitted form data. Passed as reference so both events & attachments
   *   can be added.
   * @param string $applicationNumber
   *   Generated application number.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\grants_handler\EventException
   */
  public function parseAttachments(
    array $form,
    array &$submittedFormData,
    string $applicationNumber): void {

    $attachmentHeaders = GrantsAttachments::$fileTypes;
    $attachmentFields = self::getAttachmentFieldNames($submittedFormData["application_number"], TRUE);
    foreach ($attachmentFields as $attachmentFieldName => $descriptionKey) {
      $field = $submittedFormData[$attachmentFieldName];

      // See if we have a webform field.
      if (isset($form['elements']['lisatiedot_ja_liitteet']['liitteet'][$attachmentFieldName])) {
        $wfElement = $form['elements']['lisatiedot_ja_liitteet']['liitteet'][$attachmentFieldName];
        // If field has title, use it. It's already translated here.
        if (isset($wfElement['#title'])) {
          $descriptionValue = $wfElement['#title'];
        }
        else {
          $descriptionValue = $attachmentHeaders[$descriptionKey];
        }
      }
      else {
        // If no title field present, use hard coded value.
        $descriptionValue = $attachmentHeaders[$descriptionKey];
      }

      $fileType = NULL;

      // Since we have to support multiple field elements, we need to
      // handle all as they were a multifield.
      $args = [];
      if (isset($field[0]) && is_array($field[0])) {
        $args = $field;
      }
      else {
        $args[] = $field;
      }

      // Loop args & create attachement field.
      foreach ($args as $fieldElement) {
        if (is_array($fieldElement)) {

          if (isset($fieldElement["fileType"]) && $fieldElement["fileType"] !== "") {
            $fileType = $fieldElement["fileType"];
          }
          else {
            // @todo Is this really necessary. Please, please try to debug so that this can be removed.
            if (isset($form["elements"]["lisatiedot_ja_liitteet"]["liitteet"][$attachmentFieldName]["#filetype"])) {
              $fileType = $form["elements"]["lisatiedot_ja_liitteet"]["liitteet"][$attachmentFieldName]["#filetype"];
            }
            else {
              $fileType = '0';
            }
          }

          // Get attachment structure & possible event.
          $attachment = $this->getAttachmentByFieldValue(
            $fieldElement, $descriptionValue, $fileType, $applicationNumber);

          if (!empty($attachment['attachment'])) {
            $attachmentExists = array_filter(
              $submittedFormData['attachments'],
              function ($item) use ($attachment) {
                // If we have integration ID, we have uploaded attachment
                // and we want to compare that.
                if (isset($item['integrationID']) && isset($attachment['attachment']['integrationID'])) {
                  if ($item['integrationID'] == $attachment['attachment']['integrationID']) {
                    return TRUE;
                  }
                }
                // If no upload, then compare descriptions.
                else {
                  if (isset($item['description']) && isset($attachment['attachment']['description'])) {
                    if ($item['description'] == $attachment['attachment']['description']) {
                      return TRUE;
                    }
                  }
                }
                // If no match.
                return FALSE;
              });
            // No attachment at all.
            if (empty($attachmentExists)) {
              $submittedFormData['attachments'][] = $attachment['attachment'];
            }
            else {
              // We had existing attachment, but we need to update it with
              // the data from this form.
              foreach ($submittedFormData['attachments'] as $key => $att) {
                if (isset($att['description']) && isset($attachment['attachment']['description'])) {
                  if ($att['description'] == $attachment['attachment']['description']) {
                    $submittedFormData['attachments'][$key] = $attachment['attachment'];
                  }
                }
              }
            }
          }
          // Also set event.
          // There is no event if attachment is uploaded.
          if (!empty($attachment['event'])) {
            $submittedFormData['events'][] = $attachment['event'];
          }
        }
      }
    }

    if (isset($submittedFormData["account_number"])) {
      try {
        $this->handleBankAccountConfirmation(
          $submittedFormData["account_number"],
          $applicationNumber,
          $submittedFormData
        );
      }
      catch (TempStoreException | GuzzleException $e) {
        $this->logger->error('Error: %msg', [
          '%msg' => $e->getMessage(),
        ]);
      }
    }
  }

  /**
   * The handleBankAccountConfirmation method.
   *
   * This method attaches a properly formatted bank
   * account attachment to the $submittedFormData array.
   * This is done either by:
   *
   * A. Finding an already existing attachment in the
   * $submittedFormData data and in ATV. If one is
   * found, then nothing is done.
   *
   * B. Uploading a new attachment if one does not exist,
   * or if the selected bank account has changed for
   * the application, or if the application is being
   * copied.
   *
   * @param string $accountNumber
   *   Bank account in question.
   * @param string $applicationNumber
   *   This application.
   * @param array $submittedFormData
   *   Full array of attachment information.
   * @param bool $copyingProcess
   *   A boolean indicating if the method has been
   *   called when copying an application.
   */
  public function handleBankAccountConfirmation(
    string $accountNumber,
    string $applicationNumber,
    array &$submittedFormData,
    bool $copyingProcess = FALSE): void {
    if (empty($accountNumber) || empty($applicationNumber)) {
      return;
    }

    try {
      $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
      $grantsProfileDocument = $this->grantsProfileService->getGrantsProfile($selectedCompany);
      $profileContent = $grantsProfileDocument->getContent();
    }
    catch (GrantsProfileException $e) {
      $this->logger->error('Error: %msg', ['%msg' => $e->getMessage()]);
      $this->messenger->addError($this->t('Failed to load user.', [], ['context' => 'grants_attachments']));
      return;
    }

    // Get the selected account.
    $selectedAccount = $this->getSelectedAccount($profileContent, $accountNumber);
    if (!$selectedAccount || !isset($selectedAccount['confirmationFile'])) {
      return;
    }

    // Get the selected accounts bank account attachment.
    $selectedAccountConfirmation = $grantsProfileDocument->getAttachmentForFilename($selectedAccount['confirmationFile']);
    if (!$selectedAccountConfirmation) {
      return;
    }

    // Load the ATV document.
    $applicationDocument = $this->getAtvDocument($applicationNumber);
    if (!$applicationDocument) {
      return;
    }

    // Check if a user changed the bank account in the application.
    $dataDefinition = ApplicationHandler::getDataDefinition($applicationDocument->getType());
    $existingData = $this->atvSchema->documentContentToTypedData(
      $applicationDocument->getContent(),
      $dataDefinition,
      $applicationDocument->getMetadata()
    );

    $existingAccountNumber = $existingData['account_number'] ?? FALSE;
    $accountHasChanged = FALSE;
    if ($existingAccountNumber && $existingAccountNumber !== $accountNumber) {
      $applicationDocument = $this->deletePreviousAccountConfirmation(
        $existingData,
        $applicationDocument,
        $existingAccountNumber
      );
      $accountHasChanged = TRUE;
    }

    // Look for an already existing bank account confirmation file.
    // Only done if the account has not changed, we are not copying
    // an application, and the ATV document has existing attachments.
    $applicationHasConfirmationFile = FALSE;
    $attachmentsInAtv = $applicationDocument->getAttachments();
    if (!$accountHasChanged && !$copyingProcess && !empty($attachmentsInAtv)) {
      $applicationHasConfirmationFile = $this->hasExistingBankAccountConfirmation(
        $submittedFormData,
        $selectedAccountConfirmation,
        $attachmentsInAtv
      );
    }

    // If an existing bank account confirmation does not exist,
    // or the account has changed, or the application is being copied,
    // then upload a new one.
    if (!$applicationHasConfirmationFile || $accountHasChanged || $copyingProcess) {
      $fileArray = $this->uploadNewBankAccountConfirmationToAtv(
        $applicationDocument,
        $selectedAccount,
        $selectedAccountConfirmation,
        $submittedFormData,
        $accountNumber,
        $applicationNumber
      );
      if (!empty($fileArray)) {
        $this->addFileArrayToFormData($submittedFormData, $fileArray);
      }
    }
  }

  /**
   * The getAtvDocument method.
   *
   * This method loads an ATV document with the
   * given application number.
   *
   * @param string $applicationNumber
   *   The application number.
   *
   * @return \Drupal\helfi_atv\AtvDocument|bool
   *   An application document if one is found,
   *   FALSE otherwise.
   */
  protected function getAtvDocument(string $applicationNumber): AtvDocument|bool {
    try {
      $applicationDocumentResults = $this->atvService->searchDocuments([
        'transaction_id' => $applicationNumber,
        'lookfor' => 'appenv:' . ApplicationHandler::getAppEnv(),
      ]);
      return reset($applicationDocumentResults);
    }
    catch (AtvDocumentNotFoundException | AtvFailedToConnectException | GuzzleException $e) {
      $this->logger->error('Error: %msg', ['%msg' => $e->getMessage()]);
      $this->messenger->addError($this->t('Failed to load document.', [], ['context' => 'grants_attachments']));
      return FALSE;
    }
  }

  /**
   * The uploadNewBankAccountConfirmationToAtv method.
   *
   * This method uploads a new bank account confirmation file
   * to ATV and returns a formatted array with said data.
   * The $submittedFormData array is also updated with
   * a new HANDLER_ATT_OK event.
   *
   * @param \Drupal\helfi_atv\AtvDocument $applicationDocument
   *   The ATV document.
   * @param array $selectedAccount
   *   The selected account.
   * @param array $selectedAccountConfirmation
   *   The selected account bank confirmation file.
   * @param array $submittedFormData
   *   The submitted form data.
   * @param string $accountNumber
   *   The selected bank account number.
   * @param string $applicationNumber
   *   The application number.
   *
   * @return array
   *   An array containing bank account confirmation data,
   *   or an empty array.
   */
  protected function uploadNewBankAccountConfirmationToAtv(
    AtvDocument $applicationDocument,
    array $selectedAccount,
    array $selectedAccountConfirmation,
    array &$submittedFormData,
    string $accountNumber,
    string $applicationNumber): array {
    try {
      $file = $this->atvService->getAttachment($selectedAccountConfirmation['href']);
      $uploadResult = $this->atvService->uploadAttachment(
        $applicationDocument->getId(),
        $selectedAccountConfirmation["filename"],
        $file
      );

      if ($uploadResult) {
        $submittedFormData['events'][] = EventsService::getEventData(
          'HANDLER_ATT_OK',
          $applicationNumber,
          $this->t('Attachment uploaded for the IBAN: @iban.', ['@iban' => $accountNumber]),
          $selectedAccountConfirmation["filename"],
        );

        $file->delete();

        return [
          'description' => $this->t('Confirmation for account @accountNumber',
            ['@accountNumber' => $selectedAccount["bankAccount"]], ['context' => 'grants_attachments'])->render(),
          'fileName' => $selectedAccountConfirmation["filename"],
          'isNewAttachment' => TRUE,
          'fileType' => 45,
          'isDeliveredLater' => FALSE,
          'isIncludedInOtherFile' => FALSE,
          'integrationID' => self::getIntegrationIdFromFileHref($uploadResult['href']),
        ];
      }
    }
    catch (GuzzleException | AtvDocumentNotFoundException | AtvFailedToConnectException | EntityStorageException | EventException $e) {
      $this->logger->error('Error: %msg', ['%msg' => $e->getMessage()]);
      $this->messenger->addError($this->t('Bank account confirmation file attachment failed.', [], ['context' => 'grants_attachments']));
    }
    return [];
  }

  /**
   * The hasExistingBankAccountConfirmation method.
   *
   * This method attempts to determine if a submitted
   * form already has an existing bank account confirmation file.
   * This is done by:
   *
   * 1. Looking for bank account confirmation files
   * in the "attachments" and "muu_liite" sections in the form data.
   *
   * 2. Extracting an integration ID from any found attachments.
   *
   * 3. Comparing the extracted ID against existing attachment IDs
   * in ATV.
   *
   * @param array $submittedFormData
   *   The submitted form data.
   * @param array $selectedAccountConfirmation
   *   The selected accounts bank account confirmation file.
   * @param array $attachmentsInAtv
   *   The attachments in ATV.
   *
   * @return bool
   *   TRUE if an existing bank account attachment is found
   *   in the form data and the ATV data. FALSE otherwise.
   */
  protected function hasExistingBankAccountConfirmation(
    array $submittedFormData,
    array $selectedAccountConfirmation,
    array $attachmentsInAtv): bool {

    $allFormAttachments = [];
    if (isset($submittedFormData['attachments'])) {
      $allFormAttachments[] = $submittedFormData['attachments'];
    }
    if (isset($submittedFormData['muu_liite'])) {
      $allFormAttachments[] = $submittedFormData['muu_liite'];
    }
    foreach ($allFormAttachments as $attachments) {
      $foundConfirmation = $this->hasBankAccountConfirmationInFormData($attachments, $selectedAccountConfirmation);
      if (!$foundConfirmation || !isset($foundConfirmation['integrationID'])) {
        continue;
      }
      $integrationId = $this->extractIntegrationIdFromIntegrationUrl($foundConfirmation['integrationID']);
      if ($integrationId && $this->hasBankAccountConfirmationInAtv($integrationId, $attachmentsInAtv)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * The hasBankAccountConfirmationInFormData method.
   *
   * This method loops through passed in attachment data and
   * looks for an already uploaded bank account confirmation file.
   * If one is found, we return it.
   *
   * @param array $attachmentData
   *   The submitted attachment data.
   * @param array $selectedAccountConfirmation
   *   The selected accounts bank account confirmation file.
   *
   * @return array|bool
   *   An already existing bank account attachment, or
   *   FALSE.
   */
  protected function hasBankAccountConfirmationInFormData(
    array $attachmentData,
    array $selectedAccountConfirmation): array|bool {
    foreach ($attachmentData as $attachment) {
      if (!is_array($attachment)) {
        continue;
      }
      if (!isset($attachment['fileName']) || !isset($attachment['fileType'])) {
        continue;
      }
      if ($attachment['fileName'] === $selectedAccountConfirmation['filename'] && (int) $attachment['fileType'] === 45) {
        return $attachment;
      }
    }
    return FALSE;
  }

  /**
   * The hasBankAccountConfirmationInAtv method.
   *
   * This method loops through all the attachment
   * already present in an ATV document. Each attachment's
   * ID is compared against an integration ID ($integrationId).
   *
   * @param string $integrationId
   *   The integration ID we are looking for.
   * @param array $atvAttachments
   *   An array of attachments in ATV.
   *
   * @return bool
   *   True if an attachment with the requested integration
   *   ID is found, FALSE otherwise.
   */
  protected function hasBankAccountConfirmationInAtv(string $integrationId, array $atvAttachments): bool {
    foreach ($atvAttachments as $attachment) {
      if ($attachment['id'] == $integrationId) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * The extractIntegrationIdFromIntegrationUrl method.
   *
   * This method extracts an integration ID from an
   * integration URL. An integration URL can look
   * something like this:
   *
   * "/local/v3/attachments/697828fd-f2e8-4a17-9a85/attachments/14689/"
   *
   * And the extracted value would then be "14689".
   *
   * @param string $integrationUrl
   *   An integration ID url.
   *
   * @return string|bool
   *   An integration ID if one is found, FALSE otherwise.
   */
  protected function extractIntegrationIdFromIntegrationUrl(string $integrationUrl): string|bool {
    $parts = explode('/attachments/', $integrationUrl);
    $integrationId = rtrim(end($parts), '/');
    if (filter_var($integrationId, FILTER_VALIDATE_INT)) {
      return $integrationId;
    }
    return FALSE;
  }

  /**
   * The getSelectedAccount method.
   *
   * This method loops through all the accounts on a
   * profile and returns the one whose bank account number
   * matches $accountNumber.
   *
   * @param array $profileContent
   *   An array of profile data.
   * @param string $accountNumber
   *   An account number.
   *
   * @return array|bool
   *   The found account or FALSE.
   */
  protected function getSelectedAccount(array $profileContent, string $accountNumber): array|bool {
    foreach ($profileContent['bankAccounts'] as $account) {
      if ($account['bankAccount'] == $accountNumber) {
        return $account;
      }
    }
    return FALSE;
  }

  /**
   * The addFileArrayToFormData method.
   *
   * This method formats the submitted form data and
   * adds in the $fileArray.
   * The following things are done:
   *
   * 1. Remove all bank account attachments form
   * the form data.
   *
   * 2. Convert bank account confirmation integration ID to
   * match with the current environment.
   *
   * 3. Add in the new bank account confirmation and sort the data.
   *
   * @param array $submittedFormData
   *   The submitted form data. Note that this is
   *   passed by reference.
   * @param array $fileArray
   *   The new bank account confirmation file.
   */
  protected function addFileArrayToFormData(array &$submittedFormData, array $fileArray): void {
    foreach ($submittedFormData['attachments'] as $key => $attachment) {
      if ((int) $attachment['fileType'] === 45) {
        unset($submittedFormData['attachments'][$key]);
      }
    }
    if (isset($fileArray['integrationID'])) {
      $fileArray['integrationID'] = self::addEnvToIntegrationId($fileArray['integrationID']);
    }
    $submittedFormData['attachments'][] = $fileArray;
    $submittedFormData['attachments'] = array_values($submittedFormData['attachments']);
  }

  /**
   * The deletePreviousAccountConfirmation method.
   *
   * This method deletes an old bank account attachment from
   * ATV in cases where the selected account has been changed.
   *
   * @param array $applicationData
   *   The existing data from ATV.
   * @param \Drupal\helfi_atv\AtvDocument $atvDocument
   *   The ATV document.
   * @param string $existingAccountNumber
   *   The existing bank account number whose file we are deleting.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   A modified version of the ATV document.
   */
  protected function deletePreviousAccountConfirmation(
    array $applicationData,
    AtvDocument $atvDocument,
    string $existingAccountNumber): AtvDocument {
    $bankAccountAttachment = array_filter($applicationData['muu_liite'], fn($item) => $item['fileType'] === '45');
    $bankAccountAttachment = reset($bankAccountAttachment);

    if ($bankAccountAttachment) {
      try {
        $integrationId = self::cleanIntegrationId($bankAccountAttachment['integrationID']);
        $this->atvService->deleteAttachmentViaIntegrationId($integrationId);

        $this->eventService->logEvent(
          $applicationData["application_number"],
          'HANDLER_ATT_DELETE',
          $this->t('Attachment removed for the IBAN: @iban.',
            ['@iban' => $existingAccountNumber],
            ['context' => 'grants_attachments'],
          ),
          $integrationId
        );

        $atvDocument = $this->atvService->getDocument($atvDocument->getId(), TRUE);
      }
      catch (AtvDocumentNotFoundException | AtvFailedToConnectException | TokenExpiredException | GuzzleException | EventException $e) {
        $this->logger->error(
          'Error deleting bank account attachment: @attachment. Error: @error', [
            '@attachment' => $bankAccountAttachment['integrationID'],
            '@error' => $e->getMessage(),
          ]
        );
      }
    }
    return $atvDocument;
  }

  /**
   * Extract attachments from form data.
   *
   * @param array $field
   *   The field parsed.
   * @param string $fieldDescription
   *   The field description from form element title.
   * @param string $fileType
   *   Filetype id from element configuration.
   * @param string $applicationNumber
   *   Application number for attachment.
   *
   * @return \stdClass[]
   *   Data for JSON.
   *
   * @throws \Drupal\grants_handler\EventException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function getAttachmentByFieldValue(
    array $field,
    string $fieldDescription,
    string $fileType,
    string $applicationNumber
  ): array {

    $event = NULL;
    $retval = [
      'description' => (isset($field['description']) && $field['description'] !== "") ? $field['description'] : $fieldDescription,
    ];
    $retval['fileType'] = (int) $fileType;
    // We have uploaded file. THIS time. Not previously.
    if (isset($field['attachment']) && $field['attachment'] !== NULL && !empty($field['attachment'])) {

      $file = $this->fileStorage->load($field['attachment']);
      if ($file) {
        // Add file id for easier usage in future.
        $this->attachmentFileIds[] = $field['attachment'];

        // Maybe delete file here also?
        $retval['fileName'] = $file->getFilename();
        $retval['isNewAttachment'] = TRUE;
        $retval['isDeliveredLater'] = FALSE;
        $retval['isIncludedInOtherFile'] = FALSE;

        if (isset($field["integrationID"]) && $field["integrationID"] !== "") {
          $retval['integrationID'] = $field["integrationID"];
        }

        $event = EventsService::getEventData(
          'HANDLER_ATT_OK',
          $applicationNumber,
          $this->t('Attachment uploaded to the field: @field.',
            ['@field' => $fieldDescription]
          ),
          $retval['fileName']
        );

        // Delete file entity from Drupal.
        $file->delete();

      }
    }
    else {
      // If other filetype and no attachment already set, we don't add them to
      // retval since we don't want to fill attachments with empty other files.
      if (($fileType === "0" || $fileType === '45') && empty($field["attachmentName"])) {
        return [];
      }
      // No matter upload status, we need to set up fileName always if the
      // attachmentName is present.
      if (isset($field['attachmentName'])) {
        $retval['fileName'] = $field["attachmentName"];
      }

      if (isset($field['fileStatus']) && $field['fileStatus'] === 'justUploaded') {
        $event = EventsService::getEventData(
          'HANDLER_ATT_OK',
          $applicationNumber,
          $this->t('Attachment uploaded to the field: @field.',
            ['@field' => $fieldDescription]
          ),
          $retval['fileName']
        );
      }

      switch ($field['fileStatus']) {

        case '':
        case 'new':
          if (isset($field['isDeliveredLater'])) {
            $retval['isDeliveredLater'] = $field['isDeliveredLater'] === "1";
          }
          if (isset($field['isIncludedInOtherFile'])) {
            $retval['isIncludedInOtherFile'] = $field['isIncludedInOtherFile'] === "1";
          }

          $retval['isNewAttachment'] = TRUE;
          break;

        case 'justUploaded':
          $retval['isDeliveredLater'] = FALSE;
          $retval['isIncludedInOtherFile'] = FALSE;
          $retval['isNewAttachment'] = TRUE;
          break;

        case 'uploaded':
          $retval['isDeliveredLater'] = FALSE;
          $retval['isIncludedInOtherFile'] = FALSE;
          $retval['isNewAttachment'] = FALSE;
          break;

        case 'deliveredLater':
        case 'otherFile':
          if (isset($field['isDeliveredLater'])) {
            $retval['isDeliveredLater'] = $field['isDeliveredLater'] === "1";
            $retval['isNewAttachment'] = FALSE;
          }
          else {
            $retval['isDeliveredLater'] = '0';
            $retval['isNewAttachment'] = FALSE;
          }

          if (isset($field['isIncludedInOtherFile'])) {
            $retval['isIncludedInOtherFile'] = $field['isIncludedInOtherFile'] === "1";
          }
          else {
            $retval['isIncludedInOtherFile'] = '0';
          }
          break;

        default:
          $retval['isDeliveredLater'] = FALSE;
          $retval['isIncludedInOtherFile'] = FALSE;
          $retval['isNewAttachment'] = FALSE;
          break;

      }

      if (isset($field["integrationID"]) && $field["integrationID"] !== "") {
        $retval['integrationID'] = $field["integrationID"];
        $retval['isDeliveredLater'] = FALSE;
        $retval['isIncludedInOtherFile'] = FALSE;
      }
    }

    return [
      'attachment' => $retval,
      'event' => $event,
    ];
  }

  /**
   * Find out what attachments are uploaded and what are not.
   *
   * @return array
   *   Attachments sorted by upload status.
   */
  public static function attachmentsUploadStatus(AtvDocument $document): array {
    $attachments = $document->getAttachments();
    $content = $document->getContent();

    $contentAttachments = $content["attachmentsInfo"]["attachmentsArray"] ?? [];

    $uploadedByContent = array_filter($contentAttachments, function ($item) {
      foreach ($item as $itemArray) {
        if ($itemArray['ID'] === 'fileName') {
          return TRUE;
        }
      }
      return FALSE;
    });

    $up = [];
    $not = [];

    foreach ($uploadedByContent as $ca) {

      $filesInContent = array_filter($ca, function ($caItem) {
        if ($caItem['ID'] === 'fileName') {
          return TRUE;
        }
        else {
          return FALSE;
        }
      });
      $fn1 = reset($filesInContent);
      $fn = $fn1['value'];

      $attFound = FALSE;

      foreach ($attachments as $v) {
        if (str_contains($v['filename'], $fn)) {
          $attFound = TRUE;
        }
      }

      if ($attFound) {
        $up[] = $fn;
      }
      else {
        $not[] = $fn;
      }
    }

    return [
      'uploaded' => $up,
      'not-uploaded' => $not,
    ];
  }

  /**
   * Get attachment upload time from events.
   *
   * @param array $events
   *   Events of the submission.
   * @param string $fileName
   *   Attachment file from submission data.
   *
   * @return string
   *   File upload time.
   *
   * @throws \Exception
   */
  public static function getAttachmentUploadTime(array $events, string $fileName): string {
    $dtString = '';
    $event = array_filter(
      $events,
      function ($item) use ($fileName) {
        if ($item['eventTarget'] == $fileName) {
          return TRUE;
        }
        return FALSE;
      }
    );
    $event = reset($event);
    if ($event) {
      $dt = new \DateTime($event['timeCreated']);
      $dt->setTimezone(new \DateTimeZone('Europe/Helsinki'));
      $dtString = $dt->format('d.m.Y H:i');
    }
    return $dtString;
  }

  /**
   * Adds current environment to file integration id.
   *
   * @param mixed $integrationID
   *   File integrqtion ID.
   *
   * @return mixed|string
   *   Updated integration ID.
   */
  public static function addEnvToIntegrationId(mixed $integrationID): mixed {

    $appParam = ApplicationHandler::getAppEnv();

    $atvVersion = getenv('ATV_VERSION');
    $removeBeforeThis = '/' . $atvVersion;

    $integrationID = strstr($integrationID, $removeBeforeThis);

    if ($appParam === 'PROD') {
      return $integrationID;
    }

    $addThis = '/' . $appParam;
    return $addThis . $integrationID;
  }

  /**
   * Remove environment things from integration ID. Most things will not work.
   *
   * @param mixed $integrationID
   *   File integration id.
   *
   * @return mixed|string
   *   Cleaned id.
   */
  public static function cleanIntegrationId(mixed $integrationID): mixed {
    $atvVersion = getenv('ATV_VERSION');
    $removeBeforeThis = '/' . $atvVersion;

    return strstr($integrationID, $removeBeforeThis);
  }

  /**
   * Clean domains from integration IDs.
   *
   * @param string $href
   *   Attachment url in ATV.
   *
   * @return string
   *   Cleaned url
   */
  public static function getIntegrationIdFromFileHref(string $href): string {
    $atvService = \Drupal::service('helfi_atv.atv_service');
    $baseUrl = $atvService->getBaseUrl();
    $baseUrlApps = str_replace('agw', 'apps', $baseUrl);
    // Remove server url from integrationID.
    $integrationId = str_replace($baseUrl, '', $href);
    return str_replace($baseUrlApps, '', $integrationId);
  }

}
