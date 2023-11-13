<?php

namespace Drupal\grants_profile;

use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_yjdh\YjdhClient;

/**
 * ProfilePRH data updater class.
 */
class PRHUpdaterService {

  /**
   * Constructs a PRH Updater Service.
   *
   * @param \Drupal\helfi_yjdh\YjdhClient $yjdhClient
   *   Access to yjdh data.
   * @param \Drupal\grants_profile\ProfileConnector $profileConnector
   *   Grants profile connector.
   * @param \Drupal\grants_profile\GrantsProfileService $profileService
   *   Grants profile service.
   */
  public function __construct(
    private YjdhClient $yjdhClient,
    private ProfileConnector $profileConnector,
    private GrantsProfileService $profileService
  ) {}

  /**
   * Performs a PRH data update for the profile.
   */
  public function update(AtvDocument $document) {
    $id = $document->getBusinessId();
    $content = $document->getContent();
    $upToDateData = $this->profileConnector->getRegisteredCompanyDataFromYdjhClient($id);

    $changes = $this->getChanges($content, $upToDateData);

    if (!empty($changes)) {
      $content = array_merge($content, $changes);
      $this->profileService->saveGrantsProfile($content);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get changed fields between new and old data.
   *
   * @param array $originalData
   *   Data before any updates.
   * @param array $freshData
   *   Data from PRH.
   *
   * @return array
   *   Diff array with changed data only.
   */
  private function getChanges(array $originalData, array $freshData) {
    // Compare keys from the fresh data.
    // We want to pick only PRH related fields.
    $oldData = array_intersect_key($originalData, $freshData);

    // Check what fields are different in freshData.
    $diff = array_diff($freshData, $oldData);
    return $diff;
  }

}
