<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili\DTO;

/**
 * User data decoded from the Helsinki Profile ID token (JWT).
 */
final readonly class HelsinkiProfiiliUser {

  public function __construct(
    public string $sub,
    public string $name,
    public string $given_name,
    public string $family_name,
    public string $email,
    public AuthenticationLevel $loa = AuthenticationLevel::None,
    public ?string $sid = NULL,
    public ?int $exp = NULL,
  ) {
  }

  /**
   * Create from a decoded JWT payload array.
   *
   * @param array<string, mixed> $payload
   *   The decoded JWT payload.
   */
  public static function fromArray(array $payload): self {
    return new self(
      sub: $payload['sub'],
      name: $payload['name'],
      given_name: $payload['given_name'],
      family_name: $payload['family_name'],
      email: $payload['email'],
      loa: AuthenticationLevel::fromLoa($payload['loa'] ?? NULL),
      sid: $payload['sid'] ?? NULL,
      exp: isset($payload['exp']) ? (int) $payload['exp'] : NULL,
    );
  }

}
