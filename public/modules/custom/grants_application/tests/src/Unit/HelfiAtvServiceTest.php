<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\Tests\UnitTestCase;

/**
 * @covers \Drupal\grants_application\Atv\HelfiAtvService
 *
 * @group grants_application
 */
final class HelfiAtvServiceTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    putenv('ATV_SERVICE=test-service');
    putenv('ATV_TOS_FUNCTION_ID=test-tos-function');
    putenv('ATV_TOS_RECORD_ID=test-tos-record');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    parent::tearDown();
    putenv('ATV_SERVICE');
    putenv('ATV_TOS_FUNCTION_ID');
    putenv('ATV_TOS_RECORD_ID');
  }

  /**
   * Test getAppEnv maps known APP_ENV values correctly.
   *
   * @dataProvider appEnvProvider
   */
  public function testGetAppEnv(string $input, string $expected): void {
    putenv("APP_ENV=$input");
    $this->assertSame($expected, HelfiAtvService::getAppEnv());
    putenv('APP_ENV');
  }

  /**
   * Data provider for testGetAppEnv.
   */
  public static function appEnvProvider(): array {
    return [
      ['development', 'DEV'],
      ['testing', 'TEST'],
      ['staging', 'STAGE'],
      ['production', 'PROD'],
      ['custom', 'custom'],
    ];
  }

  /**
   * Test that createAtvDocument stores schema_version in metadata.
   */
  public function testCreateAtvDocumentSetsSchemaVersion(): void {
    $document = HelfiAtvService::createAtvDocument(
      application_uuid: 'test-uuid',
      application_number: 'TEST-001',
      application_name: 'Test',
      application_type: 'test_type',
      application_title: 'Test Title',
      langcode: 'fi',
      sub: 'user-sub',
      company_identifier: 'company-id',
      copy: FALSE,
      selected_company: ['type' => 'registered_community', 'identifier' => 'company-id'],
      schemaVersion: '2',
    );

    $this->assertSame('2', $document->getMetadata()['schema_version']);
  }

  /**
   * Test that schema_version defaults to '1'.
   */
  public function testCreateAtvDocumentDefaultSchemaVersion(): void {
    $document = HelfiAtvService::createAtvDocument(
      application_uuid: 'test-uuid',
      application_number: 'TEST-001',
      application_name: 'Test',
      application_type: 'test_type',
      application_title: 'Test Title',
      langcode: 'fi',
      sub: 'user-sub',
      company_identifier: 'company-id',
      copy: FALSE,
      selected_company: ['type' => 'private_person', 'identifier' => 'person-id'],
    );

    $this->assertSame('1', $document->getMetadata()['schema_version']);
  }

}
