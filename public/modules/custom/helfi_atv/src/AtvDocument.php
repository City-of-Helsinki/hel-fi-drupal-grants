<?php

namespace Drupal\helfi_atv;

use Drupal\Component\Serialization\Json;

/**
 * Document model in ATV.
 */
final class AtvDocument implements \JsonSerializable {

  /**
   * Document UUID.
   *
   * @var string
   */
  protected string $id;

  /**
   * Created time.
   *
   * @var string
   */
  protected string $createdAt;

  /**
   * Updated time.
   *
   * @var string
   */
  protected string $updatedAt;

  /**
   * Document status.
   *
   * @var string
   */
  protected string $status;

  /**
   * Document status as array gotten from ATV.
   *
   * @var array
   */
  protected array $statusArray;

  /**
   * Document status history.
   *
   * @var array[]
   */
  protected array $statusHistory;

  /**
   * Document type.
   *
   * @var string
   */
  protected string $type;

  /**
   * Service name.
   *
   * @var string
   */
  protected string $service;

  /**
   * Full service details from service object in document.
   *
   * This allows us to use "service" field as we've done thus far.
   *
   * @var array
   */
  protected array $serviceDetails;

  /**
   * Transaction id.
   *
   * @var string
   */
  protected string $transactionId;

  /**
   * User id.
   *
   * @var string
   */
  protected string $userId;

  /**
   * Business id.
   *
   * @var string
   */
  protected string $businessId;

  /**
   * TOS function.
   *
   * @var string
   */
  protected string $tosFunctionId;

  /**
   * TOS record.
   *
   * @var string
   */
  protected string $tosRecordId;

  /**
   * Document metadata.
   *
   * @var array
   */
  protected array $metadata;

  /**
   * Is document draft.
   *
   * This will probably be deprecated at some point.
   *
   * @var bool
   */
  protected bool $draft;

  /**
   * Locked after, after this time editing of this document will be prohibited.
   *
   * @var string
   */
  protected string $lockedAfter;

  /**
   * Document language - ISO-639-1 - Max length 5 characters.
   *
   * @var string
   */
  protected ?string $documentLanguage;

  /**
   * Date after the document and related attachments are permanently deleted.
   *
   * @var string
   */
  protected ?string $deleteAfter = NULL;

  /**
   * Url for content schema.
   *
   * @var string
   */
  protected ?string $contentSchemaUrl;

  /**
   * Document content. Encrypted in ATV.
   *
   * @var array
   */
  protected array $content;

  /**
   * Document attachments.
   *
   * @var array
   */
  protected array $attachments;

  /**
   * Type strings with translations.
   *
   * @var array
   */
  protected array $humanReadableType;

  /**
   * Deletable flag for GDPR usage.
   *
   * @var bool
   */
  protected bool $deletable;

