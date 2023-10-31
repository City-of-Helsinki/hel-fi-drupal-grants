<?php

namespace Drupal\grants_profile;

use Drupal\helfi_yjdh\YjdhClient;

class PRHUpdaterService {

  /**
   * Constructs a PRH Updater Service.
   *
   * @param \Drupal\helfi_yjdh\YjdhClient $yjdhClient
   *   Access to yjdh data.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Grants profile service.
   */
  public function __construct(
    private YjdhClient $yjdhClient,
    private GrantsProfileService $grantsProfileService
  ) {}

  /**
   * Performs a PRH data update for the profile.
   */
  public function update(string $id) {
    $upToDateData = $this->grantsProfileService->getRegisteredCompanyDataFromYdjhClient($id);
    return TRUE;
  }

}

