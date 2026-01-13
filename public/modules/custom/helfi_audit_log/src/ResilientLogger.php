<?php

declare(strict_types=1);

namespace Drupal\helfi_audit_log;

use ResilientLogger\ResilientLogger as ResilientLoggerBase;
use Drupal\Core\Site\Settings;

/**
 * Implements resilient logger.
 *
 * @phpstan-import-type ResilientLoggerOptions from \ResilientLogger\Types
 */
class ResilientLogger extends ResilientLoggerBase {

  /**
   * Create from settings.
   */
  public static function createFromSettings(Settings $settings): self {
    /** @var ResilientLoggerOptions $options */
    $options = $settings->get('resilient_logger', []);
    return self::create($options);
  }

}