  /**
   * Create ATVDocument object from given values.
   *
   * @param array $values
   *   Values for document.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   Document created from values.
   */
  public static function create(array $values): AtvDocument {
    $object = new self();
    if (isset($values['id'])) {
      $object->id = $values['id'];
    }
    if (isset($values['type'])) {
      $object->type = $values['type'];
    }
    if (isset($values['service'])) {

      if (is_array($values['service'])) {
        $object->service = $values['service']['name'];
        $object->serviceDetails = $values['service'];
      }
      else {
        $object->service = $values['service'];
      }

    }
    if (isset($values['status'])) {
      if (is_array($values['status'])) {
        $object->status = $values['status']['value'];
        $object->statusArray = $values['status'];
      }
      else {
        $object->status = $values['status'];
      }
    }
    if (isset($values['status_histories'])) {
      if (is_array($values['status_histories'])) {
        $object->statusHistory = $values['status_histories'];
      }
    }
    if (isset($values['transaction_id'])) {
      $object->transactionId = $values['transaction_id'];
    }
    if (isset($values['business_id'])) {
      $object->businessId = $values['business_id'];
    }
    if (isset($values['tos_function_id'])) {
      $object->tosFunctionId = $values['tos_function_id'];
    }
    if (isset($values['tos_record_id'])) {
      $object->tosRecordId = $values['tos_record_id'];
    }
    if (isset($values['draft'])) {
      $object->draft = $values['draft'];
    }
    if (isset($values['human_readable_type'])) {
      $object->humanReadableType = $values['human_readable_type'];
    }
    if (isset($values['metadata'])) {
      // Make sure metadata is decoded if it's an string.
      if (is_string($values['metadata'])) {
        $object->metadata = Json::decode($values['metadata']);
      }
      else {
        $object->metadata = $values['metadata'];
      }
    }
    if (isset($values['content'])) {
      // Make sure content is decoded if it's an string.
      if (is_string($values['content'])) {
        $structure = self::parseContent($values['content']);
        if (is_array($structure)) {
          $object->content = $structure;
        }
        else {
          $object->content = [];
        }
      }
      else {
        $object->content = $values['content'];
      }
    }
    if (isset($values['created_at'])) {
      $object->createdAt = $values['created_at'];
    }
    if (isset($values['updated_at'])) {
      $object->updatedAt = $values['updated_at'];
    }
    if (isset($values['user_id'])) {
      $object->userId = $values['user_id'];
    }
    if (isset($values['locked_after'])) {
      $object->lockedAfter = $values['locked_after'];
    }
    if (isset($values['attachments'])) {
      $object->attachments = $values['attachments'];
    }
    if (isset($values['deletable'])) {
      $object->deletable = $values['deletable'];
    }
    if (isset($values['delete_after'])) {
      $object->deleteAfter = $values['delete_after'];
    }
    if (isset($values['document_language'])) {
      $object->documentLanguage = $values['document_language'];
    }
    if (isset($values['content_schema_url'])) {
      $object->contentSchemaUrl = $values['content_schema_url'];
    }

    return $object;
  }

  /**
   * Parse malformed json.
   *
   * @param string $contentString
   *   JSON to be checked.
   *
   * @return mixed
   *   Decoded JSON array.
   */
  public static function parseContent(string $contentString): mixed {
    $replaced = str_replace('False', 'false', $contentString);
    $replaced = str_replace('True', 'true', $replaced);
    $replaced = str_replace('None', '"none"', $replaced);

    return Json::decode($replaced);
  }

  /**
   * Helper function to json_encode to handle object values.
   *
   * @return array
   *   Array structure for this object.
   */
  public function jsonSerialize(): array {
    return $this->toArray();
  }

  /**
   * Encode this object to json.
   *
   * @return false|string
   *   This encoded in json.
   */
  public function toJson(): bool|string {
    return Json::encode($this);
  }

  /**
   * Helper function to be used with json.
   *
   * @return array
   *   This object exported to array struct.
   */
  public function toArray(): array {
    $json_array = [];

    if (isset($this->id)) {
      $json_array['id'] = $this->id;
    }

    if (isset($this->userId)) {
      $json_array['user_id'] = $this->getUserId();
    }
    if (isset($this->createdAt)) {
      $json_array['created_at'] = $this->createdAt;
    }
    if (isset($this->updatedAt)) {
      $json_array['updated_at'] = $this->getUpdatedAt();
    }
    if (isset($this->lockedAfter)) {
      $json_array['locked_after'] = $this->getLockedAfter();
    }
    if (isset($this->status)) {
      $json_array['status'] = $this->getStatus();
    }
    if (isset($this->statusArray)) {
      $json_array['status_array'] = $this->getStatusArray();
    }
    if (isset($this->statusHistory)) {
      $json_array['status_histories'] = $this->getStatusHistory();
    }
    if (isset($this->type)) {
      $json_array['type'] = $this->getType();
    }
    if (isset($this->transactionId)) {
      $json_array['transaction_id'] = $this->getTransactionId();
    }

    if (isset($this->businessId)) {
      $json_array['business_id'] = $this->getBusinessId();
    }
    if (isset($this->tosFunctionId)) {
      $json_array['tos_function_id'] = $this->getTosFunctionId();
    }
    if (isset($this->tosFunctionId)) {
      $json_array['tos_function_id'] = $this->getTosFunctionId();
    }
    if (isset($this->tosRecordId)) {
      $json_array['tos_record_id'] = $this->getTosRecordId();
    }
    if (isset($this->metadata)) {
      $json_array['metadata'] = $this->getMetadata();
    }
    if (isset($this->humanReadableType)) {
      $json_array['human_readable_type'] = $this->getHumanReadableType();
    }
    if (isset($this->content)) {
      $json_array['content'] = $this->getContent();
    }
    if (isset($this->draft)) {
      $json_array['draft'] = $this->getDraft();
    }
    if (isset($this->deletable)) {
      $json_array['deletable'] = $this->isDeletable();
    }
    if (isset($this->deleteAfter)) {
      $json_array['delete_after'] = $this->getDeleteAfter();
    }
    if (isset($this->documentLanguage)) {
      $json_array['document_language'] = $this->getDocumentLanguage();
    }
    if (isset($this->contentSchemaUrl)) {
      $json_array['content_schema_url'] = $this->getContentSchemaUrl();
    }

    return $json_array;
  }

