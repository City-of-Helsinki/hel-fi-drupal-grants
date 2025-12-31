<?php

declare(strict_types=1);

namespace Drupal\grants_application\Mapper;

use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_application\Helper;
use Drupal\grants_application\User\GrantsProfile;
use Drupal\grants_attachments\AttachmentHandlerHelper;

/**
 * The json mapper.
 */
class JsonMapper {

  /**
   * A class filled with custom mapping functions.
   */
  private JsonHandler $customHandler;

  /**
   * The array of mappings to map from react to avus2.
   */
  private array $mappings;

  /**
   * The constructor.
   */
  public function __construct(
  ) {
    $this->customHandler = new JsonHandler();
  }

  /**
   * Set the mappings.
   *
   * @param array $mappings
   * @return void
   */
  public function setMappings(array $mappings): void {
    $this->mappings = $mappings;
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

    if (!$this->mappings) {
      throw new \Exception('You must call setMappings-method before running the mapper.');
    }

    foreach ($this->mappings as $target => $definition) {
      $sourcePath = $definition['source'];
      $dataSourceType = $definition['datasource'];

      // @todo Refactor empty & hardcoded maybe.
      if (!isset($definition['skip']) && ($definition['mapping_type'] === 'empty' || $definition['mapping_type'] === 'hardcoded')) {
        match($definition['mapping_type']) {
          'empty' => $this->handleEmpty($data, $definition, $target),
          'hardcoded' => $this->handleHardcoded($data, $definition, $target),
        };
        continue;
      }

      if (
        (isset($definition['skip']) && $definition['skip']) ||
        !$definition['source'] ||
        !$this->sourcePathExists($allDataSources, $dataSourceType, $sourcePath)
      ) {
        continue;
      }

      match($definition['mapping_type']) {
        'default' => $this->handleDefault($data, $definition, $target, $allDataSources),
        'multiple_values' => $this->handleMultipleValues($data, $definition, $target, $allDataSources),
        'custom' => $this->handleCustom($data, $definition, $target, $allDataSources),
        'simple' => $this->handleSimple($data, $definition, $target, $allDataSources),
        'hardcoded' => $this->handleHardcoded($data, $definition, $target),
        'empty' => $this->handleEmpty($data, $definition, $target),
        default => $this->handleDefault($data, $definition, $target, $allDataSources),
      };
    }

    return $data;
  }

  /**
   * Does the source path exist on datasource.
   *
   * @param array $dataSources
   *   All data sources.
   * @param string $sourceType
   *   The source type.
   * @param string $sourcePath
   *   The path to the data.
   *
   * @return bool
   *   Does the path exist on source data.
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
   * @param array $data
   *   The target data.
   * @param array $definition
   *   The mapping definition.
   * @param string $targetPath
   *   The target data path.
   * @param array $dataSources
   *   The data sources.
   */
  private function handleDefault(&$data, array $definition, string $targetPath, array $dataSources): void {
    $sourcePath = $definition['source'];

    $value = $this->getValue($dataSources[$definition['datasource']], $sourcePath);

    if (isset($definition['data']['valueType']) && $definition['data']['valueType'] === 'bool') {
      $value = $value ? 'true' : 'false';
    }

    $this->setTargetValue($data, $targetPath, $value, $definition);
  }

  /**
   * Handle empty data.
   *
   * @param array $data
   *   The target data.
   * @param array $definition
   *   The mapping definition.
   * @param string $targetPath
   *   The target data path.
   */
  private function handleEmpty(&$data, array $definition, $targetPath) {
    $this->setTargetValue($data, $targetPath, [], $definition);
  }

  /**
   * Handle a case where user may add n-items with n-fields.
   *
   * F.ex ID58, user may add multiple orienteeringMaps on one application.
   *
   * @param array $data
   *   The target data.
   * @param array $definition
   *   The mapping definition.
   * @param string $targetPath
   *   The target data path.
   * @param array $dataSources
   *   The data sources.
   */
  private function handleMultipleValues(&$data, array $definition, string $targetPath, array $dataSources): void {
    $sourcePath = $definition['source'];
    $targetPath = rtrim($targetPath, '.n');

    $sourceValues = $this->getMultipleValues($dataSources[$definition['datasource']], $sourcePath);

    // Source value contains multiple objects which contains multiple fields.
    // The fields may also contain nested values.
    foreach ($sourceValues as $z => $singleObject) {
      $values = [];
      foreach ($singleObject as $fieldName => $value) {
        if (is_array($value)) {
          foreach($value as $subfield => $subValue) {
            $definitionName = $fieldName.'.'.$subfield;
            $valueArray = $definition['data'][$definitionName];
            $valueArray['value'] = (string) $subValue ?? "";
            $values[] = $valueArray;
          }
        }
        else {
          $valueArray = $definition['data'][$fieldName];
          $valueArray['value'] =  is_bool($value) ? ($value ? "true" : "false") : (string) $value;
          $values[] = $valueArray;
        }
      }
      $this->setTargetValue($data, $targetPath, $values, $definition);
    }

  }

