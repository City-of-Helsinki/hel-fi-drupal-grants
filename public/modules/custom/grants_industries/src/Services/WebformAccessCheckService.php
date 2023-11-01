<?php

namespace Drupal\grants_industries\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;

/**
 * Provides a 'WebformAccessCheckService' service.
 *
 * This service provides functionality related
 * to webform access checking.
 */
class WebformAccessCheckService {

  /**
   * Admin access roles.
   *
   * An array of roles that are considered
   * admin roles (Can do anything).
   *
   * @var array
   */
  protected array $adminAccessRoles = [
    'admin',
    'grants_admin',
  ];

  /**
   * Webform admin access roles.
   *
   * An array of roles that are considered
   * webform admin roles (Can edit any form).
   *
   * @var array
   */
  protected array $webformAdminAccessRoles = [
    'grants_producer',
  ];

  /**
   * Restricted access roles.
   *
   * An array of roles that are considered
   * restricted roles and therefore need
   * industry checking (Can edit own industry forms).
   *
   * @var array
   */
  protected array $restrictedAccessRoles = [
    'grants_producer_industry',
  ];

  /**
   * The EntityTypeManagerInterface.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The AccountProxyInterface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The RouteMatchInterface.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManagerInterface.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The AccountProxyInterface.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The RouteMatchInterface.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    AccountProxyInterface $currentUser,
    RouteMatchInterface $routeMatch) {
    $this->entityTypeManager = $entityTypeManager;
    $this->currentUser = $currentUser;
    $this->routeMatch = $routeMatch;
  }

  /**
   * The checkAdminRouteAccess method.
   *
   * This method checks whether a user should be allowed
   * access to webform admin routes.
   * Called by: WebformAdminRouteAccessCheck.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Exception on InvalidPluginDefinitionException.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Exception on PluginNotFoundException.
   *
   * @return bool
   *   True if a user is allowed access, false otherwise.
   */
  public function checkAdminRouteAccess(): bool {
    $user = $this->currentUser->getAccount();

    if (!$user) {
      return FALSE;
    }

    /** @var \Drupal\user\Entity\User $userEntity */
    $userEntity = $this->entityTypeManager->getStorage('user')->load($user->id());

    if ($this->hasAdminRole($userEntity)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * The checkRestrictedRouteAccess method.
   *
   * This method checks whether a user should be allowed
   * access to webform restricted routes.
   * Called by: WebformRestrictedRouteAccess.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Exception on InvalidPluginDefinitionException.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Exception on PluginNotFoundException.
   *
   * @return bool
   *   True if a user is allowed access, false otherwise.
   */
  public function checkRestrictedRouteAccess(): bool {
    $access = FALSE;
    $user = $this->currentUser->getAccount();
    $parameters = $this->routeMatch->getParameters()->all();

    if (!$user || !is_array($parameters) || !isset($parameters['webform'])) {
      return FALSE;
    }

    /** @var \Drupal\user\Entity\User $userEntity */
    $userEntity = $this->entityTypeManager->getStorage('user')->load($user->id());

    if ($this->hasAdminRole($userEntity) || $this->hasWebformAdminRole($userEntity)) {
      $access = TRUE;
    }

    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $parameters['webform'];

    if ($this->hasRestrictedAccessRole($userEntity) && $this->hasWebformIndustryAccess($userEntity, $webform)) {
      $access = TRUE;
    }

    return $access;
  }

  /**
   * The hadAdminRole method.
   *
   * This method check if a users has an admin role, meaning
   * a role in the adminAccessRoles property. User with
   * ID 1 is also considered and admin.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user we are checking.
   *
   * @return bool
   *   True if the user has admin access, false otherwise.
   */
  public function hasAdminRole(User $user): bool {

    // User with ID 1 is always allowed.
    if ($user->id() == 1) {
      return TRUE;
    }

    foreach ($this->adminAccessRoles as $role) {
      if ($user->hasRole($role)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * The hasWebformAdminRole method.
   *
   * This method check if a users has a webform admin role,
   * meaning a role in the webformAdminAccessRoles property.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user we are checking.
   *
   * @return bool
   *   True if the user has webform admin access, false otherwise.
   */
  public function hasWebformAdminRole(User $user): bool {
    foreach ($this->webformAdminAccessRoles as $role) {
      if ($user->hasRole($role)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * The hasRestrictedRole method.
   *
   * This method check if a users has a restricted access role,
   * meaning a role in the restrictedAccessRoles property.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user we are checking.
   *
   * @return bool
   *   True if the user has a restricted access role, false otherwise.
   */
  public function hasRestrictedAccessRole(User $user): bool {
    foreach ($this->restrictedAccessRoles as $role) {
      if ($user->hasRole($role)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * The hasWebformIndustryAccess method.
   *
   * This method check if a users' industry (field_industry)
   * matches the industry selected on a webform (applicationIndustry).
   *
   * @param \Drupal\user\Entity\User $user
   *   The user we are checking.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform we are checking.
   *
   * @return bool
   *   True if the industries are found and match, false otherwise.
   */
  public function hasWebformIndustryAccess(User $user, Webform $webform): bool {
    $userIndustryField = $user->get('field_industry')->value;
    $webformIndustryField = $webform->getThirdPartySetting('grants_metadata', 'applicationIndustry');

    return isset($userIndustryField) &&
           isset($webformIndustryField) &&
           $userIndustryField === $webformIndustryField;
  }

}
