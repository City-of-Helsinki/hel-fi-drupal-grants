<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili;

/**
 * Profiili user data request failure.
 */
class ProfileDataException extends ProfiiliException {

  public function __construct(string $message, public readonly mixed $errors) {
    parent::__construct($message);
  }

}
