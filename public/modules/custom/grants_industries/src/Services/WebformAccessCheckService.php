<?php

namespace Drupal\grants_industries\Services;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;

/**
 * Provides a 'WebformAccessCheckService' service.
 *
 * This service is used to check if a user should
 * be granted access to certain webform routes.
 */
class WebformAccessCheckService {

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
   * An array of roles that are always allowed
   * access to restricted routes.
   *
   * @var array $allowedRoles
   */
  protected array $allowedRoles =  [
    'admin',
    'grants_admin',
    'grants_producer',
  ];

  /**
   * An array of roles that require access
   * checking for restricted routes.
   *
   * @var array $restrictedRoles
   */
  protected array $restrictedRoles =  [
    'grants_producer_industry'
  ];

  /**
   * Class constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The EntityTypeManagerInterface.
   * @param AccountProxyInterface $currentUser
   *   The AccountProxyInterface.
   * @param RouteMatchInterface $routeMatch
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
   * The checkRestrictedRouteAccess method.
   *
   * This is the primary method of this service.
   * It checks whether a user should be allowed
   * access to certain webform routes based on
   * the users roles and the selected industry.
   *
   * @throws InvalidPluginDefinitionException
   *   Exception on InvalidPluginDefinitionException.
   * @throws PluginNotFoundException
   *   Exception on PluginNotFoundException.
   *
   * @return bool
   *  True if a user is allowed access, false otherwise.
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

    foreach ($this->allowedRoles as $role) {
      if ($userEntity->hasRole($role)) {
        $access = TRUE;
      }
    }

    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $parameters['webform'];

    foreach ($this->restrictedRoles as $role) {
      if ($userEntity->hasRole($role) && $this->hasIndustryAccess($userEntity, $webform)) {
        $access = TRUE;
      }
    }

    return $access;
  }

  /**
   * The hasIndustryAccess method.
   *
   * This method check if a users' industry (field_industry)
   * matches the industry selected on a webform (applicationIndustry).
   *
   * @param \Drupal\user\Entity\User  $user
   *   The user we are checking.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform we are checking.
   *
   * @return bool
   *   True if the industries are found and match, false otherwise.
   */
  public function hasIndustryAccess(User $user, Webform $webform): bool {
    $userIndustryField = $user->get('field_industry')->value;
    $webformIndustryField = $webform->getThirdPartySetting('grants_metadata', 'applicationIndustry');

    return isset($userIndustryField) &&
           isset($webformIndustryField) &&
           $userIndustryField === $webformIndustryField;
  }
}
