<?php

namespace Drupal\grants_handler;

trait DebuggableTrait {

  protected bool $debug = FALSE;

  /**
   * Is debug on?
   *
   * @return bool
   */
  public function isDebug(): bool {
    return $this->debug;
  }

  /**
   * Set debug or get from env.
   *
   * @param bool $debug
   *
   * @return bool
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
