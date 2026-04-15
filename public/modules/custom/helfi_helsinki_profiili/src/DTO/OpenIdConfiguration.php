<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili\DTO;

/**
 * Open id configuration DTO.
 */
final readonly class OpenIdConfiguration {

  public function __construct(
    public string $token_endpoint,
    public string $jwks_uri,
  ) {
  }

}
