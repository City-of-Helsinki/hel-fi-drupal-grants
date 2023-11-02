<?php

namespace Drupal\grants_profile;

use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use Drupal\helfi_yjdh\Exception\YjdhException;
use Drupal\helfi_yjdh\YjdhClient;
use Ramsey\Uuid\Uuid;

/**
 * Helper service to handle connections for Yjdh and HelsinkiProfiili.
 */
class ProfileConnector {
  /**
   * Helsinki profiili.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helsinkiProfile;

  /**
   * Municipality service.
   *
   * @var \Drupal\grants_profile\MunicipalityService
   */

  protected MunicipalityService $municipalityService;

  /**
   * Access to YTJ / Yrtti.
   *
   * @var \Drupal\helfi_yjdh\YjdhClient
   */
  protected YjdhClient $yjdhClient;

  /**
   * Constructs a ProfileConnector object.
   *
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfile
   *   Access to Helsinki profiili data.
   * @param \Drupal\grants_profile\MunicipalityService $municipalityService
   *   Municipality service.
   * @param \Drupal\helfi_yjdh\YjdhClient $yjdhClient
   *   Access to yjdh data.
   */
  public function __construct(HelsinkiProfiiliUserData $helsinkiProfile, MunicipalityService $municipalityService, YjdhClient $yjdhClient) {
    $this->helsinkiProfiili = $helsinkiProfile;
    $this->municipalityService = $municipalityService;
    $this->yjdhClient = $yjdhClient;
  }

  /**
   * Initialize grants profile.
   *
   * @param string $profileType
   *   Profile type.
   * @param array $companyData
   *   Company data.
   *
   * @return array
   *   Profile content with required fields.
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   */
  public function initGrantsProfile(string $profileType, array $companyData = NULL): array {
    // Try to load Helsinki profile data.
    try {
      $profileData = $this->helsinkiProfiili->getUserProfileData();
    }
    catch (TokenExpiredException $e) {
      throw new GrantsProfileException('Unable to fetch Helsinki profiili data');
    }

    switch ($profileType) {
      case 'private_person':
        $grantsProfileContent = $this->initGrantsProfilePrivatePerson($profileData);
        break;

      case 'registered_community':
        $grantsProfileContent = $this->initGrantsProfileRegisteredCommunity($profileData, $companyData);
        break;

      case 'unregistered_community':
        $grantsProfileContent = $this->initGrantsProfileUnRegisteredCommunity($profileData);
        break;

      default:
        throw new GrantsProfileException('Unknown profile type.');
    }
    return $grantsProfileContent;
  }

  /**
   * Get user id from HelsinkiProfiili.
   *
   * @return string
   *   User id.
   */
  public function getUserId(): string {
    $profileData = $this->helsinkiProfiili->getUserData();
    return $profileData['sub'];
  }

  /**
   * Make sure we have needed fields in our registered community profile.
   *
   * @param array $profileData
   *   Data from Helsinki Profiili.
   * @param array $companyData
   *   Company data.
   *
   * @return array
   *   Profile content with required fields.
   *
   * @throws \Drupal\grants_profile\GransProfileException
   */
  protected function initGrantsProfileRegisteredCommunity($profileData, array $companyData): array {
    $profileContent = [];
    // Try to get association details.
    $assosiationDetails = $this->yjdhClient->getAssociationBasicInfo($companyData['identifier']);
    // If they're available, use them.
    if (!empty($assosiationDetails)) {
      $profileContent["companyName"] = $assosiationDetails["AssociationNameInfo"][0]["AssociationName"];
      $profileContent["businessId"] = $assosiationDetails["BusinessId"];
      $profileContent["companyStatus"] = $assosiationDetails["AssociationStatus"];
      $profileContent["companyStatusSpecial"] = $assosiationDetails["AssociationSpecialCondition"];
      $profileContent["registrationDate"] = $assosiationDetails["RegistryDate"];

      $homeTown = $this->municipalityService->getMunicipalityName($assosiationDetails["Domicile"] ?? '');

      $profileContent["companyHome"] = $homeTown ?: $assosiationDetails["Address"][0]["City"];

    }
    else {
      try {
        $companyDetails = $this->getYjhdData($companyData['identifier']);
      }
      catch (YjdhException $e) {
        throw new GrantsProfileException('Unable to fetch company data.');
      }

      $profileContent["companyName"] = $companyDetails["TradeName"]["Name"];
      $profileContent["businessId"] = $companyDetails["BusinessId"];
      $profileContent["companyStatus"] = $companyDetails["CompanyStatus"]["Status"]["PrimaryCode"] ?? '-';
      $profileContent["companyStatusSpecial"] = $companyDetails["CompanyStatus"]["Status"]["SecondaryCode"] ?? '-';

      $profileContent["registrationDate"] =
        $companyDetails["RegistrationHistory"]["RegistryEntry"][0]["RegistrationDate"] ?? '-';

      // Try to find companyHome from Municipality info.
      $municipalityCode = $companyDetails["Municipality"]["Type"]["SecondaryCode"] ?? '';
      // Municipality service throws GrantsProfileExceptions.
      $homeTown = $this->municipalityService->getMunicipalityName($municipalityCode);

      if ($homeTown) {
        $profileContent["companyHome"] = $homeTown;
      }
      else {
        $profileContent["companyHome"] = $companyDetails["PostalAddress"]["DomesticAddress"]["City"] ?? '-';
      }

    }

    $profileContent['foundingYear'] = $profileData['foundingYear'] ?? NULL;
    $profileContent['companyNameShort'] = $profileData['companyNameShort'] ?? NULL;
    $profileContent['companyHomePage'] = $profileData['companyHomePage'] ?? NULL;
    $profileContent['businessPurpose'] = $profileData['businessPurpose'] ?? NULL;
    $profileContent['practisesBusiness'] = $profileData['practisesBusiness'] ?? NULL;

    $profileContent['addresses'] = $profileData['addresses'] ?? [];
    $profileContent['officials'] = $profileData['officials'] ?? [];
    $profileContent['bankAccounts'] = $profileData['bankAccounts'] ?? [];

    return $profileContent;

  }

