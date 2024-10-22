<?php

declare(strict_types=1);

namespace Drupal\grants_attachments;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\helfi_atv\AtvDocument;

/**
 * Service for fixing attachments.
 */
final class AttachmentFixerService {

  use StringTranslationTrait;

  /**
   * Constructs an AttachmentFixerService object.
   */
  public function __construct() {}

  /**
   * Fix attachments on applications that has missing integration IDs.
   *
   * Possibly check other things missing as well.
   *
   * @param \Drupal\helfi_atv\AtvDocument $atvDoc
   *   The ATV document.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   Void.
   */
  public function fixAttachmentsOnApplication(AtvDocument $atvDoc): AtvDocument {
    // Load attachments from the document.
    $attachments = $atvDoc->getAttachments();
    // Get the application environment.
    $appEnv = $atvDoc->getMetadata()['appenv'];
    // Get the content from the document.
    $content = $atvDoc->getContent();
    // Get the events from the content.
    $events = $content['events'];
    // Get the attachment info from the content.
    $attachmentInfo = $content['attachmentsInfo']['attachmentsArray'];

    // Loop attachments and if attachment is not ok, update the integration ID.
    foreach ($attachments as $attachment) {
      if ($this->areAttachmentsOk($events, $attachment, $attachmentInfo, $appEnv)['form'] === FALSE) {
        $this->updateIntegrationIdForAttachment($attachment, $attachmentInfo, $appEnv);
      }
    }

    // Make sure we have labels for all fields.
    foreach ($attachmentInfo as $key => $info) {
      foreach ($info as $key2 => $item) {
        if (!isset($item['label'])) {
          // If not, use the ID as label.
          $attachmentInfo[$key][$key2]['label'] = $attachmentInfo[$key][$key2]['ID'];
        }
      }
    }

    $content['attachmentsInfo']['attachmentsArray'] = $attachmentInfo;
    $atvDoc->setContent($content);
    return $atvDoc;
  }

  /**
   * If integrationID is missing from application data, add it.
   *
   * @param array $attachment
   *   The attachment.
   * @param array $attachmentInfo
   *   The attachment info.
   * @param string $appEnv
   *   The application environment for this application.
   *
   * @return void
   *   Void.
   */
  private function updateIntegrationIdForAttachment(
    array $attachment,
    array &$attachmentInfo,
    string $appEnv,
  ): void {
    // Clean file href to integrationID format so that it'll work with ATV.
    $intID = '/' . $appEnv . AttachmentHandlerHelper::cleanIntegrationId($attachment['href']);

    // Loop attachment fields that are added to the application.
    foreach ($attachmentInfo as &$innerArray) {
      $fileNameMatched = FALSE;
      $integrationIdUpdated = FALSE;

      foreach ($innerArray as &$item) {
        if ($item['ID'] === 'fileName' && $item['value'] === $attachment['filename']) {
          $fileNameMatched = TRUE;
        }
        if ($fileNameMatched && $item['ID'] === 'integrationID') {
          // Update integrationID in place.
          $item['value'] = $intID;
          // Set the value to control adding new integrationID.
          $integrationIdUpdated = TRUE;
          break;
        }
      }
      // If filename matched but no integrationID was found, add it.
      if ($fileNameMatched && !$integrationIdUpdated) {
        $innerArray[] = [
          'ID' => 'integrationID',
          'label' => 'Integration ID',
          'value' => $intID,
          'valueType' => 'string',
          'meta' => "[]",
        ];
      }
    }
  }

  /**
   * Try to figure our if attachments are ok.
   *
   * This is a helper function for fixAttachmentsOnApplication that goes through
   * the events and attachment info to see if the attachments are added properly
   * to the document.
   *
   * @param array $events
   *   Events.
   * @param array $attachment
   *   Attachment.
   * @param array $attachmentInfo
   *   Attachment info.
   * @param string $appEnv
   *   Application environment.
   *
   * @return array
   *   Array of booleans.
   */
  public function areAttachmentsOk(
    array $events,
    array $attachment,
    array $attachmentInfo,
    string $appEnv,
  ): array {
    // Look for events from us, the handler.
    $handlerOk = $this->filterEventsByTypeAndFilename($events, 'HANDLER_ATT_OK', $attachment['filename']);
    // Look for events from AVUSTUS2.
    $avus2Ok = $this->filterEventsByTypeAndFilename($events, 'AVUSTUS2_ATT_OK', $attachment['filename']);
    // Look for errors from AVUSTUS2.
    $avus2Error = $this->filterEventsByTypeAndFilename($events, 'AVUSTUS2_ATT_ERROR', $attachment['filename']);
    // Are attachments added properly to form data.
    $formOk = $this->checkAttachmentInfo($attachmentInfo, $attachment, $appEnv);

    return [
      'handler' => !empty($handlerOk),
      'avus2' => !empty($avus2Ok),
      'avus2Errors' => $avus2Error,
      'form' => !empty($formOk),
    ];
  }

  /**
   * Filter events.
   *
   * @param array $events
   *   Events.
   * @param string $eventType
   *   Event type.
   * @param string $filename
   *   Filename.
   *
   * @return array
   *   Filtered events.
   */
  private function filterEventsByTypeAndFilename(
    array $events,
    string $eventType,
    string $filename,
  ): array {
    return array_filter($events, function ($event) use ($eventType, $filename) {
      return $event['eventType'] === $eventType && $event['eventTarget'] === $filename;
    });
  }

  /**
   * Check attachment info for existing intergation ID.
   *
   * @param array $attachmentInfo
   *   Attachment info.
   * @param array $attachment
   *   Attachment.
   * @param string $appEnv
   *   Application environment.
   *
   * @return bool
   *   True if found, false otherwise.
   */
  private function checkAttachmentInfo(
    array $attachmentInfo,
    array $attachment,
    string $appEnv,
  ): bool {
    // If no attachments, nothing to check.
    if (empty($attachmentInfo)) {
      return FALSE;
    }

    // Loop attachment info and check if we have the attachment added.
    foreach ($attachmentInfo as $info) {
      // Check if filename and integrationID are found.
      $filenameFound = $this->findValueById($info, 'fileName', $attachment['filename']);
      $intFound = $this->findIntegrationId($info, $attachment, $appEnv);
      // Return true if both are found.
      if ($filenameFound && $intFound) {
        return TRUE;
      }
    }
    // If not found, return false.
    return FALSE;
  }

  /**
   * Find value by id.
   *
   * @param array $info
   *   Info.
   * @param string $id
   *   Id.
   * @param string $value
   *   Value.
   *
   * @return bool
   *   True if found, false otherwise.
   */
  private function findValueById(
    array $info,
    string $id,
    string $value,
  ): bool {
    foreach ($info as $item) {
      if ($item['ID'] === $id && $item['value'] === $value) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Find integration id.
   *
   * @param array $info
   *   Info.
   * @param array $attachment
   *   Attachment.
   * @param string $appEnv
   *   Application environment.
   *
   * @return bool
   *   True if found, false otherwise.
   */
  private function findIntegrationId(
    array $info,
    array $attachment,
    string $appEnv,
  ): bool {
    $targetId = '/' . $appEnv . AttachmentHandlerHelper::cleanIntegrationId($attachment['href']);
    return $this->findValueById($info, 'integrationID', $targetId);
  }

}
