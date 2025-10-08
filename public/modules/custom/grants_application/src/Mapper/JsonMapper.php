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
   * A class filled with custom mapping functions.
   */
  private JsonHandler $customHandler;

  /**
   * The constructor.
   */
  public function __construct(private readonly array $mappings) {
    $this->customHandler = new JsonHandler();
  }

  /**
   * Map the data from application form to AVUS2-format.
   *
   * @param array $allDataSources
   *   All the data sources that are needed by the mapper.
   *
   * @return array
   *   The data mapped in Avus2-format.
   */
  public function map(array $allDataSources): array {
    $data = [];

    foreach ($this->mappings as $target => $definition) {
      $sourcePath = $definition['source'];
      $dataSourceType = $definition['datasource'];
      $mappingType = $definition['mapping_type'];
      if (!$this->sourcePathExists($allDataSources, $dataSourceType, $sourcePath)) {
        continue;
      }

      match($definition['mapping_type']) {
        'default' => $this->handleDefault($data, $definition, $target, $allDataSources),
        'multiple_values' => $this->handleMultipleValues($data, $definition, $target, $allDataSources),
        'custom' => $this->handleCustom($data, $definition, $target, $allDataSources),
        'simple' => $this->handleSimple($data, $definition, $target, $allDataSources),
        // 'hardcoded' => $this->handleHardcoded($data, $definition, $target),
        default => $this->handleDefault($data, $definition, $target, $allDataSources),
      };
    }

    return $data;
  }

  /**
   * Does the source path exist on datasource.
   *
   * @param array $dataSources
   * @param array $definition
   * @return bool
   */
  private function sourcePathExists(array $dataSources, string $sourceType, string $sourcePath): bool {
    $value = $this->getValue($dataSources[$sourceType], $sourcePath);
    if ($value === NULL) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * Handle the most basic case, copy the value from source to target.
   *
   * @param $data
   * @param array $definition
   * @param $target
   * @param array $dataSources
   */
  private function handleDefault(&$data, array $definition, $target, array $dataSources) {
    $sourcePath = $definition['source'];

    $value = $this->getValue($dataSources[$definition['datasource']], $sourcePath);
    $this->setTargetValue($data, $target, $value, $definition);
  }

  /**
   * Handle a case where user may add n-items with n-fields.
   *
   * F.ex ID58, user may add multiple orienteeringMaps on one application.
   *
   * @param $data
   * @param array $definition
   * @param $targetPath
   * @param array $dataSources
   * @return void
   */
  private function handleMultipleValues(&$data, array $definition, $targetPath, array $dataSources) {
    $sourcePath = $definition['source'];
    $targetPath = rtrim($targetPath, '.n');

    $sourceValues = $this->getMultipleValues($dataSources[$definition['datasource']], $sourcePath);

    // Source value contains multiple objects with contains multiple fields.
    foreach ($sourceValues as $singleObject) {
      $values = [];
      foreach ($singleObject as $fieldName => $value) {
        $valueArray = $definition['data'][$fieldName];
        $valueArray['value'] = $value;
        $values[] = $valueArray;
      }
      $this->setTargetValue($data, $targetPath, $values, $definition);
    }

  }

  /**
   * Just use the mapped data.
   *
   * @param $data
   * @param array $definition
   * @param $targetPath
   * @return void
   */
  private function handleHardcoded(&$data, array $definition, $targetPath) {
    $value = $definition['data'];
    $this->setTargetValue($data, $targetPath, $value, $definition);
  }

  /**
   * Handle the case where we just set a key and value to the target.
   *
   * @param $data
   * @param array $definition
   * @param $targetPath
   * @param array $dataSources
   */
  private function handleSimple(&$data, array $definition, $targetPath, array $dataSources) {
    $sourcePath = $definition['source'];
    $sourceValue = $this->getValue($dataSources[$definition['datasource']], $sourcePath);

    $targetValue = $sourceValue;
    $this->setTargetValue($data, $targetPath, $targetValue, $definition);
  }

  /**
   * Handle the more complex cases.
   *
   * You can add new handlers to the handler class.
   *
   * @param $data
   *   The data array.
   * @param array $definition
   *   The mapping definition.
   * @param $targetPath
   *   The target path
   * @param array $dataSources
   *   The data sources.
   */
  private function handleCustom(&$data, array $definition, $targetPath, array $dataSources): void {
    $sourcePath = $definition['source'];

    $sourceValue = $this->getValue($dataSources[$definition['datasource']], $sourcePath);
    $definition['data'] = $this->customHandler
      ->handleDefinitionUpdate(
        $definition['custom_handler'],
        $sourceValue,
        $definition
      );
    $sourceValue = $definition['data'];

    $this->setTargetValue($data, $targetPath, $sourceValue, $definition);
  }

  public function mapMultipleValues(array $allDataSources) {
    $data = [];
    foreach ($this->mappings as $target => $definition) {
      $sourcePath = $definition['source'];
      $datasource = $definition['datasource'];

      if (isset($definition['multiple_values'])) {
        $sourceValues = $this->getMultipleValues($allDataSources[$datasource], $sourcePath);

        $t = rtrim($target, '.n');
        foreach ($sourceValues as $singleObject) {
          $dd = [];
          foreach ($singleObject as $fieldName => $v) {
            $d = $definition['data'][$fieldName];
            $d['value'] = $v;
            $dd[] = $d;
          }
          $this->setTargetValue($data, $t, $dd, $definition);
        }
      }

    }
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

  private function getMultipleValues(array $sourceData, string $sourcePath): array|string|null {
    $path = explode('.', $sourcePath);
    return $this->getMultipleNestedArrayValues($sourceData, $path);
  }

  /**
   * Traverse an array recursively and return target value.
   *
   * @param array $array
   *   The data array.
   * @param $indexes
   *   The array indexes.
   *
   * @return mixed
   *   The data or null.
   */
  private function getNestedArrayValue(array $array, $indexes): array|string|null {
    // When we reach the end of source path, get the value.
    if (count($indexes) === 1) {
      $value = $array[$indexes[0]];
      // Commented this out because of complex value must return array
      // if (!is_null($value) && !is_array($value)) {
      if (!is_null($value)) {
        if (is_array($value)) {
          return $value;
        }

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


  private function getMultipleNestedArrayValues(array $array, $indexes) {
    if (count($indexes) === 1) {
      return $array[$indexes[0]];
    }

    // If we are still traversing the array, keep going.
    if (isset($indexes[0]) && isset($array[$indexes[0]])) {
      return $this->getMultipleNestedArrayValues($array[$indexes[0]], array_slice($indexes, 1));
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
   * @param string|array $value
   *   The value.
   * @param array $definition
   *   Predefined data for the Avus2 document.
   *
   * @return void
   */
  private function setTargetValue(array &$data, string $targetPath, string|array $sourceValue, array $definition): void {
    // This is the predefined hardcoded part of the json data for all fields.
    $targetValue = $definition['data'];

    match($definition['mapping_type']) {
      'default' => $targetValue['value'] = $sourceValue,
      'multiple_values',
      'custom',
      'simple' => $targetValue = $sourceValue,
      // 'hardcoded' => $targetValue = $sourceValue,
      default => $targetValue['value'] = $sourceValue,
    };

    $this->setTargetValueRecursively(
      $data,
      explode('.', $targetPath),
      $targetValue
    );
  }

  /**
   * Traverse the target array recursively and set value.
   *
   * @param $data
   *   The actual data array.
   * @param $indexes
   *   Array of indexes to traverse.
   * @param $theValue
   *   The value whatever it may be.
   */
  private function setTargetValueRecursively(&$data, $indexes, $theValue): void {
    if (count($indexes) === 1) {
      // @todo Refactor exceptions.
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


  /**
   * Combine the data sources required by mapper.
   *
   * The application requires data from multiple sources.
   * For example form settings, grants profile and many more.
   *
   * @param array $formData
   * @param array $userData
   * @param array $companyData
   * @param array $userProfileData
   * @param GrantsProfile $grantsProfile
   * @param FormSettings $formSettings
   * @param string $applicationNumber
   * @param int $applicantTypeId
   *
   * @return array
   *   Array of data required by mapper.
   */
  public function getCombinedDataSources(
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

}
