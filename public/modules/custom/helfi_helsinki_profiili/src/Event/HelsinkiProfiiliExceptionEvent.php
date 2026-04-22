<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event submission create.
 */
class HelsinkiProfiiliExceptionEvent extends Event {

  public function __construct(
    private readonly \Throwable $exception,
  ) {}

  /**
   * Get the exception.
   */
  public function getException(): \Throwable {
    return $this->exception;
  }

}
