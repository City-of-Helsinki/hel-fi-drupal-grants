<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_handler\Kernel;

use Drupal\helfi_atv\AtvDocument;

/**
 * Test WebformSubmissionNotesHelper class.
 */
class ApplicationInitServiceTest extends GrantsHandlerKernelTestBase {

  /**
   * Test delete after.
   */
  public function testDeleteAfter(): void {
    $service = $this->container->get('grants_handler.application_init_service');
    $settings = [
      'applicationClose' => '2027-06-06',
      'applicationContinuous' => FALSE,
    ];
    $atvDocument = $this->getAtvDocument();

    // A month from close date.
    $service->setDeleteAfter($settings, $atvDocument);
    $this->assertEquals('2027-07-06', $atvDocument->getDeleteAfter());

    $settings = [
      'applicationClose' => '',
      'applicationContinuous' => TRUE,
    ];
    // A year from now.
    $service->setDeleteAfter($settings, $atvDocument);
    $this->assertEquals(date('Y-m-d', strtotime('+1 year')), $atvDocument->getDeleteAfter());
  }

  /**
   * Create an atv document.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   The atv document.
   */
  private function getAtvDocument(): AtvDocument {
    return AtvDocument::create([
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
      'created_at' => '2026-06-06',
      'updated_at' => '2026-06-06',
      'user_id' => 'userId',
      'locked_after' => '2024-06-08',
      'deletable' => TRUE,
      'document_language' => 'fi',
      'content_schema_url' => 'schemaURL',
    ]);
  }

}
