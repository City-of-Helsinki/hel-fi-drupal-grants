<?php

namespace Drupal\grants_industries\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a 'WebformRouteSubscriber' route subscriber.
 *
 * This route subscriber adds access checks to various
 * webform routes. The targeted routes can be found in
 * the $adminOnlyWebformRoutes and $restrictedWebformRoutes
 * properties.
 */
class WebformRouteSubscriber extends RouteSubscriberBase {

  /**
   * An array of webform routes that are considered
   * to be admin routes.
   *
   * @var array $adminOnlyWebformRoutes
   */
  protected array $adminOnlyWebformRoutes = [
    'entity.webform.edit_form',
    'entity.webform.test_form',
    'entity.webform.results_submissions',
    'entity.webform.settings_form',
    'entity.webform.settings_submissions',
    'entity.webform.settings_confirmation',
    'entity.webform.handlers',
    'entity.webform.settings_access',
    'entity.webform_submission.collection',
    'entity.webform_options.collection',
    'webform.config',
    'webform.addons',
    'webform.help',
  ];

  /**
   * An array of routes that are restricted
   * and therefore require further access checking.
   *
   * @var array $restrictedWebformRoutes
   */
  protected array $restrictedWebformRoutes = [
    'entity.webform.settings',
  ];

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Add an access check to all admin routes.
    foreach ($this->adminOnlyWebformRoutes as $route) {
      if ($collectionRoute = $collection->get($route)) {
        $requirements = $collectionRoute->getRequirements();
        $requirements['_webform_admin_route_access_check'] = 'TRUE';
        $collectionRoute->setRequirements($requirements);
      }
    }

    // Add an access check to all the restricted routes.
    foreach ($this->restrictedWebformRoutes as $route) {
      if ($collectionRoute = $collection->get($route)) {
        $requirements = $collectionRoute->getRequirements();
        $requirements['_webform_restricted_route_access_check'] = 'TRUE';
        $collectionRoute->setRequirements($requirements);
      }
    }
  }

}
