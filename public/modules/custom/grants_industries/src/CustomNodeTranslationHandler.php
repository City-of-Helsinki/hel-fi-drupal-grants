<?php

namespace Drupal\grants_industries;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_industries\Services\NodeAccessCheckService;
use Drupal\node\NodeTranslationHandler;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a custom translation handler for nodes.
 *
 * This translation handler is used to hide the
 * "Delete translation" button from users that are
 * not allowed to delete the original node.
 */
class CustomNodeTranslationHandler extends NodeTranslationHandler {

  /**
   * The NodeAccessCheckService service.
   *
   * @var \Drupal\grants_industries\Services\NodeAccessCheckService
   */
  protected NodeAccessCheckService $nodeAccessCheckService;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    $instance = parent::createInstance($container, $entity_type);
    /** @var \Drupal\grants_industries\Services\NodeAccessCheckService $nodeAccessCheckService */
    $nodeAccessCheckService = $container->get('grants_industries.node_access_check_service');
    $instance->nodeAccessCheckService = $nodeAccessCheckService;
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function entityFormAlter(array &$form, FormStateInterface $form_state, EntityInterface $entity) {
    parent::entityFormAlter($form, $form_state, $entity);

    try {
      /** @var \Drupal\user\Entity\User $userEntity */
      $userEntity = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

      if (($this->nodeAccessCheckService->hasContentAdminAccessRole($userEntity) ||
           $this->nodeAccessCheckService->hasRestrictedAccessRole($userEntity)) &&
           !$entity->access('delete', $this->currentUser) &&
           isset($form['actions']['delete_translation'])) {
        $form['actions']['delete_translation']['#access'] = FALSE;
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $exception) {
      watchdog_exception('grants_industries', $exception, $exception->getMessage());
    }
  }
}
