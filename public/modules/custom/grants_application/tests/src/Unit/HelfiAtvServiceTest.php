<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\Tests\UnitTestCase;

/**
 * @covers \Drupal\grants_application\Atv\HelfiAtvService
 *
 * @group grants_application
 */
final class HelfiAtvServiceTest extends UnitTestCase {

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
   * Test atv document creation.
   */
  public function testCreateAtvDocument(): void {
    $helfiAtvService = new HelfiAtvService(new MockAtvService());

    putenv('ATV_SERVICE=test-atv-service');
    putenv('ATV_TOS_FUNCTION_ID=test-tos-function-id');
    putenv('ATV_TOS_RECORD_ID=test-tos-record-id');

    $document = $helfiAtvService->createAtvDocument(
      'aaaaaaaa-bbbb-cccc-ddddd-eeeeeeeeeeee',
      'test-058-00001000',
      'Test application',
      '58',
      'unit test application',
      'fi',
      'aaaaaaaa-bbbb-cccc-ddddd-eeeeeeeeeeee',
      '7777480-7',
      FALSE,
      ['identifier' => '7777480-7', 'type' => 'registered_community'],
      'registered_community',
      TRUE
    );
    $this->assertInstanceOf(AtvDocument::class, $document);
  }

  /**
   * Test side document creation.
   */
  public function testCreateSideDocument(): void {
    $helfiAtvService = new HelfiAtvService(new MockAtvService());
    putenv('ATV_SERVICE=test-atv-service');
    putenv('ATV_TOS_FUNCTION_ID=test-tos-function-id');
    putenv('ATV_TOS_RECORD_ID=test-tos-record-id');

    $sideDocument = $helfiAtvService->createSideDocument(
      "58",
      'unit test application',
      'aaaaaaaa-bbbb-cccc-ddddd-eeeeeeeeeeee',
      ['identifier' => '7777480-7', 'type' => 'registered_community'],
      'aaaaaaaa-bbbb-cccc-ddddd-eeeeeeeeeeee',
    );
    $this->assertInstanceOf(AtvDocument::class, $sideDocument);
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

}
