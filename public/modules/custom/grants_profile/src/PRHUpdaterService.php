<?php

namespace Drupal\grants_profile;

use Drupal\helfi_atv\AtvDocument;
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
  public function update(AtvDocument $document) {
    $id = $document->getBusinessId();
    $content = $document->getContent();
    $upToDateData = $this->grantsProfileService->getRegisteredCompanyDataFromYdjhClient($id);

    $changes = $this->getChanges($content, $upToDateData);

    if (!empty($changes)) {
      $content = array_merge($content, $changes);
      $this->grantsProfileService->saveGrantsProfile($content);
      return TRUE;
    }

    return FALSE;
  }

  private function getChanges(array $originalData, array $freshData) {
    // Compare keys from the fresh data.
    // We want to pick only PRH related fields.
    $oldData = array_intersect_key($originalData, $freshData);

    // Check what fields are different in freshData.
    $diff = array_diff($freshData, $oldData);
    return $diff;
  }

}

