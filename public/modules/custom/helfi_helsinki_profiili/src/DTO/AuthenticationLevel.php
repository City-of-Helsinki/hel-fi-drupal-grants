<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili\DTO;

/**
 * Authentication level derived from the JWT "loa" (Level of Assurance) claim.
 *
 * This is based on eIDAS Levels of Assurance.
 *
 * @see https://ec.europa.eu/digital-building-blocks/sites/spaces/DIGITAL/pages/467110081/eIDAS+Levels+of+Assurance
 *
 * For some reason, we use custom names here and don't support "high" level.
 */
enum AuthenticationLevel: string {
  case Strong = 'strong';
  case Weak = 'weak';
  case None = 'noAuth';

  /**
   * Map a raw JWT "loa" claim value to an authentication level.
   */
  public static function fromLoa(?string $loa): self {
    return match ($loa) {
      'substantial' => self::Strong,
      'low' => self::Weak,
      default => self::None,
    };
  }

}
