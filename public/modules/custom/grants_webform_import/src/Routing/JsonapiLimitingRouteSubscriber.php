<?php

namespace Drupal\grants_webform_import\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class JsonapiLimitingRouteSubscriber.
 *
 * Allow only users with json_api_user role to access
 * JSON API routes.
 */
class JsonapiLimitingRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Limit access to all jsonapi routes with an extra permission.
    foreach ($collection as $route) {
      $defaults = $route->getDefaults();
      if (!empty($defaults['_is_jsonapi'])) {
        $route->setRequirement('_role', 'json_api_user');
      }
    }
  }

}
