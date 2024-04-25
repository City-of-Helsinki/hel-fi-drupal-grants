<?php

namespace Drupal\grants_industries\Access;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Utility\Error;
use Drupal\grants_industries\Services\WebformAccessCheckService;

/**
 * Provides a 'WebformAdminRouteAccessCheck' access.
 *
 * This access check is utilized by the $adminOnlyWebformRoutes
 * routes in the WebformRouteSubscriber class.
 * It uses the WebformAccessCheckService service for the
 * access checking logic.
 */
class WebformAdminRouteAccessCheck implements AccessInterface {

  /**
   * The WebformAccessCheckService service.
   *
   * @var \Drupal\grants_industries\Services\WebformAccessCheckService
   */
  protected WebformAccessCheckService $webformAccessCheckService;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The class constructor.
   *
   * @param \Drupal\grants_industries\Services\WebformAccessCheckService $webformAccessCheckService
   *   The WebformAccessCheckService service.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   The logger factory.
   */
  public function __construct(
    WebformAccessCheckService $webformAccessCheckService,
    LoggerChannelFactoryInterface $loggerFactory) {
    $this->webformAccessCheckService = $webformAccessCheckService;
    $this->logger = $loggerFactory->get('grants_industries');
  }

  /**
   * The access method.
   *
   * This method either allows or denies access
   * to a webform route depending on the results
   * from checkAdminRouteAccess().
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Returns an access result.
   */
  public function access(): AccessResultInterface {
    try {
      $checkAdminRouteResult = $this->webformAccessCheckService->checkAdminRouteAccess();
      return ($checkAdminRouteResult) ? AccessResult::allowed() : AccessResult::forbidden();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $exception) {
      Error::logException($this->logger, $exception);
      return AccessResult::forbidden();
    }
  }

}
