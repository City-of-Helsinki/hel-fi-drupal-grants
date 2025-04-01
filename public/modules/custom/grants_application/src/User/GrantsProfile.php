<?php

declare(strict_types=1);

namespace Drupal\grants_application\User;

/**
 * The grants profile class.
 */
class GrantsProfile {

  /**
   * The constructor.
   *
   * @param array $grantsProfileData
   *   The raw data returned from outside system.
   */
  public function __construct(private array $grantsProfileData){
  }

  /**
   * Get the raw profile data array.
   *
   * CompanyShortName:string,
   * companyName:string,
   * companyHome:string,
   * companyHomePage:string,
   * companyStatus:string,
   * companyStatusSpecial:string,
   * businessPurpose:string,
   * foundingYear:string,
   * registrationDate:string
   * officials:array,
   * addresses:array,
   * bankAccounts: array,
   * businessId:string.
   *
   * @return array
   *   The grants profile array.
   */
  public function toArray(): array {
    return $this->grantsProfileData;
  }

  /**
   * Find official from grants profile data by UUID.
   *
   * Array with keys:
   * official_id: string,
   * name: string,
   * role: number,
   * email: string,
   * phone: string.
   *
   * @param string $uuid
   *   The uuid.
   *
   * @return array
   *   The official-array.
   */
  public function getCommunityOfficialByUuid(string $uuid): array {
    foreach ($this->grantsProfileData['officials'] as $official) {
      if ($official['official_id'] === $uuid) {
        return $official;
      }
    }

    // This should not be possible since the end user selects the data
    // from this list.
    throw new \Exception('Selected official not found.');
  }

  /**
   * Find Address from grants profile data by UUID.
   *
   * Array with keys:
   * address_id: string,
   * street: string,
   * postCode: number,
   * city: string,
   * country: string.
   *
   * @param string $uuid
   *   The uuid.
   *
   * @return array
   *   The official-array.
   */
  public function getAddressByStreetname(string $street_name): array {
    // @todo Maybe do this with the uuid.
    foreach ($this->grantsProfileData['addresses'] as $address) {
      if ($address['street'] === $street_name) {
        return $address;
      }
    }

    // This should not be possible since the end user selects the data
    // from this list.
    throw new \Exception('Selected address not found.');
  }

  /**
   * Get the registration date.
   *
   * @param bool $formatted
   *   Format to d.m.Y format.
   *
   * @return string
   *   The registration date.
   */
  public function getRegistrationDate(bool $formatted = FALSE): string {
    return $formatted ?
      (new \DateTime($this->grantsProfileData['registrationDate']))
        ->format('d.m.Y') :
      $this->grantsProfileData['registrationDate'];
  }

  /**
   * Get the founding year.
   *
   * @return string
   *   The founding year.
   */
  public function getFoundingYear(): string {
    return $this->grantsProfileData['foundingYear'];
  }

  /**
   * Get founding year.
   *
   * @return string
   *   Founding year.
   */
  public function getFoundationYear(): string {
    return $this->grantsProfileData['foundingYear'];
  }

  /**
   * Get the home city.
   *
   * @return string
   *   The company home.
   */
  public function getHome(): string {
    return $this->grantsProfileData['companyHome'];
  }

  /**
   * Get company home page.
   *
   * @return string
   *   The home page.
   */
  public function getHomePage(): string {
    return $this->grantsProfileData['companyHomePage'];
  }

  /**
   * Get the company name.
   *
   * @return string
   *   The company name.
   */
  public function getCompanyName(): string {
    return $this->grantsProfileData['companyName'];
  }

  /**
   * Get the company short name.
   *
   * @return string
   *   The company short name.
   */
  public function getCompanyShortName(): string {
    return $this->grantsProfileData['companyNameShort'];
  }

  /**
   * Get the business purpose
   *
   * @return string
   *   The business purpose
   */
  public function getBusinessPurpose(): string {
    return $this->grantsProfileData['businessPurpose'];
  }

}
