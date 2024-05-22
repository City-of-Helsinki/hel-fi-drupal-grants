<?php

namespace Drupal\grants_industries;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Utility\Error;
use Drupal\grants_industries\Services\NodeAccessCheckService;
use Drupal\taxonomy\TermTranslationHandler;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a custom translation handler for terms.
 *
 * This translation handler is used to prevent users
 * from deleting a term translation if they are
 * not allowed to delete the original term.
 */
class CustomTermTranslationHandler extends TermTranslationHandler {

  /**
   * The NodeAccessCheckService service.
   *
   * @var \Drupal\grants_industries\Services\NodeAccessCheckService
   */
  protected NodeAccessCheckService $nodeAccessCheckService;

  /**
   * The logger channel.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $loggerChannel;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    /** @var \Drupal\grants_industries\Services\NodeAccessCheckService $nodeAccessCheckService */
    $nodeAccessCheckService = $container->get('grants_industries.node_access_check_service');
    $instance->nodeAccessCheckService = $nodeAccessCheckService;
    $instance->loggerChannel = $container->get('logger.factory')->get('grants_industries');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationAccess(EntityInterface $entity, $op) {
    $access = parent::getTranslationAccess($entity, $op);

    if ($op !== 'delete') {
      return $access;
    }

    try {
      /** @var \Drupal\user\Entity\User $userEntity */
      $userEntity = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

      if (($this->nodeAccessCheckService->hasContentAdminAccessRole($userEntity) ||
           $this->nodeAccessCheckService->hasRestrictedAccessRole($userEntity)) &&
           !$entity->access('delete', $this->currentUser)) {
        $access = AccessResult::forbidden();
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $exception) {
      Error::logException($this->loggerChannel, $exception);
    }

    return $access;
  }

}
