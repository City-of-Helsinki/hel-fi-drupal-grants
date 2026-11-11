<?php

declare(strict_types=1);

namespace Drupal\grants_application\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\Sql\Query;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\grants_application\Entity\ApplicationMetadata;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;

/**
 * Views hook implementations for grants_application.
 */
final class ApplicationMetadataHooks {

  use AutowireTrait;

  public function __construct(
    protected AccountProxyInterface $user,
  ) {
  }

  /**
   * Implements hook_views_query_alter().
   *
   * Hide unnecessary applications from application_metadata view.
   *
   * @phpstan-param \Drupal\views\Plugin\views\query\Sql $sql
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query): void {
    if (
      $view->id() !== 'application_metadata' ||
      !in_array('grants_producer_industry', $this->user->getRoles())
    ) {
      return;
    }

    $user = User::load($this->user->id());
    $industry = $user->get('field_industry')->value;

    // No industry selection means everything may be shown.
    if (!$industry) {
      return;
    }

    $query->addField('application_metadata', 'application_industry');
    $query->addWhere(0, 'application_industry', $industry);
  }

  /**
   * Implements hook_application_metadata_access().
   *
   * Prevent access to application_metadata entity by user's selected industry.
   */
  #[Hook('application_metadata_access')]
  public function applicationMetadataAccess(EntityInterface $entity, string $operation, AccountInterface $account): AccessResultInterface {
    if (
      !$entity instanceof ApplicationMetadata ||
      !in_array('grants_producer_industry', $account->getRoles())
    ) {
      return AccessResult::neutral();
    }

    $user = User::load($account->id());
    $industry = $user->get('field_industry')->value;

    // No industry selection means everything may be shown.
    if (!$industry) {
      return AccessResult::neutral();
    }

    $entityIndustry = $entity->get('application_industry')->value;
    if ($entityIndustry !== $industry) {
      return AccessResult::forbidden();
    }

    return AccessResult::allowed();
  }

}
