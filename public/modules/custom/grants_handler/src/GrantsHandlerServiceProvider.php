<?php

namespace Drupal\grants_handler;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Overrides the form_error_handler service to enable inline form errors.
 */
class GrantsHandlerServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container->getDefinition('form_error_handler')
      ->setClass(FormErrorHandler::class)
      ->setArguments([
        new Reference('string_translation'),
        new Reference('renderer'),
        new Reference('messenger'),
      ]);
  }

}