  /**
   * Get document status history.
   *
   * @return array[]
   *   Document status history.
   */
  public function getStatusHistory(): array {
    return $this->statusHistory;
  }

  /**
   * Get service value.
   *
   * @return string
   *   Document service.
   */
  public function getService(): string {
    return $this->service;
  }

  /**
   * Get id.
   *
   * @return string
   *   Document ID.
   */
  public function getId(): string {
    return $this->id ?? '';
  }

  /**
   * Check if document is new.
   *
   * @return bool
   *   True if it is.
   */
  public function isNew(): bool {
    return empty($this->getId());
  }

  /**
   * Get creation time.
   *
   * @return string
   *   Document created time.
   */
  public function getCreatedAt(): string {
    return $this->createdAt;
  }

  /**
   * Get update time.
   *
   * @return string
   *   Document update time
   */
  public function getUpdatedAt(): string {
    return $this->updatedAt;
  }

  /**
   * Get document status.
   *
   * @return string
   *   Document status
   */
  public function getStatus(): string {
    return $this->status;
  }

  /**
   * Get document status.
   *
   * @return array
   *   Document status
   */
  public function getStatusArray(): array {
    return $this->statusArray;
  }

  /**
   * Get document type.
   *
   * @return string
   *   Document type.
   */
  public function getType(): string {
    return $this->type;
  }

  /**
   * Get transaction id.
   *
   * @return string
   *   Document transaction ID
   */
  public function getTransactionId(): string {
    return $this->transactionId;
  }

  /**
   * Get user id.
   *
   * @return string|null
   *   Document user id.
   */
  public function getUserId(): string|null {
    return $this->userId ?? NULL;
  }

  /**
   * Get business id.
   *
   * @return string
   *   Document business ID.
   */
  public function getBusinessId(): string {
    return $this->businessId;
  }

  /**
   * Get TOS function.
   *
   * @return string
   *   Document TOS function.
   */
  public function getTosFunctionId(): string {
    return $this->tosFunctionId;
  }

  /**
   * Get TOS record.
   *
   * @return string
   *   Document TOS record.
   */
  public function getTosRecordId(): string {
    return $this->tosRecordId;
  }

  /**
   * Get metadata.
   *
   * @return array
   *   Document metadata.
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

  /**
   * Set metadata.
   *
   * @param array $metadata
   *   New metadata.
   */
  public function setMetadata(array $metadata): void {
    $this->metadata = $metadata;
  }

  /**
   * Set metadata.
   *
   * @param string $status
   *   Status of document.
   */
  public function setStatus(string $status): void {
    $this->status = $status;
  }

  /**
   * Set document type.
   *
   * @param string $type
   *   Type string from application form type.
   */
  public function setType(string $type): void {
    $this->type = $type;
  }

  /**
   * Set metadata.
   *
   * @param string $key
   *   Metadata key.
   * @param mixed $value
   *   Metadata value for given key.
   */
  public function addMetadata(string $key, mixed $value): void {
    $this->metadata[$key] = $value;
  }

  /**
   * Get document draft status.
   *
   * @return bool
   *   Document draft status.
   */
  public function getDraft(): bool {
    return $this->draft;
  }

  /**
   * Get document locked after date.
   *
   * @return string
   *   Document locked after date.
   */
  public function getLockedAfter(): string {
    return $this->lockedAfter;
  }

  /**
   * Get document content.
   *
   * @return array
   *   Document content.
   */
  public function getContent(): array {
    $retval = $this->content;

    return $retval;
  }

  /**
   * Set document content.
   *
   * @param array $content
   *   Document content.
   */
  public function setContent(array $content): void {
    $this->content = $content;
  }

