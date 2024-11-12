<?php

namespace Drupal\Tests\grants_handler\Unit;

use Drupal\grants_handler\ApplicationHelpers;

/**
 * A subclass to expose protected methods for testing.
 */
class ApplicationHelpersExposed extends ApplicationHelpers {

  /**
   * Expose the protected method getApplicationNumberInEnvFormatOldFormat.
   *
   * @param string $appParam
   *   The application parameter.
   * @param string $typeId
   *   The type ID.
   * @param string $serial
   *   The serial number.
   *
   * @return string
   *   The application number in the old format.
   */
  public static function exposedGetApplicationNumberInEnvFormatOldFormat(string $appParam, string $typeId, string $serial): string {
    return self::getApplicationNumberInEnvFormatOldFormat($appParam, $typeId, $serial);
  }

  /**
   * Expose the protected method getApplicationNumberInEnvFormat.
   *
   * @param string $appParam
   *   The application parameter.
   * @param string $typeId
   *   The type ID.
   * @param string $serial
   *   The serial number.
   *
   * @return string
   *   The application number in the new format.
   */
  public static function exposedGetApplicationNumberInEnvFormat(string $appParam, string $typeId, string $serial) {
    return self::getApplicationNumberInEnvFormat($appParam, $typeId, $serial);
  }

}