  /**
   * Fetch Yjdh data.
   *
   * @param string $identifier
   *   Company identifier.
   *
   * @return array
   *   Yjhd data.
   *
   * @throws \Drupal\helfi_yjdh\Exception\YjdhException
   */
  protected function getYjdhData(string $identifier) {
    try {
      // If not, get company details and use them.
      $companyDetails = $this->yjdhClient->getCompany($identifier);
    }
    catch (\Exception $e) {
      $companyDetails = NULL;
    }

    if ($companyDetails == NULL) {
      throw new YjdhException('Company details not found');
    }

    if (!$companyDetails["TradeName"]["Name"]) {
      throw new YjdhException('Company name not set, cannot proceed');
    }
    if (!$companyDetails["BusinessId"]) {
      throw new YjdhException('Company BusinessId not set, cannot proceed');
    }
    return $companyDetails;
  }

  /**
   * Make sure we have needed fields in our UNregistered community profile.
   *
   * @param array $profileData
   *   Data from Helsinki Profiili.
   *
   * @return array
   *   Profile content with required fields.
   */
  protected function initGrantsProfileUnRegisteredCommunity($profileData): array {
    $profileContent = [];

    $profileContent["companyName"] = NULL;

    if ($profileData["myProfile"]["primaryAddress"]) {
      $profileContent['addresses'][] = $profileData["myProfile"]["primaryAddress"];
    }
    elseif ($profileData["myProfile"]["verifiedPersonalInformation"]["permanentAddress"]) {
      $profileContent['addresses'][] = [
        'street' => $profileData["myProfile"]["verifiedPersonalInformation"]["permanentAddress"]["streetAddress"],
        'postCode' => $profileData["myProfile"]["verifiedPersonalInformation"]["permanentAddress"]["postalCode"],
        'city' => $profileData["myProfile"]["verifiedPersonalInformation"]["permanentAddress"]["postOffice"],
        'country' => 'Suomi',
      ];
    }
    else {
      $profileContent['addresses'] = [];
    }

    $profileContent['officials'] = [];

    $profileContent['bankAccounts'] = [];

    // Prefill data from helsinki profile.
    if ($profileData && isset($profileData['myProfile'])) {

      if (isset($profileData['myProfile']['primaryAddress'])) {
        $profileContent['addresses'][0] = [
          'street' => $profileData['myProfile']['primaryAddress']['address'],
          'postCode' => $profileData['myProfile']['primaryAddress']['postalCode'],
          'city' => $profileData['myProfile']['primaryAddress']['city'],
          'address_id' => $profileData['myProfile']['primaryAddress']['id'],
        ];
      }

      $profileContent['bankAccounts'][0] = [
        'bankAccount' => NULL,
        'ownerName' => NULL,
        'ownerSsn' => NULL,
        'confirmationFileName' => NULL,
        'confirmationFile' => NULL,
        'bank_account_id' => Uuid::uuid4()->toString(),
      ];

      $profileContent['officials'][0] = [
        'name' => $profileData['myProfile']['firstName'] . " " . $profileData['myProfile']['lastName'],
        'additional' => '',
      ];

      if (isset($profileData['myProfile']['primaryPhone'])) {
        $profileContent['officials'][0]['phone'] = $profileData['myProfile']['primaryPhone']['phone'];
      }

      if (isset($profileData['myProfile']['primaryEmail'])) {
        $profileContent['officials'][0]['email'] = $profileData['myProfile']['primaryEmail']['email'];
      }

    }

    return $profileContent;

  }

  /**
   * Make sure we have needed fields for private person profile.
   *
   * @param array $profileData
   *   Data from Helsinki Profiili.
   *
   * @return array
   *   Profile content with required fields.
   */
  protected function initGrantsProfilePrivatePerson($profileData): array {
    $profileContent['addresses'] = [];
    $profileContent['phone_number'] = NULL;
    $profileContent['email'] = NULL;
    $profileContent['bankAccounts'] = [];
    $profileContent['unregisteredCommunities'] = NULL;

    // Prefill data from helsinki profile.
    if ($profileData && isset($profileData['myProfile'])) {

      if (isset($profileData['myProfile']['primaryAddress'])) {
        $profileContent['addresses'][0] = [
          'street' => $profileData['myProfile']['primaryAddress']['address'],
          'postCode' => $profileData['myProfile']['primaryAddress']['postalCode'],
          'city' => $profileData['myProfile']['primaryAddress']['city'],
          'address_id' => $profileData['myProfile']['primaryAddress']['id'],
        ];
      }

      if (isset($profileData['myProfile']['primaryPhone'])) {
        $profileContent['phone_number'] = $profileData['myProfile']['primaryPhone']['phone'];
      }

      if (isset($profileData['myProfile']['primaryEmail'])) {
        $profileContent['email'] = $profileData['myProfile']['primaryEmail']['email'];
      }

    }

    return $profileContent;

  }

}
