<?php

namespace Drupal\grants_industries\Access;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\grants_industries\Services\WebformAccessCheckService;

/**
 * Provides a 'WebformAdminRouteAccessCheck' access.
 *
 * This access check is utilized by the $restrictedWebformRoutes
 * routes in the WebformRouteSubscriber class.
 * It uses the WebformAccessCheckService service for the
 * access checking logic.
 */
class WebformRestrictedRouteAccessCheck implements AccessInterface {

  /**
   * The WebformAccessCheckService service.
   *
   * @var \Drupal\grants_industries\Services\WebformAccessCheckService
   */
  protected WebformAccessCheckService $webformAccessCheckService;

  /**
   * The class constructor.
   *
   * @param \Drupal\grants_industries\Services\WebformAccessCheckService $webformAccessCheckService
   *   The WebformAccessCheckService service.
   */
  public function __construct(WebformAccessCheckService $webformAccessCheckService) {
    $this->webformAccessCheckService = $webformAccessCheckService;
  }

  /**
   * The access method.
   *
   * This method either allows or denies access
   * to a webform route depending on the results
   * from checkRestrictedRouteAccess().
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Returns an access result.
   */
  public function access(): AccessResultInterface {
    try {
      return ($this->webformAccessCheckService->checkRestrictedRouteAccess()) ? AccessResult::allowed() : AccessResult::forbidden();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $exception) {
      watchdog_exception('grants_industries', $exception, $exception->getMessage());
      return AccessResult::forbidden();
    }
  }

}
