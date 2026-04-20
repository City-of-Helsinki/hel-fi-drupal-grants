<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_helsinki_profiili\Unit\Helper;

use Drupal\helfi_helsinki_profiili\Helper\JwtHelper;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests JwtHelper class.
 */
#[Group('helfi_helsinki_profiili')]
class JwtHelperTest extends UnitTestCase {

  /**
   * Builds a JWT-like token string from a payload array.
   *
   * @phpstan-param array<string, mixed> $payload
   */
  private static function buildToken(array $payload): string {
    $header = base64_encode('{"alg":"RS256","typ":"JWT"}');
    $body = base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    $signature = base64_encode('fake-signature');
    return "$header.$body.$signature";
  }

  /**
   * Data provider for valid tokens.
   *
   * @phpstan-return array<string, array{array<string, mixed>}>
   */
  public static function validTokenDataProvider(): array {
    return [
      'basic payload' => [
        ['sub' => '1234567890', 'name' => 'Test User', 'iat' => 1516239022],
      ],
    ];
  }

  /**
   * Data provider for invalid tokens.
   *
   * @phpstan-return array<string, array{string, string}>
   */
  public static function invalidTokenDataProvider(): array {
    $header = base64_encode('{"alg":"RS256"}');

    return [
      'two parts' => ['header.body', 'Invalid token'],
      'single segment' => ['not-a-jwt', 'Invalid token'],
      'empty string' => ['', 'Invalid token'],
      'invalid json body' => [
        "$header." . base64_encode('not-valid-json') . '.signature',
        'Syntax error',
      ],
      'non-array json body' => [
        "$header." . base64_encode('"just a string"') . '.signature',
        'Invalid token',
      ],
    ];
  }

  /**
   * Tests that parseToken returns the decoded payload for valid tokens.
   *
   * @phpstan-param array<string, mixed> $payload
   */
  #[DataProvider('validTokenDataProvider')]
  public function testParseTokenReturnsDecodedPayload(array $payload): void {
    $result = JwtHelper::parseToken(self::buildToken($payload));
    $this->assertEquals($payload, $result);
  }

  /**
   * Tests that parseToken throws on invalid input.
   */
  #[DataProvider('invalidTokenDataProvider')]
  public function testParseTokenThrowsOnInvalidInput(string $token, string $expectedMessage): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($expectedMessage);
    JwtHelper::parseToken($token);
  }

}
