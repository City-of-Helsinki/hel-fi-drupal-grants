<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Trait;

use Drupal\helfi_atv\AtvDocument;

trait AtvDocumentTrait {

  /**
   * Default atv document for testing.
   *
   * @param string $application_number
   *   The application number.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   Atv document for testing.
   */
  public function getAtvDocument(string $application_number): AtvDocument {
    $document = AtvDocument::create([
      'id' => 'test-id',
      'type' => 'type',
      'status' => [
        'value' => 'DRAFT',
      ],
      'status_histories' => [
        'DRAFT',
      ],
      'transaction_id' => '1234567890',
      'business_id' => '1234567-1',
      'tos_function_id' => '12345',
      'tos_record_id' => '54321',
      'draft' => TRUE,
      'human_readable_type' => ['humanType'],
      'metadata' => '{"name": "Name", "value": "Value"}',
      'content' => '{"data": "content"}',
      'created_at' => '2024-06-06',
      'updated_at' => '2024-06-07',
      'user_id' => 'userId',
      'locked_after' => '2024-06-08',
      'deletable' => TRUE,
      'delete_after' => '2075-01-01',
      'document_language' => 'fi',
      'content_schema_url' => 'schemaURL',
    ]);
    $document->setContent([
      'compensation' => [],
      'formUpdate' => FALSE,
      'events' => [
        [
          "caseId" => $application_number,
          "eventType" => "MESSAGE_READ",
          "eventCode" => 0,
          "eventSource" => "GrantsApplications",
          "timeUpdated" => "2026-05-06T12:02:09",
          "timeCreated" => "2026-05-06T12:02:09",
          "eventDescription" => "Viesti merkitty luetuksi",
          "eventID" => "9355d811-d81b-4f66-bfda-15affd07734b",
          // Event target matches the second message messageId.
          "eventTarget" => "bbbbbbbb-4444-5555-6666-cccccccccccc",
        ],
      ],
      'messages' => [
        [
          'caseId' => $application_number,
          'messageId' => 'aaaaaaaa-1111-2222-3333-bbbbbbbbbbbbb',
          'body' => 'viesti viesti viesti',
          'sentBy' => 'Avustusten kasittelyjarjestelma',
          'sendDateTime' => '2026-05-06T08:40:37',
        ],
        [
          'caseId' => $application_number,
          'messageId' => 'bbbbbbbb-4444-5555-6666-cccccccccccc',
          'body' => 'Luettu viesti, jolla on mätsäävä eventti',
          'sentBy' => 'Avustusten kasittelyjarjestelma',
          'sendDateTime' => '2026-05-07T08:40:37',
        ],
      ],
    ]);
    return $document;
  }

}
