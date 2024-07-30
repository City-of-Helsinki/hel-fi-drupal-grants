<?php

namespace Drupal\grants_handler;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\webform\Entity\Webform;

/**
 * Provides the ServicePageBlockService service.
 */
class ServicePageBlockService {

  /**
   * Constructs a new WebformLoader.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   The current route match.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   The grants profile service.
   */
  public function __construct(
    protected EntityTypeManager $entityTypeManager,
    protected CurrentRouteMatch $routeMatch,
    protected GrantsProfileService $grantsProfileService,
  ) {}

  /**
   * The loadServicePageWebform function.
   *
   * This function loads the current service pages Webform.
   *
   * @return \Drupal\webform\Entity\Webform|bool
   *   Returns either the Webform, or FALSE if:
   *   - We are not on a node, or the node is not a service page.
   *   - The node has not referenced a Webform.
   *   - We fail to find a Webform with the referenced ID.
   */
  public function loadServicePageWebform(): Webform|bool {
    try {
      $node = $this->routeMatch->getParameter('node');
      if (!$node || $node->bundle() !== 'service') {
        return FALSE;
      }

      $webformId = $node->get('field_webform')->target_id;
      if (!$webformId) {
        return FALSE;
      }

      /** @var \Drupal\webform\Entity\Webform $webform */
      $webform = $this->entityTypeManager->getStorage('webform')->load($webformId);
      if (!$webform) {
        return FALSE;
      }

      return $webform;
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return FALSE;
    }
  }

  /**
   * The isCorrectApplicantType function.
   *
   * This function checks if the current user is of
   * the correct applicant type for a given Webform.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   The Webform we want to check assess for.
   *
   * @return bool
   *   TRUE if the user has the correct role, FALSE otherwise.
   */
  public function isCorrectApplicantType(Webform $webform): bool {
    $selectedRole = $this->grantsProfileService->getSelectedRoleData();
    if (!$selectedRole) {
      return FALSE;
    }

    $thirdPartySettings = $webform->getThirdPartySettings('grants_metadata');
    $applicantTypes = $this->normalizeApplicantTypes($thirdPartySettings['applicantTypes']);
    if (!in_array($selectedRole['type'], $applicantTypes)) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * The normalizeApplicantTypes function.
   *
   * Normalizes applicant types to ensure compatibility
   * with single and multiple type settings.
   *
   * @param mixed $applicantTypes
   *   The applicant types from third-party settings.
   *   This may be an array or a single value.
   *
   * @return array
   *   An array of applicant types.
   */
  private function normalizeApplicantTypes(mixed $applicantTypes): array {
    if (!is_array($applicantTypes)) {
      return [$applicantTypes];
    }
    return array_values($applicantTypes);
  }

}
