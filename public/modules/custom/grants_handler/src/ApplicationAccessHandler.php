<?php

declare(strict_types=1);

namespace Drupal\grants_handler;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\grants_mandate\CompanySelectException;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Class to handle application access.
 */
final readonly class ApplicationAccessHandler {

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private \Drupal\Core\Logger\LoggerChannelInterface $logger;

  /**
   * Constructs an ApplicationAccessHandler object.
   */
  public function __construct(
    private GrantsProfileService $grantsProfileService,
    private ApplicationGetterService $applicationGetterService,
    private LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->logger = $this->loggerFactory->get('grants_handler');
  }

  /**
   * Gets webform & submission with data and determines access.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Submission object.
   *
   * @return bool
   *   Access status
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   * @throws \Drupal\grants_mandate\CompanySelectException
   */
  public function singleSubmissionAccess(WebformSubmission $webform_submission): bool {
    // If we have account number, load details.
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    if (empty($selectedCompany)) {
      throw new CompanySelectException('User not authorised');
    }

    try {
      $grantsProfileDocument = $this->grantsProfileService->getGrantsProfile($selectedCompany);
    }
    catch (\Exception $e) {
      $this->logger->error(
        "Failed to load grants profile while checking access: @message",
        ['@message' => $e->getMessage()],
      );
      return FALSE;
    }

    if (!$grantsProfileDocument) {
      $this->logger->error("No grants profile found while checking access");
      return FALSE;
    }

    $profileContent = $grantsProfileDocument->getContent();
    $webformData = $webform_submission->getData();
    $companyType = $selectedCompany['type'] ?? NULL;
    if (!$companyType) {
      $this->logger->alert('Application access denied, missing companytype');
      return FALSE;
    }

    if (!$webformData) {
      $this->logger->alert('Application access denied, missing webformdata');
      return FALSE;
    }

    if (!isset($webformData['application_number'])) {
      $this->logger->alert('Application access denied, webformdata missing application_number');
      return FALSE;
    }

    try {
      $atvDoc = $this->applicationGetterService->getAtvDocument($webformData['application_number']);
    }
    catch (\Exception $e) {
      $this->logger->alert('Exception while checking application access: @message', ['@message' => $e->getMessage()]);
      return FALSE;
    }

    if (!$atvDoc) {
      $this->logger->alert("Application access denied, missing ATV-document for application");
      return FALSE;
    }

    $atvMetadata = $atvDoc->getMetadata();
    // Mismatch between profile and application applicant type.
    if ($companyType !== $webformData['hakijan_tiedot']['applicantType']) {
      $this->logger->alert("User is trying to access application without proper mandate:
       Company type ($companyType) does not match the webform data.");
      return FALSE;
    }
    elseif ($companyType == "registered_community" && $profileContent['businessId'] !== $atvDoc->getBusinessId()) {
      $this->logger->alert("User mandated as registered community is trying to access application,
        but selected businessId does not match with the application.");
      return FALSE;
    }
    elseif ($companyType === "private_person" && $profileContent['businessId'] !== $atvDoc->getUserId()) {
      $this->logger->alert("A private person is trying to access application,
        but selected businessId does not match the application.");
      return FALSE;
    }
    elseif ($companyType === "unregistered_community" && $profileContent['businessId'] !== $atvMetadata['applicant_id']) {
      $this->logger->alert("User mandated as unregistered_community is trying to access application,
        but selected businessId does not match the application.");
      return FALSE;
    }

    return TRUE;
  }

}
