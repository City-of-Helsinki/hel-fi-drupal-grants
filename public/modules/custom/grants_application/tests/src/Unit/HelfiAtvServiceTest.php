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

}
