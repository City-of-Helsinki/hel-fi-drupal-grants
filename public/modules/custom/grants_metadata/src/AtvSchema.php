<?php

namespace Drupal\grants_metadata;

use Drupal\Component\Serialization\Json;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\grants_attachments\AttachmentHandler;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Map ATV documents to typed data.
 */
class AtvSchema {

  use StringTranslationTrait;
  /**
   * Drupal\Core\TypedData\TypedDataManager definition.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  protected TypedDataManagerInterface $typedDataManager;

  /**
   * Schema structure as parsed from schema file.
   *
   * @var array
   */
  protected array $structure;

  /**
   * Path to schema file.
   *
   * @var string
   */
  protected string $atvSchemaPath;

  /**
   * Constructs an AtvShcema object.
   */
  public function __construct(TypedDataManagerInterface $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * Load json schema file.
   *
   * @param string $schemaPath
   *   Path to schema file.
   */
  public function setSchema(string $schemaPath) {

    if ($schemaPath != '') {
      $jsonString = file_get_contents($schemaPath);
      $jsonStructure = Json::decode($jsonString);

      $this->structure = $jsonStructure;
    }

  }

  /**
   * Map document structure to typed data object.
   *
   * @param array $documentData
   *   Document as array.
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionInterface $typedDataDefinition
   *   Data definition for this document / application.
   * @param array|null $metadata
   *   Metadata to attach.
   *
   * @return array
   *   Mapped dta from document.
   */
  public function documentContentToTypedData(
    array $documentData,
    ComplexDataDefinitionInterface $typedDataDefinition,
    ?array $metadata = []
  ): array {
    if (isset($documentData['content']) && is_array($documentData['content'])) {
      $documentContent = $documentData['content'];
    }
    else {
      $documentContent = $documentData;
    }

    $propertyDefinitions = $typedDataDefinition->getPropertyDefinitions();

    $typedDataValues = [];

    foreach ($propertyDefinitions as $definitionKey => $definition) {
      $jsonPath = $definition->getSetting('jsonPath');
      $webformDataExtractor = $definition->getSetting('webformDataExtracter');

      if ($webformDataExtractor) {
        $arguments = $webformDataExtractor['arguments'] ?? [];
        $extractedValues = self::getWebformDataFromContent($webformDataExtractor, $documentData, $definition, $arguments);
        if (isset($webformDataExtractor['mergeResults']) && $webformDataExtractor['mergeResults']) {
          $typedDataValues = array_merge($typedDataValues, $extractedValues);
        }
        else {
          $typedDataValues[$definitionKey] = $extractedValues;
        }
      }
      else {
        // If json path not configured for item, do nothing.
        if (is_array($jsonPath)) {
          $elementName = array_pop($jsonPath);

          $typedDataValues[$definitionKey] = DocumentValueExtractor::getValueFromDocument(
            $documentContent, $jsonPath, $elementName, $definition
          );
        }
      }
    }
    if (isset($typedDataValues['status_updates']) && is_array($typedDataValues['status_updates'])) {
      // Loop status updates & set the last one as submission status.
      foreach ($typedDataValues['status_updates'] as $status) {
        $typedDataValues['status'] = $status['citizenCaseStatus'];
      }
    }

    $other_attachments = [];
    $attachmentFileTypes = AttachmentHandler::getAttachmentFieldNames($typedDataValues["application_number"], TRUE);

    if (!isset($typedDataValues["attachments"])) {
      $typedDataValues["attachments"] = [];
    }

    foreach ($typedDataValues["attachments"] as $key => $attachment) {
      $fileType = $attachment["fileType"];
      $fieldName = array_search($fileType, $attachmentFileTypes);
      $newValues = $attachment;

      // If we have fileName property we know the file is definitely not new.
      if (isset($attachment["fileName"]) && $attachment["fileName"] !== '') {
        $newValues["isNewAttachment"] = 'false';
        $newValues['attachmentName'] = $attachment['fileName'];
      }

      // Attachments under "muu_liite" and the bank account confirmation
      // file (type 45) should all go under $other_attachments.
      if ($fieldName === 'muu_liite' || (int) $fileType === 45) {
        $other_attachments[$key] = $newValues;
        unset($typedDataValues["attachments"][$key]);
      }
      else {
        $typedDataValues[$fieldName] = $newValues;
      }
    }

    // Fix for issuer_name vs. issuerName case.
    if (isset($typedDataValues['myonnetty_avustus'])) {
      foreach ($typedDataValues['myonnetty_avustus'] as $key => $avustus) {
        if (isset($avustus['issuerName'])) {
          $typedDataValues['myonnetty_avustus'][$key]['issuer_name'] = $avustus['issuerName'];
        }
      }
    }
    if (isset($typedDataValues['haettu_avustus_tieto'])) {
      foreach ($typedDataValues['haettu_avustus_tieto'] as $key => $avustus) {
        if (isset($avustus['issuerName'])) {
          $typedDataValues['haettu_avustus_tieto'][$key]['issuer_name'] = $avustus['issuerName'];
        }
      }
    }

    $community_address = [];
    if (isset($typedDataValues['community_street'])) {
      $community_address['community_street'] = $typedDataValues['community_street'];
      unset($typedDataValues['community_street']);
    }
    if (isset($typedDataValues['community_city'])) {
      $community_address['community_city'] = $typedDataValues['community_city'];
      unset($typedDataValues['community_city']);
    }
    if (isset($typedDataValues['community_post_code'])) {
      $community_address['community_post_code'] = $typedDataValues['community_post_code'];
      unset($typedDataValues['community_post_code']);
    }
    if (isset($typedDataValues['community_country'])) {
      $community_address['community_country'] = $typedDataValues['community_country'];
      unset($typedDataValues['community_country']);
    }

    $typedDataValues['community_address'] = $community_address;

    if (isset($typedDataValues['account_number'])) {
      $typedDataValues['bank_account']['account_number'] = $typedDataValues['account_number'];
      $typedDataValues['bank_account']['account_number_select'] = $typedDataValues['account_number'];
      $typedDataValues['bank_account']['account_number_ssn'] = $typedDataValues['account_number_ssn'] ?? NULL;
      $typedDataValues['bank_account']['account_number_owner_name'] = $typedDataValues['account_number_owner_name'] ?? NULL;
    }

    if (isset($typedDataValues['community_practices_business'])) {
      if ($typedDataValues['community_practices_business'] === 'false') {
        $typedDataValues['community_practices_business'] = 0;
      }
      if ($typedDataValues['community_practices_business'] === 'true') {
        $typedDataValues['community_practices_business'] = 1;
      }
    }

    if (isset($typedDataValues['hakijan_tiedot']['applicantType'])) {
      $typedDataValues['applicant_type'] = $typedDataValues['hakijan_tiedot']['applicantType'];
    }

    $typedDataValues['muu_liite'] = $other_attachments;
    $typedDataValues['metadata'] = $metadata;
    return $typedDataValues;

  }

  /**
   * PArse accepted json datatype & actual datatype from definitions.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   Data definition for item.
   *
   * @return string[]
   *   Array with dataType & jsonType.
   */
  public static function getJsonTypeForDataType(DataDefinitionInterface $definition): array {
    $propertyType = $definition->getDataType();
    // Default both types same.
    $retval = [
      'dataType' => $propertyType,
      'jsonType' => $propertyType,
    ];
    // If override, then override given value.
    if ($typeOverride = $definition->getSetting('typeOverride')) {
      if (isset($typeOverride['dataType'])) {
        $retval['dataType'] = $typeOverride['dataType'];
      }
      if (isset($typeOverride['jsonType'])) {
        $retval['jsonType'] = $typeOverride['jsonType'];
      }
    }
    if ($propertyType == 'integer') {
      $retval['jsonType'] = 'int';
    }
    elseif ($propertyType == 'datetime_iso8601') {
      $retval['jsonType'] = 'datetime';
    }
    elseif ($propertyType == 'boolean') {
      $retval['jsonType'] = 'bool';
    }

    return $retval;
  }

  /**
   * Sanitize input to make sure there's no illegal input.
   *
   * @param mixed $value
   *   Value to be sanitized.
   *
   * @return mixed
   *   Sanitized value.
   */
  public static function sanitizeInput(mixed $value): mixed {

    if (is_array($value)) {
      array_walk_recursive($value, function (&$item) {
        if (is_string($item)) {
          $item = filter_var($item, FILTER_UNSAFE_RAW);
        }
      });
    }
    else {
      $value = filter_var($value, FILTER_UNSAFE_RAW);
    }

    return $value;
  }

  /**
   * Generate document content JSON from typed data.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typedData
   *   Typed data to export.
   * @param \Drupal\webform\Entity\WebformSubmission $webformSubmission
   *   Form submission entity.
   * @param array $submittedFormData
   *   Form data from actual submission.
   *
   * @return array
   *   Document structure based on schema.
   */
  public function typedDataToDocumentContent(
    TypedDataInterface $typedData,
    WebformSubmission $webformSubmission,
    array $submittedFormData
  ): array {
    $webform = $webformSubmission->getWebform();
    $pages = $webform->getPages('edit', $webformSubmission);
    return $this->typedDataToDocumentContentWithWebform(
      $typedData,
      $webform,
      $pages,
      $submittedFormData
    );
  }

  /**
   * Generate document content JSON from typed data using submission.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typedData
   *   Typed data to export.
   * @param \Drupal\webform\Entity\Webform $webform
   *   Form entity.
   * @param array $pages
   *   Page structure of webform.
   * @param array $submittedFormData
   *   Data from form.
   *
   * @return array
   *   Document structure based on schema.
   */
  public function typedDataToDocumentContentWithWebform(
    TypedDataInterface $typedData,
    Webform $webform,
    array $pages,
    array $submittedFormData
  ): array {
    return TypedDataToDocumentContentWithWebform::getTypedDataToDocumentContentWithWebform(
      $typedData,
      $webform,
      $pages,
      $submittedFormData,
      $this->structure);
  }

  /**
   * Look for numeric keys in array, and return if they're found or not.
   *
   * @param array $array
   *   Array to look in.
   *
   * @return bool
   *   Is there only numeric keys?
   */
  public static function numericKeys(array $array): bool {
    $non_numeric_key_found = FALSE;

    foreach (array_keys($array) as $key) {
      if (!is_numeric($key)) {
        $non_numeric_key_found = TRUE;
      }
    }
    return !$non_numeric_key_found;
  }

  /**
   * Parse incorrect json string & decode.
   *
   * @param array $atvDocument
   *   Document structure.
   *
   * @return array
   *   Decoded content.
   */
  public function getAtvDocumentContent(array $atvDocument): array {
    if (is_string($atvDocument['content'])) {
      $replaced = str_replace("'", "\"", $atvDocument['content']);
      $replaced = str_replace("False", "false", $replaced);
      $replaced = str_replace("True", "true", $replaced);

      return Json::decode($replaced);
    }
    return $atvDocument['content'];
  }

  /**
   * Set content item to given document.
   *
   * @param array $atvDocument
   *   Document.
   * @param array $atvDocumentContent
   *   Content.
   *
   * @return array
   *   Added array.
   */
  public function setAtvDocumentContent(array $atvDocument, array $atvDocumentContent): array {
    $atvDocument['content'] = $atvDocumentContent;
    return $atvDocument;
  }

  /**
   * Format data type from item types.
   *
   * Use default value & value callback if applicable.
   *
   * @param array $itemTypes
   *   Item types for this value.
   * @param mixed $itemValue
   *   Value itself.
   * @param mixed $defaultValue
   *   Default value used if no value given. Configurable in typed data.
   * @param mixed $valueCallback
   *   Callback to handle value formatting. Configurable in typed data.
   *
   * @return mixed
   *   Formatted value.
   */
  public static function getItemValue(array $itemTypes, mixed $itemValue, mixed $defaultValue, mixed $valueCallback): mixed {

    // Support new value callback format to use either service or class.
    if ($valueCallback) {
      if (isset($valueCallback['service'])) {
        $fullItemValueService = \Drupal::service($valueCallback['service']);
        $funcName = $valueCallback['method'];
        $itemValue = $fullItemValueService->$funcName($itemValue, $valueCallback['arguments'] ?? []);
      }
      elseif (isset($valueCallback['class'])) {
        $funcName = $valueCallback['method'];
        $itemValue = $valueCallback['class']::$funcName($itemValue, $valueCallback['arguments'] ?? []);
      }
      else {
        // But still support old way to just call function.
        $itemValue = call_user_func($valueCallback, $itemValue);
      }
    }

    // If value is null, try to set default value from config.
    if (is_null($itemValue) && $defaultValue !== NULL) {
      $itemValue = $defaultValue;
    }

    if ($itemTypes['dataType'] === 'string' && $itemTypes['jsonType'] !== 'bool') {
      $itemValue = $itemValue . "";
    }

    if ($itemTypes['dataType'] === 'string' && $itemTypes['jsonType'] === 'bool') {
      $itemValue = self::convertToBooleanString($itemValue);
    }

    if ($itemTypes['jsonType'] == 'int') {
      $itemValue = str_replace('_', '', $itemValue);
    }

    return $itemValue;
  }

  /**
   * Converts a value to its boolean string representation.
   *
   * @param mixed $itemValue
   *   The item value to be converted.
   *
   * @return string
   *   The converted boolean string representation of the item value.
   */
  private static function convertToBooleanString(mixed $itemValue): string {
    $falseValues = [FALSE, '0', 0, 'No'];
    $trueValues = [TRUE, '1', 1, 'Yes'];

    if (in_array($itemValue, $falseValues, TRUE)) {
      return 'false';
    }

    if (in_array($itemValue, $trueValues, TRUE)) {
      return 'true';
    }

    // Return orignal values as fallback.
    return $itemValue;
  }

  /**
   * Get field values from full item callback.
   *
   * @param array $fullItemValueCallback
   *   Callback config.
   * @param array $content
   *   Content.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   Definition.
   * @param array $arguments
   *   Possible arguments for value callback.
   *
   * @return array
   *   Full item callback array.
   */
  public function getWebformDataFromContent(
    array $fullItemValueCallback,
    array $content,
    DataDefinitionInterface $definition,
    array $arguments
  ): mixed {
    $fieldValues = [];
    if (isset($fullItemValueCallback['service'])) {
      $fullItemValueService = self::getDynamicService($fullItemValueCallback['service']);
      $funcName = $fullItemValueCallback['method'];

      $fieldValues = $fullItemValueService->$funcName($definition, $content, $arguments);
    }
    else {
      if (isset($fullItemValueCallback['class'])) {
        $funcName = $fullItemValueCallback['method'];
        $fieldValues = $fullItemValueCallback['class']::$funcName($definition, $content, $arguments);
      }
    }
    return $fieldValues;
  }

  /**
   * Extracts data from ATV document compensation field.
   *
   * @param array $content
   *   ATV document in array form.
   * @param array $keys
   *   Array with IDs that the function will look for.
   *
   * @return array
   *   Associative array with the results if they are found.
   */
  public static function extractDataForWebForm(array $content, array $keys): array {
    $values = [];

    if (!isset($content['compensation'])) {
      return $values;
    }

    self::extractValuesRecursively($content['compensation'], $keys, $values);

    return $values;
  }

  /**
   * Recursively extract values from nested arrays.
   *
   * @param array $items
   *   The array to search through.
   * @param array $keys
   *   Array with IDs that the function will look for.
   * @param array &$values
   *   Associative array to store the results.
   */
  private static function extractValuesRecursively(array $items, array $keys, array &$values) {
    foreach ($items as $key => $item) {
      if (is_array($item)) {
        self::extractValuesRecursively($item, $keys, $values);
      }
      if (in_array($key, $keys) && !in_array($key, $values)) {
        $values[$key] = $item;
      }
      if (is_numeric($key) &&
          !AtvSchema::numericKeys($item) &&
          isset($item['ID']) &&
          in_array($item['ID'], $keys) &&
          !in_array($item['ID'], $values)) {
        $values[$item['ID']] = $item['value'];
      }
    }
  }

  /**
   * Extracts data from ATV document compensation field.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   Field definition.
   * @param array $content
   *   ATV data.
   * @param array $arguments
   *   Arguments for method.
   *
   * @return array
   *   Assocative array with fields.
   *
   * @throws \Exception
   */
  public function returnRelations(DataDefinitionInterface $definition, array $content, array $arguments): array {
    /*
     * Fields in relations array:
     * master: Field name in ATV
     * slave: Field that exists in webform and is calculated based on master.
     * type: Type of the slave field.
     */
    $relations = $arguments['relations'];
    $pathArray = $definition->getSetting('jsonPath');
    $elementName = array_pop($pathArray);
    // Pick up the value for the master field.
    $value = DocumentValueExtractor::getValueFromDocument($content, $pathArray, $elementName, $definition);
    $relatedValue = match ($relations['type']) {
      // We use values 1 and 0 for boolean fields in webform.
      'boolean' => $value ? 1 : 0,
      default => throw new \Exception('Unknown relation type.'),
    };
    $retval = [
      $relations['master'] => $value,
      $relations['slave'] => $relatedValue,
    ];
    return $retval;
  }

  /**
   * Get metadata array for JSON schema meta field.
   *
   * This function is used to guarantee that meta field in
   * schema will always have necessary fields.
   *
   * @param array|null $page
   *   Array with page data.
   * @param array|null $section
   *   Array with section data.
   * @param array|null $element
   *   Array with element data.
   *
   * @return array
   *   MetaData array
   */
  public static function getMetaData(?array $page = [], ?array $section = [], ?array $element = []): array {
    $metaData = [
      'page' => [
        'id' => $page['id'] ?? 'unknown_page',
        'number' => $page['number'] ?? -1,
        'label' => $page['label'] ?? 'Page',
      ],
      'section' => [
        'id' => $section['id'] ?? 'unknown_section',
        'weight' => $section['weight'] ?? -1,
        'label' => $section['label'] ?? 'Section',
      ],
      'element' => [
        'weight' => $element['weight'] ?? -1,
        'label' => $element['label'] ?? 'Element',
        'hidden' => $element['hidden'] ?? FALSE,
      ],
    ];

    if (isset($element['input_mask'])) {
      $metaData['element']['input_mask'] = $element['input_mask'];
    }

    return $metaData;
  }

  /**
   * Get dynamically defined service via Drupal::service method.
   *
   * @param string $serviceName
   *   Service name.
   */
  public static function getDynamicService(string $serviceName) {
    return \Drupal::service($serviceName);
  }

}
