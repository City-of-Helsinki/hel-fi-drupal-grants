<?php

declare(strict_types=1);

namespace Drupal\grants_application;

use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;

/**
 * Class to handle user specific information.
 */
class UserInformationService {

  /**
   * The constructor.
   *
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   The grants profile service.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata
   *   The helsinki profiili user data.
   */
  public function __construct(
    private GrantsProfileService $grantsProfileService,
    private HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata,
  ) {
  }

  /**
   * Get the grants profile data fetched from ATV.
   *
   * @return array
   *   The grants profile.
   */
  public function getGrantsProfileData(): array {
    // @todo Grants profile should to be a value object,
    // to make it more obvious what it contains.
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    return $this->grantsProfileService->getGrantsProfileContent($selectedCompany);
  }

  /**
   * Get user data.
   */
  public function getUserData(): array {
    return $this->helfiHelsinkiProfiiliUserdata->getUserData();
  }

  /**
   * Get user profile data.
   */
  public function getUserProfileData(): array {
    return $this->helfiHelsinkiProfiiliUserdata->getUserProfileData();
  }

}
