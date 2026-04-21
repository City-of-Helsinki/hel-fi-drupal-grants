<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili\DTO;

/**
 * Helsinki Profiili token DTO.
 */
final readonly class ProfiiliToken {

  public function __construct(
    public string $access_token,
    public int $expires_in,
  ) {
  }

}
