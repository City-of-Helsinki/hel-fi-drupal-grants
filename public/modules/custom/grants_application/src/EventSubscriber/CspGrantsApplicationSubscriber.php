<?php

declare(strict_types=1);

namespace Drupal\grants_application\EventSubscriber;

use Drupal\csp\Csp;
use Drupal\helfi_platform_config\EventSubscriber\CspSubscriberBase;

/**
 * Event subscriber for CSP policy alteration.
 *
 * @package Drupal\grants_application\EventSubscriber
 */
class CspGrantsApplicationSubscriber extends CspSubscriberBase {

  const SCRIPT_SRC = [Csp::POLICY_UNSAFE_EVAL];

}
