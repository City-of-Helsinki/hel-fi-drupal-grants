<?php

namespace Drupal\grants_industries\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a 'WebformRouteSubscriber' route subscriber.
 *
 * This route subscriber adds access checks to various
 * webform routes. The targeted routes can be found in
 * the $blockedRoutes and $restrictedRoutes properties.
 */
class WebformRouteSubscriber extends RouteSubscriberBase {

  /**
   * An array of webform routes that are blocked
   * unless the user has a certain role.
   *
   * @var array $blockedRoutes
   */
  protected array $blockedRoutes = [
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
   * An array of routes that restricted and
   * therefore require access checking.
   *
   * @var array $restrictedRoutes
   */
  protected array $restrictedRoutes = [
    'entity.webform.settings',
  ];

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {

    // Add a role requirement to all the blocked routes.
    foreach ($this->blockedRoutes as $route) {
      if ($collectionRoute = $collection->get($route)) {
        $collectionRoute->setRequirement('_role', 'admin|grants_admin');
      }
    }

    // Add an access check to all the restricted routes.
    foreach ($this->restrictedRoutes as $route) {
      if ($collectionRoute = $collection->get($route)) {
        $requirements = $collectionRoute->getRequirements();
        $requirements['_webform_access_check'] = 'TRUE';
        $collectionRoute->setRequirements($requirements);
      }
    }
  }

}