  /**
   * Get document attachments.
   *
   * @return array
   *   Document attachments.
   */
  public function getAttachments(): array {
    return $this->attachments;
  }

  /**
   * Get document attachments.
   *
   * @param string $filename
   *   Filename for attachment.
   *
   * @return array|bool
   *   Attachment details for given filename
   */
  public function getAttachmentForFilename(string $filename): array|bool {
    foreach ($this->getAttachments() as $attachment) {
      if ($attachment['filename'] == $filename) {
        return $attachment;
      }
    }
    return FALSE;
  }

  /**
   * Set transaction id.
   *
   * @param string $transactionId
   *   Transaction id.
   */
  public function setTransactionId(string $transactionId): void {
    $this->transactionId = $transactionId;
  }

  /**
   * Return huban readable type.
   *
   * @return array
   *   Type.
   */
  public function getHumanReadableType(): array {
    return $this->humanReadableType;
  }

  /**
   * Set human readable type.
   *
   * @param array $humanReadableType
   *   Set type.
   */
  public function setHumanReadableType(array $humanReadableType): void {
    $this->humanReadableType = $humanReadableType;
  }

  /**
   * Set service.
   *
   * @param string $service
   *   Service.
   */
  public function setService(string $service): void {
    $this->service = $service;
  }

  /**
   * Set user id.
   *
   * @param string $userId
   *   User id.
   */
  public function setUserId(string $userId): void {
    $this->userId = $userId;
  }

  /**
   * Set TOS function.
   *
   * @param string $tosFunctionId
   *   Tos function ID.
   */
  public function setTosFunctionId(string $tosFunctionId): void {
    $this->tosFunctionId = $tosFunctionId;
  }

  /**
   * Set Record ID.
   *
   * @param string $tosRecordId
   *   Record.
   */
  public function setTosRecordId(string $tosRecordId): void {
    $this->tosRecordId = $tosRecordId;
  }

  /**
   * Set business ID.
   *
   * @param string $businessId
   *   Business id.
   */
  public function setBusinessId(string $businessId): void {
    $this->businessId = $businessId;
  }

  /**
   * Set draft.
   *
   * @param bool $draft
   *   Is draft true/false.
   */
  public function setDraft(bool $draft): void {
    $this->draft = $draft;
  }

  /**
   * GDPR deletable flag.
   *
   * @return bool
   *   Is document deletable.
   */
  public function isDeletable(): bool {
    return $this->deletable;
  }

  /**
   * Set deletable flag.
   *
   * @param bool $deletable
   *   Is parameter deletable.
   */
  public function setDeletable(bool $deletable): void {
    $this->deletable = $deletable;
  }

  /**
   * Get service details from object.
   *
   * @return array
   *   Service details.
   */
  public function getServiceDetails(): array {
    return $this->serviceDetails;
  }

  /**
   * Get document language.
   *
   * @return null|string
   *   Document language in ISO-639-1 if set.
   */
  public function getDocumentLanguage(): ?string {
    return $this->documentLanguage;
  }

  /**
   * Set the document language.
   *
   * @param string $documentLanguage
   *   Language code in ISO-639-1 format and maximum 5 of characters.
   */
  public function setDocumentLanguage(string $documentLanguage): void {
    $this->documentLanguage = $documentLanguage;
  }

  /**
   * Get delete after date.
   *
   * @return null|string
   *   Date string if set.
   */
  public function getDeleteAfter(): ?string {
    return $this->deleteAfter;
  }

  /**
   * Set delete after timestamp.
   *
   * @param string $deleteAfter
   *   Date string which after the document and
   *   related attachments are permanently deleted.
   *
   *   For example "2022-12-12".
   */
  public function setDeleteAfter(string $deleteAfter): void {
    $this->deleteAfter = $deleteAfter;
  }

  /**
   * Set content schema url.
   *
   * @return null|string
   *   Url if set.
   */
  public function getContentSchemaUrl(): ?string {
    return $this->contentSchemaUrl;
  }

  /**
   * Set content schema url.
   *
   * @param string $contentSchemaUrl
   *   Link to content schema if available.
   */
  public function setContentSchemaUrl(string $contentSchemaUrl): void {
    $this->contentSchemaUrl = $contentSchemaUrl;
  }

}
