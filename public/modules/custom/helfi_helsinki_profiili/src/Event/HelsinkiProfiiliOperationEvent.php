<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event submission create.
 */
class HelsinkiProfiiliOperationEvent extends Event {

  public function __construct(
    private readonly string $name,
  ) {}

  /**
   * Get the name of the operation.
   */
  public function getName(): string {
    return $this->name;
  }

}
