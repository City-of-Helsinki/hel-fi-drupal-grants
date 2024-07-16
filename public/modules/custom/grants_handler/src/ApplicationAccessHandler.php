<?php

declare(strict_types=1);

namespace Drupal\grants_handler;

use Drupal\grants_mandate\CompanySelectException;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Class to handle application access.
 */
final readonly class ApplicationAccessHandler {

  /**
   * Constructs an ApplicationAccessHandler object.
   */
  public function __construct(
    private GrantsProfileService $grantsProfileService,
  ) {}

  /**
   * Gets webform & submission with data and determines access.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Submission object.
   *
   * @return bool
   *   Access status
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function singleSubmissionAccess(WebformSubmission $webform_submission): bool {

    // If we have account number, load details.
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    if (empty($selectedCompany)) {
      throw new CompanySelectException('User not authorised');
    }
    $grantsProfileDocument = $this->grantsProfileService->getGrantsProfile($selectedCompany);
    $profileContent = $grantsProfileDocument->getContent();
    $webformData = $webform_submission->getData();
    $companyType = $selectedCompany['type'] ?? NULL;
    if (!$companyType || !$webformData) {
      return FALSE;
    }

    if (!isset($webformData['application_number'])) {
      return FALSE;
    }

    try {
      $atvDoc = ApplicationHelpers::atvDocumentFromApplicationNumber($webformData['application_number']);
    }
    catch (AtvDocumentNotFoundException $e) {
      return FALSE;
    }
    $atvMetadata = $atvDoc->getMetadata();
    // Mismatch between profile and application applicant type.
    if ($companyType !== $webformData['hakijan_tiedot']['applicantType']) {
      return FALSE;
    }
    elseif ($companyType == "registered_community" && $profileContent['businessId'] !== $atvDoc->getBusinessId()) {
      return FALSE;
    }
    elseif ($companyType === "private_person" && $profileContent['businessId'] !== $atvDoc->getUserId()) {
      return FALSE;
    }
    elseif ($companyType === "unregistered_community" && $profileContent['businessId'] !== $atvMetadata['applicant_id']) {
      return FALSE;
    }

    return TRUE;
  }

}
