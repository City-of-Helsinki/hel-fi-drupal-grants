<?php

namespace Drupal\grants_attachments;

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
use Drupal\grants_handler\EventsService;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_audit_log\AuditLogService;
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
   * Figure out if account confirmation file has been added to application.
   *
   * And if so, attach that file to this application for bank account
   * confirmation.
   *
   * @param string $accountNumber
   *   Bank account in question.
   * @param string $applicationNumber
   *   This application.
   * @param array $submittedFormData
   *   Full array of attachment information.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\grants_handler\EventException
   */
  public function handleBankAccountConfirmation(
    string $accountNumber,
    string $applicationNumber,
    array &$submittedFormData
  ): void {
    $tOpts = ['context' => 'grants_attachments'];

    // If no accountNumber is selected, do nothing.
    if (empty($accountNumber)) {
      return;
    }

    $atvSchema = $this->atvSchema;
    $applicationDocument = FALSE;
    $selectedAccount = FALSE;
    $selectedAccountConfirmationAttachment = FALSE;
    $selectedAccountConfirmationAttachmentFilename = FALSE;
    $accountChanged = FALSE;
    $accountConfirmationExists = FALSE;
    $accountConfirmationFile = [];
    $fileArray = [];

    // If we have account number, load details.
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    $grantsProfileDocument = $this->grantsProfileService->getGrantsProfile($selectedCompany);
    $profileContent = $grantsProfileDocument->getContent();

    // Find selected account details from profile content.
    foreach ($profileContent['bankAccounts'] as $account) {
      if ($account['bankAccount'] == $accountNumber) {
        $selectedAccount = $account;
        $selectedAccountConfirmationAttachment = $grantsProfileDocument->getAttachmentForFilename($selectedAccount['confirmationFile']);
        $selectedAccountConfirmationAttachmentFilename = $selectedAccountConfirmationAttachment['filename'] ?? FALSE;
        break;
      }
    }

    // If we can't find the selected account, do nothing.
    if (!$selectedAccount || !$selectedAccountConfirmationAttachment) {
      return;
    }

    // Look for the application document from ATV.
    try {
      $applicationDocumentResults = $this->atvService->searchDocuments([
        'transaction_id' => $applicationNumber,
        'lookfor' => 'appenv:' . ApplicationHandler::getAppEnv(),
      ]);
      /** @var \Drupal\helfi_atv\AtvDocument $applicationDocument */
      $applicationDocument = reset($applicationDocumentResults);

      $dataDefinition = ApplicationHandler::getDataDefinition($applicationDocument->getType());
      $existingData = $atvSchema->documentContentToTypedData(
        $applicationDocument->getContent(),
        $dataDefinition,
        $applicationDocument->getMetadata()
      );

      // Check if the user has changed bank accounts. We want to delete old confirmations.
      $existingAccountNumber = $existingData['account_number'];

      if (isset($existingAccountNumber) && $existingAccountNumber !== $accountNumber) {
        $accountChanged = TRUE;
        $applicationDocument = $this->deletePreviousAccountConfirmation($existingData, $applicationDocument, $existingAccountNumber);
      }
    }
    catch (AtvDocumentNotFoundException | AtvFailedToConnectException | GuzzleException $e) {
      $this->logger->error(
          'Error loading application document. Application number: @appno. Error: @error',
          ['@appno' => $applicationNumber, '@error' => $e->getMessage()]
      );
    }

    // If we don't have the ATV document, do nothing.
    if (!$applicationDocument) {
      return;
    }

    // Attempt to find the bank account confirmation from the ATV document.
    /*$applicationAttachments = $applicationDocument->getAttachments();
    foreach ($applicationAttachments as $attachment) {
      if (isset($attachment['id']) && $selectedAccountConfirmationAttachmentId == $attachment['id']) {
        $accountConfirmationExists = TRUE;
        $accountConfirmationFile = $attachment;
        break;
      }
    }*/

    // Look under the "attachments" section.
    if (isset($submittedFormData['attachments'])) {
      $foundInAttachments = array_filter($submittedFormData['attachments'], function ($fn) use($selectedAccountConfirmationAttachmentFilename) {
        if (!is_array($fn)) {
          return FALSE;
        }
        if (!isset($fn['fileName']) || !isset($fn['fileType'])) {
          return FALSE;
        }
        // File type comparison for files inside the "attachments" section.
        if ($fn['fileName'] === $selectedAccountConfirmationAttachmentFilename && (int) $fn['fileType'] === 45) {
          return TRUE;
        }
        return FALSE;
      });
      if (!empty($foundInAttachments)) {
        $accountConfirmationExists = TRUE;
        $accountConfirmationFile = reset($foundInAttachments);
      }
    }

    // If we can't find a bank account confirmation file,
    // or the bank account has been changed, then upload a
    // confirmation file to ATV,
    if (!$accountConfirmationExists || $accountChanged) {

      try {
        $file = $this->atvService->getAttachment($selectedAccountConfirmationAttachment['href']);
        $uploadResult = $this->atvService->uploadAttachment(
          $applicationDocument->getId(),
          $selectedAccountConfirmationAttachment["filename"], $file
        );

        if ($uploadResult) {
          $integrationID = self::getIntegrationIdFromFileHref($uploadResult['href']);

          // Add the upload event to the form data.
          $submittedFormData['events'][] = EventsService::getEventData(
            'HANDLER_ATT_OK',
            $applicationNumber,
            $this->t('Attachment uploaded for the IBAN: @iban.', ['@iban' => $accountNumber]),
            $file->getFilename()
          );

          // Add account confirmation to attachment array.
          $fileArray = [
            'description' => $this->t('Confirmation for account @accountNumber', ['@accountNumber' => $selectedAccount["bankAccount"]], $tOpts)->render(),
            'fileName' => $selectedAccountConfirmationAttachment["filename"],
            // IsNewAttachment controls upload to Avustus2.
            'isNewAttachment' => TRUE,
            'fileType' => 45,
            'isDeliveredLater' => FALSE,
            'isIncludedInOtherFile' => FALSE,
          ];
          // Delete the file since we don't want to store it.
          $file->delete();
        }
      }
      catch (\Exception $e) {
        $this->logger->error('Error: %msg', ['%msg' => $e->getMessage()]);
        $this->messenger->addError($this->t('Bank account confirmation file attachment failed.', [], $tOpts));
      }
    }
    else {
      $integrationID = $accountConfirmationFile['integrationID'];
      $fileArray = [
        'description' => $this->t('Confirmation for account @accountNumber', ['@accountNumber' => $selectedAccount["bankAccount"]], $tOpts)->render(),
        'fileName' => $accountConfirmationFile["fileName"],
        'isNewAttachment' => TRUE,
        'fileType' => 45,
        'isDeliveredLater' => FALSE,
        'isIncludedInOtherFile' => FALSE,
      ];
    }

    if (!empty($fileArray)) {

      // Remove old bank account confirmation files.
      foreach ($submittedFormData['attachments'] as $key => $value) {
        if ((int) $value['fileType'] === 45) {
          unset($submittedFormData['attachments'][$key]);
        }
      }
      // Modify the integration ID to match the environment.
      if (!empty($integrationID)) {
        $fileArray['integrationID'] = self::addEnvToIntegrationId($integrationID);
      }

      $submittedFormData['attachments'][] = $fileArray;
      $submittedFormData['attachments'] = array_values($submittedFormData['attachments']);
    }
  }

  /**
   * Delete old bank account confirmation file before adding a new one.
   *
   * @param array $applicationData
   *   Full data set to extract from.
   * @param \Drupal\helfi_atv\AtvDocument $atvDocument
   *   Document.
   * @param string $existingAccountNumber
   *   The existing bank account number whose file we are deleting.
   *
   * @return false|mixed
   *   Found value or false
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException|\Drupal\grants_handler\EventException
   */
  public function deletePreviousAccountConfirmation(
    array $applicationData,
    AtvDocument $atvDocument,
    string $existingAccountNumber): mixed {
    $tOpts = ['context' => 'grants_attachments'];

    $atvService = $this->atvService;
    $eventService = $this->eventService;

    $bankAccountAttachment = array_filter($applicationData['muu_liite'], fn($item) => $item['fileType'] === '45');
    $bankAccountAttachment = reset($bankAccountAttachment);
    if ($bankAccountAttachment) {

      // Since deleting attachments is incostintent,
      // make sure we return updated document.
      $integrationId = self::cleanIntegrationId($bankAccountAttachment['integrationID']);
      $atvService->deleteAttachmentViaIntegrationId($integrationId);

      $eventService->logEvent(
        $applicationData["application_number"],
        'HANDLER_ATT_DELETE',
        $this->t('Attachment removed for the IBAN: @iban.',
          ['@iban' => $existingAccountNumber],
          $tOpts
        ),
        $integrationId
      );

      return $atvService->getDocument($atvDocument->getId(), TRUE);
    }
    else {
      return $atvDocument;
    }
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
