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
   * Mappings to all fields.
   */
  private array $mappings;

  /**
   * A class filled with custom mapping functions.
   */
  private JsonHandler $customHandler;

  /**
   * The constructor.
   */
  public function __construct(){
    $this->mappings =  json_decode(file_get_contents(__DIR__ . '/mappings.json'), true);
    $this->customHandler = new JsonHandler();
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
    // Combine all different data sources as an array for easier mapping.
    $allDataSources = $this->combineAllDataSources(
      $formData,
      $userData,
      $companyData,
      $userProfileData,
      $grantsProfile,
      $formSettings,
      $applicationNumber,
      $applicantTypeId,
    );

    $data = [];
    $errors = [];

    // Here we handle (currently) all three mapping cases, from top to bottom:
    // Use a custom handler function to do whatever you need(for hard cases).
    // Use the hard coded data from mappings.json and add it to the target.
    // Use the source data and add it to the target.
    foreach ($this->mappings as $target => $definition) {
      $sourcePath = $definition['source'];
      $datasource = $definition['datasource'];
      $handle_value = isset($definition['handle_value']);

      // Some of the fields are too complex to handle via mapper.
      // In that case, we should handle the value by handler function.
      // And we cheat by updating the definition beforehand.
      // This might not work later but so far this is enough.
      // @todo Refactor when needed.
      if ($handle_value) {
        $sourceValue = $this->getValue($allDataSources[$datasource], $sourcePath);
        $definition['data'] = $this->customHandler
          ->handleDefinitionUpdate(
            $sourcePath,
            $sourceValue,
            $definition
          );

        $this->setTargetValue($data, $target, $sourceValue, $definition);
        continue;
      }

      // Sometimes we can just hard code the value in the mapping.
      // Map the datasource as 'null' and the hardcoded value is used as is.
      if ($sourcePath === NULL) {
        $value = (string) $definition['data']['value'];
        $this->setTargetValue($data, $target, $value, $definition);
      }
      // This is the default case: Get data from datasource and use the value.
      else if ($value = $this->getValue($allDataSources[$datasource], $sourcePath)) {
        $this->setTargetValue($data, $target, $value, $definition);
      }

      // If we reach here, either the map, the source or the target is invalid.
      $errors[] = $sourcePath;
    }

    return $data;
  }

  /**
   * Create a datasource array which is used by the mapper.
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
   *   The grants profile.
   * @param FormSettings $formSettings
   *   The form settings.
   * @param string $applicationNumber
   *   The application number.
   * @param int $applicantTypeId
   *   The application type id.
   *
   * @return array
   *   All the data sources combined into one array.
   */
  private function combineAllDataSources(
    array $formData,
    array $userData,
    array $companyData,
    array $userProfileData,
    GrantsProfile $grantsProfile,
    FormSettings $formSettings,
    string $applicationNumber,
    int $applicantTypeId,
  ): array {
    $community_official_uuid = $formData['applicant_info']['community_officials']['community_officials'][0]['official'];
    $street_name = $formData['applicant_info']['community_address']['community_address'];

    try {
      $community_official = $grantsProfile->getCommunityOfficialByUuid($community_official_uuid);
      $address = $grantsProfile->getAddressByStreetname($street_name);
      $address['country'] = $address['country'] ?? 'Suomi';
    }
    catch (\Exception $e) {
      // User has deleted the community official and exception occurs.
      throw $e;
    }

    // Any data can be added here, and it is accessible by the mapper.
    $custom = [
      'applicant_type_id' => $applicantTypeId,
      'application_number' => $applicationNumber,
      'now' => (new \DateTime())->format('Y-m-d\TH:i:s'),
      'registration_date' => $grantsProfile->getRegistrationDate(TRUE),
      'selected_address' => $address,
      'selected_community_official' => $community_official,
      'status' => 'DRAFT',
    ];

    return [
      'form_data' => $formData,
      'user' => $userData,
      'company' => $companyData,
      'user_profile' => $userProfileData,
      'grants_profile_array' => $grantsProfile->toArray(),
      'form_settings' => $formSettings->toArray(),
      'custom' => $custom,
    ];
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

    // Handle the values that can have 1 to n values added to it.
    // Check otherCompensationsArray from mappings.json.
    // TODO handle the fields that can have n values set by user.
    if ($targetPath === 'compensation.otherCompensationInfo.otherCompensationsArray.0') {
      $x = 1;
    }
    // Usually we set the value to the predefined json object.
    else if (isset($theValue['value'])) {
      $theValue['value'] = $value;
    }
    // Sometimes the value is just a "key": "value"
    else {
      $theValue = $value;
    }

    $this->setTargetValueRecursively(
      $data,
      explode('.', $targetPath),
      $theValue
    );
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
