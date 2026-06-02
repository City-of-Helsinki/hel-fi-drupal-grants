<?php

namespace Drupal\grants_application\Entity;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\ContentEntityInterface;
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
class ApplicationSubmissionAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The constructor.
   */
  public function __construct(
    EntityTypeInterface $entityTypeInterface,
    private readonly UserInformationService $userInformationService,
  ) {
    parent::__construct($entityTypeInterface);
  }

  /**
   * {@inheritdoc}
   */
  final public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get(UserInformationService::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, ?AccountInterface $account = NULL, $return_as_object = FALSE): AccessResultInterface {
    // Original implementation: ApplicationAccessHandler.php in grants handler.
    assert($entity instanceof ContentEntityInterface);

    // The admins can break content lock.
    if ($account?->hasPermission('break content lock')) {
      return AccessResult::allowed()->cachePerPermissions();
    }

    $applicantType = $this->userInformationService->getApplicantType();
    if (!$applicantType) {
      // This used to be possible case.
      return AccessResult::forbidden('No applicant type selected.');
    }
    try {
      if ($applicantType === 'private_person') {
        // Private person may see only own applications.
        return $this->privateApplicationAllowed($entity) ? AccessResult::allowed() : AccessResult::forbidden();
      }
      else {
        // Community user may access the community's applications.
        return $this->communityApplicationAllowed($entity) ? AccessResult::allowed() : AccessResult::forbidden();
      }
    }
    catch (\Exception) {
      return AccessResult::forbidden('Unable to read user data.');
    }

  }

  /**
   * Check access for user mandated as private_person.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   Allowed.
   */
  private function privateApplicationAllowed(EntityInterface $entity): bool {
    assert($entity instanceof ContentEntityInterface);

    $userInformation = $this->userInformationService->getUserData();

    // User mandated as private person may not see community applications.
    return $userInformation->sub === $entity->get('sub')->value &&
      $entity->get('business_id')->value === '';
  }

  /**
   * Check access for user mandated as community user (reg/unreg).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return bool
   *   Allowed.
   */
  private function communityApplicationAllowed(EntityInterface $entity): bool {
    assert($entity instanceof ContentEntityInterface);

    $grantsProfileData = $this->userInformationService->getGrantsProfileContent();
    $business_id = $grantsProfileData->getBusinessId();

    return $business_id === $entity->get('business_id')->value;
  }

}
