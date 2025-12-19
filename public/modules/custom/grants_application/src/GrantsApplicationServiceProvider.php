<?php

declare(strict_types=1);

namespace Drupal\grants_application;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\grants_application\EventSubscriber\CspGrantsApplicationSubscriber;
use Drupal\helfi_platform_config\HelfiPlatformConfigServiceProvider;

/**
 * A service provider.
 */
final class GrantsApplicationServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) : void {
    HelfiPlatformConfigServiceProvider::registerCspEventSubscribers($container, [
      CspGrantsApplicationSubscriber::class,
    ]);
  }

}
