<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili\Helper;

/**
 * Helper class for JWT token operations.
 */
class JwtHelper {

  /**
   * Parse JWT token.
   *
   * @param string $token
   *   The encoded ID token containing the user data.
   *
   * @return array<string, mixed>
   *   The parsed JWT token.
   *
   * @throws \InvalidArgumentException
   */
  public static function parseToken(string $token): array {
    $parts = explode('.', $token, 3);

    try {
      if (count($parts) === 3) {
        [, $body] = $parts;

        $decoded = json_decode(base64_decode($body), TRUE, flags: JSON_THROW_ON_ERROR);
        if (is_array($decoded)) {
          return $decoded;
        }
      }
    }
    catch (\JsonException $e) {
      throw new \InvalidArgumentException($e->getMessage(), previous: $e);
    }

    throw new \InvalidArgumentException("Invalid token");
  }

}