  /**
   * Just use the mapped data.
   *
   * @param array $data
   *   The target data.
   * @param array $definition
   *   The mapping definition.
   * @param string $targetPath
   *   The target data path.
   */
  private function handleHardcoded(&$data, array $definition, string $targetPath): void {
    $value = array_values($definition['data'])[0];
    $this->setTargetValue($data, $targetPath, $value, $definition);
  }

  /**
   * Handle the case where we just set a key and value to the target.
   *
   * @param array $data
   *   The target data.
   * @param array $definition
   *   The mapping definition.
   * @param string $targetPath
   *   The target data path.
   * @param array $dataSources
   *   The data sources.
   */
  private function handleSimple(&$data, array $definition, string $targetPath, array $dataSources): void {
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
   * @param array $data
   *   The target data array.
   * @param array $definition
   *   The mapping definition.
   * @param string $targetPath
   *   The target path.
   * @param array $dataSources
   *   The data sources.
   */
  private function handleCustom(&$data, array $definition, string $targetPath, array $dataSources): void {
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

  /**
   * Get a value for field from source-data.
   *
   * @param array $sourceData
   *   The source data.
   * @param string $sourcePath
   *   Path to the data.
   *
   * @return array|string|null
   *   The value.
   */
  private function getValue(array $sourceData, string $sourcePath): array|string|null {
    $path = explode('.', $sourcePath);
    return $this->getNestedArrayValue($sourceData, $path);
  }

  /**
   * Traverse an array recursively and return target value.
   *
   * @param array $sourceData
   *   The source data -array.
   * @param array $indexes
   *   The array indexes.
   *
   * @return array|string|null
   *   The data or null.
   */
  private function getNestedArrayValue(array $sourceData, array $indexes): array|string|null {
    // When we reach the end of source path, get the value.
    if (count($indexes) === 1) {
      $value = $sourceData[$indexes[0]];
      if (is_null($value)) {
        return "";
      }

      if (is_array($value)) {
        return $value;
      }

      // All values must be string.
      return (string) $value;
    }

    // If we are still traversing the array, keep going.
    if (isset($indexes[0]) && isset($sourceData[$indexes[0]])) {
      return $this->getNestedArrayValue($sourceData[$indexes[0]], array_slice($indexes, 1));
    }
    return NULL;
  }

  /**
   * Get all the values for the multivalue-field.
   *
   * @param array $sourceData
   *   The source data.
   * @param string $sourcePath
   *   The source path.
   *
   * @return array|string|null
   *   The value from source-data
   */
  private function getMultipleValues(array $sourceData, string $sourcePath): array|string|null {
    $path = explode('.', $sourcePath);
    return $this->getMultipleNestedArrayValues($sourceData, $path);
  }

  /**
   * Get values for the multi-value field recursively.
   *
   * @param array $sourceData
   *   The source data.
   * @param array $indexes
   *   The json-path as array.
   *
   * @return mixed|null
   *   The value.
   */
  private function getMultipleNestedArrayValues(array $sourceData, array $indexes) {
    if (count($indexes) === 1) {
      return $sourceData[$indexes[0]];
    }

    // If we are still traversing the array, keep going.
    if (isset($indexes[0]) && isset($sourceData[$indexes[0]])) {
      return $this->getMultipleNestedArrayValues($sourceData[$indexes[0]], array_slice($indexes, 1));
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
   * @param string|array $sourceValue
   *   The source value.
   * @param array $definition
   *   Predefined data for the Avus2 document.
   */
  private function setTargetValue(array &$data, string $targetPath, string|array $sourceValue, array $definition): void {
    // This is the predefined hardcoded part of the json data for all fields.
    $targetValue = $definition['data'];

    match($definition['mapping_type']) {
      'default' => $targetValue['value'] = $sourceValue,
      'multiple_values',
      'custom',
      'simple' => $targetValue = $sourceValue,
      'empty' => $targetValue = [],
      'hardcoded' => $targetValue = $sourceValue,
      'file' => $targetValue = $sourceValue,
      default => $targetValue['value'] = $sourceValue,
    };

    // @todo Refactor hardcoded.
    $key = NULL;
    if ($definition['mapping_type'] == 'hardcoded') {
      $key = array_key_first($definition['data']);
    }

    $this->setTargetValueRecursively(
      $data,
      explode('.', $targetPath),
      $targetValue,
      $key
    );
  }

  /**
   * Traverse the target array recursively and set value.
   *
   * @param array $data
   *   The actual data array.
   * @param array $indexes
   *   Array of indexes to traverse.
   * @param mixed $theValue
   *   The value whatever it may be.
   * @param string $key
   *   Key for hardcoded value.
   */
  private function setTargetValueRecursively(&$data, $indexes, $theValue, $key = NULL): void {
    if (count($indexes) === 1) {

      // Hardcoded values are just key: value.
      if ($indexes[0] === $key) {
        $data[$key] = $theValue;
        return;
      }

      // @todo Refactor exceptions.
      if ($indexes[0] === 'additionalInformation') {
        $data[$indexes[0]] = $theValue;
        return;
      }
      elseif (is_array($theValue) && empty($theValue)) {
        // Allow setting empty value to target data.
        $data[] = $theValue;
        return;
      }

      if (is_numeric($indexes[0])) {
        $data[] = $theValue;
        return;
      }

      $data[$indexes[0]] = $theValue;
      return;
    }

    $this->setTargetValueRecursively($data[$indexes[0]], array_slice($indexes, 1), $theValue, $key);
  }

  /**
   * Map all files added to the application.
   *
   * The file data lives outside of compensations in the final data.
   * The files are mapped in attachmentsInfo.attachmentsArray.
   *
   * @param array $dataSources
   *   The datasources.
   *
   * @return array
   *   Array of mapped files.
   */
  public function mapFiles(array $dataSources): array {
    $fileData = [];

    $definitions = array_filter($this->mappings, fn(array $item) => $item['mapping_type'] === 'file');
    foreach ($definitions as $targetPath => $definition) {
      $this->handleFile($fileData, $definition, $targetPath, $dataSources);
    }

    return $fileData;
  }

  /**
   * Get the values from the form and map it in correct format.
   *
   * @param mixed $data
   *   The final data.
   * @param array $definition
   *   The file-field definitions from mapping-json.
   * @param string $targetPath
   *   The json-path to target data location.
   * @param array $dataSources
   *   The data sources.
   */
  private function handleFile(&$data, array $definition, string $targetPath, array $dataSources): void {
    $value = $this->getFileData($definition['data'], $dataSources[$definition['datasource']], $definition['source']);
    $this->setTargetValue($data, $targetPath, $value, $definition);
  }

  /**
   * Get all required field values for file.
   *
   * There are two cases we must handle here:
   * 1. When an actual uploaded file is mapped
   * 2. The file will be sent in future or has already been sent.
   * Both cases require different amount of fields.
   * The default values for file-fields must be in mappings-json.
   *
   * @param array $defaultData
   *   Array of default values for file, comes from mapping.json.
   * @param array $sourceData
   *   The data sources.
   * @param string $sourcePath
   *   The path to data inside datasource.
   *
   * @return array
   *   A mapped file with all required fields.
   */
  private function getFileData(array $defaultData, array $sourceData, string $sourcePath): array {
    $formValues = $this->getNestedArrayValue($sourceData, explode('.', $sourcePath));

    // Figure out which fields to send.
    $defaultFieldsForNoFile = ['description', 'fileType', 'isDeliveredLater', 'isIncludedInOtherFile'];
    $defaultFieldsForFile = array_keys($defaultData);
    $fieldNames = isset($formValues['integrationID']) ? $defaultFieldsForFile : $defaultFieldsForNoFile;

    $values = [];
    // Loop through the required fields and read the data from formValues.
    foreach ($fieldNames as $fieldName) {
      // Use the default value-array as base for the data.
      $field = $defaultData[$fieldName];

      // And overwrite the value -value if necessary.
      // @todo Remove filetype-condition once react-form no longer sends it.
      if (isset($formValues[$fieldName]) && $fieldName !== 'fileType') {
        $val = $defaultData[$fieldName]['value'];

        // And make sure we are adding the boolean as a string.
        if ($defaultData[$fieldName]['valueType'] === 'bool') {
          $field['value'] = isset($formValues[$fieldName]) && $formValues[$fieldName] ? 'true' : 'false';
        }
        else if ($defaultData[$fieldName]['ID'] === 'integrationID' && $formValues[$fieldName]) {
          $field['value'] = '';
          $urlPath = parse_url($formValues['integrationID'], PHP_URL_PATH);
          if ($urlPath) {
            // Production doesn't require the environment name in integrationID.
            // Dev/testing/staging requires env name generated by helper function.
            // Original implementation GrantsAttachments::739
            if (Helper::getAppEnv() === 'PROD') {
              $field['value'] = $urlPath;
            } else {
              $field['value'] = '/' . Helper::getAppEnv() . $urlPath;
            }
          }
        }
        else {
          $field['value'] = isset($formValues[$fieldName]) ? (string) $formValues[$fieldName] : $val;
        }
      }

      $values[] = $field;
    }

    return $values;
  }

  /**
   * Handle the bank file mapping.
   *
   * @param string $selected_bank_account
   *   The selected bank account number.
   * @param array $bank_file
   *   The uploaded bank file.
   *
   * @return array
   *   Mapping for bank file.
   */
  public function mapBankFile(string $selected_bank_account, array $bank_file): array {
    $integrationID = AttachmentHandlerHelper::getIntegrationIdFromFileHref($bank_file['href']);
    $integrationID = AttachmentHandlerHelper::addEnvToIntegrationId($integrationID);

    $description = "Vahvistus tilinumerolle $selected_bank_account";
    $filename = $bank_file['filename'];
    $isDeliveredLater = 'false';
    $isIncludedInOtherFile = 'false';
    $filetype = "45";

    return [
      ['ID' => 'description', 'value' => $description, 'valueType' => 'string', 'label' => 'Liitteen kuvaus'],
      ['ID' => 'fileName', 'value' => $filename, 'valueType' => 'string', 'label' => 'Tiedostonimi'],
      ['ID' => 'fileType', 'value' => $filetype, 'valueType' => 'int', 'label' => "filetype"],
      ['ID' => 'integrationID', 'value' => $integrationID, 'valueType' => 'string', 'label' => "integrationID"],
      [
        'ID' => 'isDeliveredLater',
        'value' => $isDeliveredLater,
        'valueType' => 'bool',
        'label' => 'Liite toimitetaan myöhemmin',
      ],
      [
        'ID' => 'isIncludedInOtherFile',
        'value' => $isIncludedInOtherFile,
        'valueType' => 'bool',
        'label' => 'Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä',
      ],
    ];
  }

  /**
   * Get the current status value from document.
   *
   * @param array $document
   *   The ATV document as array.
   *
   * @return string
   *   The status.
   */
  public function getStatusValue(array $document): string {
    $applicationInfoArray = $document['content']['compensation']['applicationInfoArray'];
    $statusArray = array_find($applicationInfoArray, fn($item) => $item['ID'] === 'status');
    foreach ($statusArray as $key => $status) {
      if ($key === 'value') {
        return $status;
      }
    }
    return '';
  }

  /**
   * Set the status to a document data.
   *
   * Status is special field since it is initialized by us but
   * updated by external system. We may not overwrite it.
   *
   * @param array $documentData
   *   The document data.
   * @param string $oldStatusValue
   *   The old status value.
   */
  public function setStatusValue(array &$documentData, string $oldStatusValue): void {
    $applicationInfoArray = $documentData['compensation']['applicationInfoArray'];

    foreach ($applicationInfoArray as $key => $field) {
      if ($field['ID'] === 'status') {
        $documentData['compensation']['applicationInfoArray'][$key]['value'] = $oldStatusValue;
        break;
      }
    }
  }

  /**
   * Status is the only field which is altered by someone else after submit.
   *
   * @param array $mappedFiles
   *   The attachmentsInfo.attachmentsArray from atv document content.
   *
   * @return bool
   *   Bank file exists.
   */
  public function hasBankFile(array $mappedFiles): bool {
    foreach ($mappedFiles as $fileArray) {
      return (bool) array_find($fileArray, fn($item) => $item['fileType'] === 45);
    }
    return FALSE;
  }

  /**
   * Get the bank file.
   *
   * @param array $mappedFiles
   *   Contents of attachmentsInfo.attachmentsArray.
   *
   * @return array
   *   The bank file.
   */
  public function getMappedBankFile(array $mappedFiles): ?array {
    foreach ($mappedFiles as $fileArray) {
      if (array_find($fileArray, fn($item) => $item['ID'] === 'fileType' && $item['value'] == 45)) {
        return $fileArray;
      }
    }
    return NULL;
  }

}
