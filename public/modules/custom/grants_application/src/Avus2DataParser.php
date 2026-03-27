<?php

namespace Drupal\grants_application;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\grants_handler\MessageService;
use Drupal\helfi_atv\AtvDocument;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Parse the handlers, states, files etc.
 */
final readonly class Avus2DataParser {

  public function __construct(
    #[Autowire(service: 'grants_handler.message_service')]
    private MessageService $messageService,
    private LanguageManagerInterface $languageManager,
  ) {
  }

  /**
   * Get list of people who have handled the application.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The value to validate, a result of json_decode function call.
   *
   * @return array
   *   Messages array.
   */
  public function getHandlers(AtvDocument $document): array {
    $content = $document->getContent();
    $handlerEvents = array_filter($content['events'], fn($event) => $event['eventType'] == 'EVENT_INFO');
    $handlers = [];

    foreach ($handlerEvents as $handlerEvent) {
      $handlers[] = explode(";", $handlerEvent['eventDescription']);
    }
    return $handlers;
  }

  /**
   * Get unread messages.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The atv document.
   *
   * @return array
   *   Messages array.
   */
  public function getUnreadMessages(AtvDocument $document): array {
    $content = $document->getContent();

    // grants_handler_preprocess_webform_submission_messages.
    $unreadMsg = [];
    foreach ($content['messages'] as $msg) {
      if (!isset($msg["messageStatus"]) || !$msg["messageStatus"]) {
        continue;
      }

      if ($msg["messageStatus"] == 'UNREAD' && $msg["sentBy"] == 'Avustusten kasittelyjarjestelma') {
        $unreadMsg[] = [
          '#theme' => 'message_notification_item',
          '#message' => $msg,
        ];
      }
    }

    return $unreadMsg;
  }

  /**
   * Get the read messages.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The atv document.
   *
   * @return array
   *   Read messages.
   */
  public function getReadMessages(AtvDocument $document): array {
    $content = $document->getContent();

    $messages = [];
    if (isset($content['messages']) && is_array($content['messages'])) {
      $submissionMessages = $this->messageService->parseMessages($content);
      foreach ($submissionMessages as $message) {
        $messages[] = $message;
      }
    }
    return $messages;
  }

  /**
   * Get submitted time from document events.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The atv document.
   *
   * @return string
   *   The submitted datetime.
   */
  public function getSubmitted(AtvDocument $document): string {
    $statusHistory = $document->getStatusHistory();
    $submitted = array_find($statusHistory, fn($item) => $item['value'] === 'SUBMITTED') ?? FALSE;
    if ($submitted) {
      $submitted = (new \DateTime($submitted['timestamp']))->format('d.m.Y H:i');
    }
    return $submitted ?: '';
  }

  /**
   * Get status history from status updates.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The atv document.
   *
   * @return array
   *   The history.
   */
  public function getHistory(AtvDocument $document): array {
    $content = $document->getContent();

    $history = [];
    if (isset($content["statusUpdates"]) && is_array($content['statusUpdates'])) {
      $langCode = $this->languageManager->getCurrentLanguage()->getId();
      $statusStrings = $this->getStatusStrings($langCode);
      foreach (array_reverse($content["statusUpdates"]) as $event) {
        if ($event["citizenCaseStatus"] != 'SUBMITTED') {
          $eventDate = new \DateTime($event['timeCreated']);
          $eventDate->setTimezone(new \DateTimeZone('Europe/Helsinki'));
          $translatedStatus = $statusStrings[$event['citizenCaseStatus']];
          $history[] = $translatedStatus . ': ' . $eventDate->format('d.m.Y H:i');
        }
      }
    }
    return $history;
  }

  /**
   * Get submitted attachments.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The atv document.
   *
   * @return array
   *   The submitted attachments.
   */
  public function getSubmittedAttachments(AtvDocument $document): array {
    $content = $document->getContent();
    $events = $document->getContent()['events'];

    $attachment_data = [];
    foreach ($document->getAttachments() as $att) {
      $filename = $att['filename'];
      $event = array_find($events, fn($event) => isset($event['eventTarget']) && $event['eventTarget'] === $filename);
      if ($event) {
        if (!isset($content['attachmentsInfo']['attachmentsArray'])) {
          continue;
        }
        $documentContentAttachment = array_find($content['attachmentsInfo']['attachmentsArray'], fn($a) => $a[2]['ID'] === 'fileType' && $a[1]['value'] === $filename);
        $description = $documentContentAttachment[0]['value'] . ', ' ?? '';
        $description = trim($description, ',');
        $submitted = $event['timeCreated'] ?? $event['eventCreated'];
        $submitted = (new \DateTime($submitted))->format('d.m.Y H:i');

        $submitted = !$submitted ? '' : $submitted . ': ';

        // Create a string: "file description, submitted: filename".
        $attachment_data[] = "$description$submitted$filename";
      }
    }

    return $attachment_data;
  }

  /**
   * Get all attachment-elements from application.
   *
   * @param AtvDocument $document
   *   The document.
   *
   * @return array
   *   Uploaded files.
   */
  public function getUploadedAttachments(AtvDocument $document): array {
    return $document->getAttachments();
  }

  /**
   * Get list of uploaded filenames.
   *
   * @param AtvDocument $document
   *   The document.
   *
   * @return array
   *
   */
  public function getUploadedAttachmentsFilenames(AtvDocument $document): array {
    $files = $this->getUploadedAttachments($document);
    return array_map(fn($file) => $file['filename'], $files);
  }

  /**
   * Get status string.
   *
   * @param string $langcode
   *   The langcode.
   *
   * @return array|null
   *   The status string array or null.
   */
  public function getStatusStrings(string $langcode): ?array {
    $statuses = [
      'en' => [
        'DRAFT' => 'Draft',
        'SENT' => 'Sent',
        'SUBMITTED' => 'Sent - waiting for confirmation',
        'RECEIVED' => 'Received',
        'PREPARING' => 'In Preparation',
        'PENDING' => 'Pending',
        'PROCESSING' => 'Processing',
        'READY' => 'Ready',
        'DONE' => 'Processed',
        'REJECTED' => 'Rejected',
        'DELETED' => 'Deleted',
        'CANCELED' => 'Cancelled',
        'CANCELLED' => 'Cancelled',
        'CLOSED' => 'Closed',
        'RESOLVED' => 'Processed',
      ],
      'fi' => [
        'DRAFT' => 'Luonnos',
        'SENT' => 'Lähetetty',
        'SUBMITTED' => 'Lähetetty - odotetaan vahvistusta',
        'RECEIVED' => 'Vastaanotettu',
        'PREPARING' => ' Valmistelussa',
        'PENDING' => ' Odottaa',
        'PROCESSING' => ' Käsittelyssä',
        'READY' => ' Valmiina',
        'RESOLVED' => ' Ratkaistu',
        'DONE' => ' Ratkaistu',
        'REJECTED' => ' Hylätty',
        'DELETED' => ' Poistettu',
        'CANCELED' => ' Peruttu',
        'CANCELLED' => ' Peruttu',
        'CLOSED' => ' Suljettu',
      ],
      'sv' => [
        'DRAFT' => 'Utkast',
        'SENT' => 'Skickad',
        'SUBMITTED' => 'Skickad - väntar på bekräftelse',
        'RECEIVED' => 'Mottagen',
        'PREPARING' => 'Förbereds',
        'PENDING' => 'I väntan på',
        'PROCESSING' => 'Behandlas',
        'READY' => 'Redo',
        'DONE' => 'Behandlad',
        'REJECTED' => 'Avvisade',
        'DELETED' => 'Raderade',
        'CANCELED' => 'Annullerad',
        'CANCELLED' => 'Annullerad',
        'CLOSED' => 'Stängd',
        'RESOLVED' => 'Behandlad',
      ],
    ];
    return $statuses[$langcode] ?? NULL;
  }

}
