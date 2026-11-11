<?php

declare(strict_types=1);

namespace Drupal\grants_application\Hook;

use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
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
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query) {
    if (
      $view->id() !== 'application_metadata' ||
      !in_array('grants_producer_industry', $this->user->getRoles())
    ) {
      return;
    }

    $user = User::load($this->user->id());
    $industry = $user->get('field_industry')->value;
    $query->addField('application_metadata', 'application_industry');
    $query->addWhere(0, 'application_industry', $industry);
  }

}
