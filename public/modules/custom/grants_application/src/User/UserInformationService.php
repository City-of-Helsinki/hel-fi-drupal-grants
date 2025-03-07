<?php

declare(strict_types=1);

namespace Drupal\grants_application\User;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Class to handle user specific information.
 */
class UserInformationService {

  use AutowireTrait;

  /**
   * The constructor.
   *
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   The grants profile service.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata
   *   The helsinki profiili user data.
   */
  public function __construct(
    #[Autowire(service: 'grants_profile.service')]
    private GrantsProfileService $grantsProfileService,
    #[Autowire(service: 'helfi_helsinki_profiili.userdata')]
    private HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata,
  ) {
  }

  /**
   * Get the grants profile data fetched from ATV.
   *
   * @todo Figure out what this data actually is
   * and document it here.
   *
   * @return array
   *   The grants profile.
   */
  public function getGrantsProfileContent(): array {
    // @todo Grants profile should to be a value object,
    // to make it more obvious what it contains.
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    return $this->grantsProfileService->getGrantsProfileContent($selectedCompany);
  }

  /**
   * Get the selected company.
   *
   * @todo Figure out what this actually is
   * and document it here.
   *
   * @return array
   *   Array containing selected company data.
   */
  public function getSelectedCompany(): array {
    return $this->grantsProfileService->getSelectedRoleData();
  }

  /**
   * Get the applicant type.
   *
   * @todo Figure out what applicant type actually is
   * and document it here.
   *
   * @return string
   *   The applicant type
   */
  public function getApplicantType(): ?string {
    return $this->grantsProfileService->getApplicantType();
  }

  /**
   * Get the user data.
   *
   * @todo Figure out what this actually is
   * and document it here.
   *
   * @return array
   *   The user data.
   */
  public function getUserData(): array {
    return $this->helfiHelsinkiProfiiliUserdata->getUserData();
  }

  /**
   * Get the user profile data.
   *
   * @todo Figure out what this actually is
   * and document it here.
   *
   * @return array
   *   The user profile data.
   */
  public function getUserProfileData(): array {
    return $this->helfiHelsinkiProfiiliUserdata->getUserProfileData();
  }

  /**
   * Get the applicant information.
   *
   * @todo Figure out what this actually is
   * and refactor.
   *
   * @return array
   *   The applicant information.
   */
  public function getApplicantInformation(array $selectedCompany, array $companyData, array $userData, array $userProfileData): array {
    return match ($selectedCompany["type"]) {
      'registered_community' => [
        'applicantType' => $selectedCompany["type"],
        'applicant_type' => $selectedCompany["type"],
        'communityOfficialName' => $selectedCompany["name"],
        'companyNumber' => $selectedCompany["identifier"],
        'registrationDate' => $companyData["registrationDate"],
        'home' => $companyData["companyHome"],
        'communityOfficialNameShort' => $companyData["companyNameShort"],
        'foundingYear' => $companyData["foundingYear"],
        'homePage' => $companyData["companyHomePage"],
      ],
      'unregistered_community' => [
        'applicantType' => $selectedCompany["type"],
        'applicant_type' => $selectedCompany["type"],
        'communityOfficialName' => $companyData["companyName"],
        'firstname' => $userData["given_name"],
        'lastname' => $userData["family_name"],
        'socialSecurityNumber' => $userProfileData["myProfile"]["verifiedPersonalInformation"]["nationalIdentificationNumber"],
        'email' => $userData["email"],
        'street' => $companyData["addresses"][0]["street"],
        'city' => $companyData["addresses"][0]["city"],
        'postCode' => $companyData["addresses"][0]["postCode"],
        'country' => $companyData["addresses"][0]["country"],
      ],
      'private_person' => [
        'applicantType' => $selectedCompany["type"],
        'applicant_type' => $selectedCompany["type"],
        'firstname' => $userData["given_name"],
        'lastname' => $userData["family_name"],
        'socialSecurityNumber' => $userProfileData["myProfile"]["verifiedPersonalInformation"]["nationalIdentificationNumber"] ?? '',
        'email' => $userData["email"],
        'street' => $companyData["addresses"][0]["street"] ?? '',
        'city' => $companyData["addresses"][0]["city"] ?? '',
        'postCode' => $companyData["addresses"][0]["postCode"] ?? '',
        'country' => $companyData["addresses"][0]["country"] ?? '',
      ],
      default => [],
    };
  }

}
