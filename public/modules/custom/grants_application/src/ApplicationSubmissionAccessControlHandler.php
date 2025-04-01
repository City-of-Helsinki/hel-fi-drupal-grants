<?php

namespace Drupal\grants_application;

use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grants_application\User\UserInformationService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access control handler for form submission.
 */
final class ApplicationSubmissionAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  public function __construct(
    EntityTypeInterface $entityType,
    private UserInformationService $userInformationService,
  ) {
    parent::__construct($entityType);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): ApplicationSubmissionAccessControlHandler|EntityHandlerInterface {
    return new ApplicationSubmissionAccessControlHandler(
      $entity_type,
      $container->get(UserInformationService::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, ?AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface {
    $userInformation = $this->userInformationService->getUserData();

    if ($userInformation['sub'] && $userInformation['sub'] === $entity->get('sub')->value) {
      return new AccessResultAllowed();
    }

    return new AccessResultForbidden();
  }

}
