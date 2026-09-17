<?php

namespace Drupal\grants_application\Drush\Commands;

use Drupal\grants_application\Helper;

/**
 * Restrict to certain environments.
 */
trait EnvironmentRestrictionTrait {

  /**
   * The environments the command may run in.
   *
   * Anything starting with "LOCAL" is allowed as well.
   */
  protected const ALLOWED_ENVIRONMENTS = ['DEV', 'TEST', 'STAGE'];

  /**
   * Checks whether the command may run in the given environment.
   *
   * @return bool
   *   TRUE when the command may run.
   */
  private function isEnvironmentAllowed(): bool {
    $appEnv = Helper::getAppEnv();
    // Helper::getAppEnv() passes an unrecognised value through as it is, and
    // local environments are named freely, so compare in upper case.
    $appEnv = strtoupper($appEnv);

    return in_array($appEnv, self::ALLOWED_ENVIRONMENTS, TRUE) || str_starts_with($appEnv, 'LOCAL');
  }

}
