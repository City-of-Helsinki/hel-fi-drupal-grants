<?php

namespace Drupal\grants_industries\Services;

use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Provides a 'NodeAccessCheckService' service.
 *
 * This service provides functionality related
 * to service node access checking.
 */
class NodeAccessCheckService {

  /**
   * Restricted access roles.
   *
   * An array of roles that are considered
   * restricted roles and therefore need
   * industry checking (Can edit own industry nodes).
   *
   * @var array
   */
  protected array $restrictedAccessRoles = [
    'content_producer_industry',
  ];

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
   * matches the industry selected on a service node (field_industry).
   *
   * @param \Drupal\user\Entity\User $user
   *   The user we are checking.
   * @param \Drupal\node\Entity\Node $node
   *   The node we are checking.
   *
   * @return bool
   *   True if the industries are found and match, false otherwise.
   */
  public function hasNodeIndustryAccess(User $user, Node $node): bool {
    $userIndustryField = $user->get('field_industry')->value;
    $nodeIndustryField = $node->get('field_industry')->value;

    return isset($userIndustryField) &&
      isset($nodeIndustryField) &&
      $userIndustryField === $nodeIndustryField;
  }

}
