<?php

namespace Drupal\grants_application;

/**
 * Random functionalities.
 */
class Helper {

  /**
   * Get current environment name.
   *
   * @return string
   *   Environment name.
   */
  public static function getAppEnv(): string {
    return match(getenv('APP_ENV')) {
      'development' => 'DEV',
      'testing' => 'TEST',
      'staging' => 'STAGE',
      'production' => 'PROD',
      default => getenv('APP_ENV'),
    };
  }

}
