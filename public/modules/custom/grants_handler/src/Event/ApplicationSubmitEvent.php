<?php

declare(strict_types=1);

namespace Drupal\grants_handler\Event;

use Drupal\Component\EventDispatcher\Event;
use Drupal\grants_handler\ApplicationSubmitType;

/**
 * Event that is fired when a grants application is submitted.
 *
 * This is intended to allow other parts of the system to react to form
 * submissions in a ways avoids adding more dependencies to submission
 * logic.
 */
final class ApplicationSubmitEvent extends Event {

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\grants_handler\ApplicationSubmitType $type
   *   Submit type.
   */
  public function __construct(
    public readonly ApplicationSubmitType $type,
  ) {
  }

}
