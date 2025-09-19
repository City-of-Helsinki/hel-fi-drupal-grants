<?php

declare(strict_types=1);

namespace Drupal\grants_application\Mapper;

use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_application\User\GrantsProfile;

/**
 * The json mapper.
 */
class JsonMapper {

  /**
   * Mappings for fields which are common between all application forms.
   */
  private array $commonMappings;

  /**
   * Mappings that are specific to one or more application forms.
   */
  private array $mappings;

  /**
   * The constructor.
   */
  public function __construct(){
    $this->commonMappings = json_decode(file_get_contents(__DIR__ . '/common_mappings.json'), true);
    $this->mappings =  json_decode(file_get_contents(__DIR__ . '/mappings.json'), true);
  }

  /**
   * Map the data from application form to AVUS2 format.
   *
   * @param array $formData
   *   The form data.
   * @param array $userData
   *   The user data.
   * @param array $companyData
   *   The company data.
   * @param array $userProfileData
   *   The user profile data.
   * @param GrantsProfile $grantsProfile
   *   The grants profile object.
   * @param FormSettings $formSettings
   *   The form settings
   * @param string $applicationNumber
   *   The application number.
   * @param string $applicant_type
   *   The applicant type.
   * @return array
   * @throws \Exception
   */
  public function map(
    array $formData,
    array $userData,
    array $companyData,
    array $userProfileData,
    GrantsProfile $grantsProfile,
    FormSettings $formSettings,
    string $applicationNumber,
    int $applicantTypeId,
  ): array {
    // hierotaan lomakkeella valitut asiat tässä etukäteen ni menee helpommin
    $community_official_uuid = $formData['applicant_info']['community_officials']['community_officials'][0]['official'];
    $street_name = $formData['applicant_info']['community_address']['community_address'];

    try {
      $community_official = $grantsProfile->getCommunityOfficialByUuid($community_official_uuid);
      $address = $grantsProfile->getAddressByStreetname($street_name);
      $address['country'] = $address['country'] ?? 'Suomi';

    }
    catch (\Exception $e) {
      // käyttäjä on poistanu officialin minkä on valinnu lomakkeella
      throw $e;
    }

    // Special fields which value may be change based on what ever.
    $custom = [
      'applicant_type_id' => $applicantTypeId,
      'application_number' => $applicationNumber,
      'now' => (new \DateTime())->format('Y-m-d\TH:i:s'),
      'registration_date' => $grantsProfile->getRegistrationDate(TRUE),
      'selected_address' => $address,
      'selected_community_official' => $community_official,
      'status' => 'DRAFT',
    ];

    // Common fields use mostly external.
    $all_sources = [
      'form_data' => $formData,
      'user' => $userData,
      'company' => $companyData,
      'user_profile' => $userProfileData,
      'grants_profile_array' => $grantsProfile->toArray(),
      'form_settings' => $formSettings->toArray(),
      'custom' => $custom,
    ];


    $data = [];

    // Map common values.
    // täällä mäpätään paljon asioita mitkä ei ole react-lomakeella.
    foreach ($this->commonMappings as $target => $definition) {
      $sourcePath = $definition['source'];
      $sourceType = $definition['source_type'];

      // Null type allows adding hard coded values
      if ($sourcePath === NULL) {
        $value = (string) $definition['data']['value'];
        $this->setTargetValue($data, $target, $value, $definition);
      }
      else if ($value = $this->getValue($all_sources[$sourceType], $sourcePath)) {
        $this->setTargetValue($data, $target, $value, $definition);
      }

    }

    // täällä mäpätään ei-yhteisiä react-lomake -asioita
    foreach ($this->mappings as $target => $definition) {
      $sourcePath = $definition['source'];

      // tää sourcehan voi olla react-lomake tai grants profile tai company profile tai tai tai...
      if ($value = $this->getValue(['form_data' => $formData], $sourcePath)) {
        $this->setTargetValue($data, $target, $value, $definition);
      }
    }

    // mäpättäiskö tiedostot täällä kansa ?

    return $data;
  }

  /**
   * Get a value for field from application form.
   *
   * @param array $sourceData
   * @param string $sourcePath
   * @return string
   */
  private function getValue(array $sourceData, string $sourcePath): array|string|null {
    $path = explode('.', $sourcePath);
    return $this->getNestedArrayValue($sourceData, $path);
  }

  /**
   * Traverse an array recursively and return target value.
   *
   * @param array $array
   * @param $indexes
   * @return mixed
   */
  private function getNestedArrayValue(array $array, $indexes): array|string|null {
    // When we reach the end of source path, get the value.
    if (count($indexes) === 1) {
      $value = $array[$indexes[0]];
      if (!is_null($value) && !is_array($value)) {
        // All values must be string.
        return (string)$value;
      }
      return [];
    }

    // If we are still traversing the array, keep going.
    if (isset($indexes[0]) && isset($array[$indexes[0]])) {
      return $this->getNestedArrayValue($array[$indexes[0]], array_slice($indexes, 1));
    }
    return NULL;
  }

  /**
   * Set the value to target array.
   *
   * @param array $data
   *   The data parsed from source.
   * @param string $targetPath
   *   Data location on AVUS2 document tree.
   * @param string $value
   *   The value.
   * @param array $definition
   *   Predefined data for the Avus2 document.
   *
   * @return void
   */
  private function setTargetValue(array &$data, string $targetPath, string $value, array $definition): void {
    $theValue = $definition['data'];

    if ($targetPath === 'compensation.otherCompensationInfo.otherCompensationsArray.0') {
      $x = 1;
    }

    // if (is_array($definition['data']) && empty($definition['data'])) {
      // $theValue['value'] = [];
    // }
    // Most are objects, some are not.
    else if (isset($theValue['value'])) {
      $theValue['value'] = $value;
    }
    else {
      $theValue = $value;
    }

    $path = explode('.', $targetPath);
    $this->setTargetValueRecursively($data, $path, $theValue);
  }

  /**
   * Traverse the target array recursively and set value.
   *
   * @param $data
   * @param $indexes
   * @param $the_value
   * @return void
   */
  private function setTargetValueRecursively(&$data, $indexes, $theValue): void {
    if (count($indexes) === 1) {
      // @todo Refactor exception.
      if ($indexes[0] === 'additionalInformation') {
        $data[$indexes[0]] = $theValue;
        return;
      }
      if (is_array($theValue) && empty($theValue)) {
        // allow setting empty value to target data.
        return;
      }

      $data[] = $theValue;
      return;
    }

    $this->setTargetValueRecursively($data[$indexes[0]], array_slice($indexes, 1), $theValue);
  }

}
