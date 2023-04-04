<?php

namespace Drupal\grants_profile\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber to alter core user routes.
 *
 * @package Drupal\openid_connect_logout_redirect\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Reroute the user.page route.
    if ($route = $collection->get('user.page')) {
      $route->setDefault('_controller', '\Drupal\grants_profile\Controller\GrantsProfileController::redirectToMyServices');
    }
    // Reroute the entity.user.canonical.
    if ($route = $collection->get('entity.user.canonical')) {
      $route->setDefault('_controller', '\Drupal\grants_profile\Controller\GrantsProfileController::redirectToMyServices');
    }
  }

}
