<?php

namespace Drupal\grants_attachments;

/**
 * Trait to add debug functionality.
 */
trait DebuggableTrait {

  /**
   * Is debug on?
   *
   * @var bool
   */
  protected bool $debug = FALSE;

  /**
   * Is debug on?
   *
   * @return bool
   *   Is debug on?
   */
  public function isDebug(): bool {
    return $this->debug;
  }

  /**
   * Set debug or get from env.
   *
   * @param bool $debug
   *   Debug value.
   *
   * @return bool
   *   Debug value.
   */
  public function setDebug(mixed $debug): bool {
    if ($debug === NULL) {
      $debug = getenv('debug');
    }

    if ($debug == 'true') {
      $this->debug = TRUE;
    }
    elseif (is_bool($debug)) {
      $this->debug = $debug;
    }
    else {
      $this->debug = FALSE;
    }

    return $this->debug;
  }

}
