<?php

namespace Drupal\autologout_extend\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber to alter core and contrib routes.
 *
 * @package Drupal\autologout_extend\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Reroute the autologout.alt_logout.
    if ($route = $collection->get('autologout.alt_logout')) {
      $route->setDefault('_controller', '\Drupal\autologout_extend\Controller\AutoLogoutExtendController::altLogout');
    }
  }

}
