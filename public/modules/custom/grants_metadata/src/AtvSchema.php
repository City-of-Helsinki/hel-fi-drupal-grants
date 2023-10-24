<?php

namespace Drupal\grants_metadata;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\Annotation\DataType;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\grants_attachments\AttachmentHandler;
use Drupal\grants_attachments\Element\GrantsAttachments as GrantsAttachmentsElement;
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
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected TypedDataManager $typedDataManager;

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
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * Constructs an AtvShcema object.
   */
  public function __construct(TypedDataManager $typed_data_manager, LoggerChannelFactory $loggerFactory) {

    $this->typedDataManager = $typed_data_manager;
    $this->logger = $loggerFactory->get('grants_attachments');

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

          $typedDataValues[$definitionKey] = $this->getValueFromDocument($documentContent, $jsonPath, $elementName, $definition);
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
    }

    if (isset($typedDataValues['community_practices_business'])) {
      if ($typedDataValues['community_practices_business'] === 'false') {
        $typedDataValues['community_practices_business'] = 0;
      }
      if ($typedDataValues['community_practices_business'] === 'true') {
        $typedDataValues['community_practices_business'] = 1;
      }
    }

    $typedDataValues['muu_liite'] = $other_attachments;
    $typedDataValues['metadata'] = $metadata;
    return $typedDataValues;

  }

  /**
   * Get schema definition for single property.
   *
   * @param string $elementName
   *   Name of the element.
   * @param array $structure
   *   Full schema structure.
   *
   * @return mixed
   *   Schema for given property.
   */
  protected function getPropertySchema(string $elementName, array $structure): mixed {

    foreach ($structure['properties'] as $topLevelElement) {
      if ($topLevelElement['type'] == 'object') {
        if (array_key_exists($elementName, $topLevelElement['properties'])) {
          return $topLevelElement['properties'][$elementName];
        }
        else {
          foreach ($topLevelElement['properties'] as $element0) {
            if ($element0['type'] == 'array') {
              if ($element0['items']['type'] == 'object') {
                if (in_array($elementName, $element0['items']['properties']['ID']['enum'])) {
                  return $element0['items'];
                }
              }
              else {
                if (in_array($elementName, $element0['items']['items']['properties']['ID']['enum'])) {
                  return $element0['items']['items'];
                }
              }
            }
            if ($element0['type'] == 'object') {
              if (array_key_exists($elementName, $element0['properties'])) {
                return $element0['properties'][$elementName];
              }
              else {
                foreach ($element0['properties'] as $element1) {
                  if ($element1['type'] == 'array') {
                    if ($element1['items']['type'] == 'object') {
                      if (isset($element1['items']['properties']['ID']) && array_key_exists('enum', $element1['items']['properties']['ID'])) {
                        if (is_array($element1['items']['properties']['ID']['enum']) && in_array($elementName, $element1['items']['properties']['ID']['enum'])) {
                          return $element1['items'];
                        }
                      }
                    }
                    else {
                      if (in_array($elementName, $element1['items']['items']['properties']['ID']['enum'])) {
                        return $element1['items']['items'];
                      }
                    }
                  }
                }
              }
            }
            if ($element0['type'] == 'string') {
              return $element0;
            }
          }
        }
      }
    }
    return NULL;
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
   * The modifyCallback method.
   *
   * This method adds the $webform and $submittedFormData
   * variables to a callbacks arguments if they have
   * been defined.
   *
   * @param array|null $callback
   *   The callback we are altering.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform we are handling.
   * @param array $submittedFormData
   *   The webforms submitted data.
   *
   * @return array|null
   *   Return the callback or null if it is not defined.
   */
  protected function modifyCallback(?array $callback, Webform $webform, array $submittedFormData): ?array {
    if (!$callback) {
      return NULL;
    }
    if (isset($callback['webform'])) {
      $callback['arguments']['webform'] = $webform;
    }
    if (isset($callback['submittedData'])) {
      $callback['arguments']['submittedData'] = $submittedFormData;
    }
    return $callback;
  }

  /**
   * The modifyPropertyName method.
   *
   * This method modifies the property name.
   *
   * @param string $propertyName
   *   The name of the property.
   * @param bool $strictTarget
   *   A boolean indicating if the property name
   *   should strictly be compared against certain
   *   targets.
   *
   * @return string
   *   The unmodified or modified property name.
   */
  protected function modifyPropertyName(string $propertyName, ?bool $strictTarget = false): string {
    if ($strictTarget) {
      if ($propertyName === 'account_number') {
        $propertyName = 'bank_account';
      }
      return $propertyName;
    }
    if ($this->isAddressField($propertyName)) {
      $propertyName = 'community_address';
    }
    if ($this->isBankAccountField($propertyName)) {
      $propertyName = 'bank_account';
    }

    return $propertyName;
  }

  /**
   * The isAddressField method.
   *
   * This method checks if a $propertyName
   * can be considered as an "address" field.
   *
   * @param string $propertyName
   *   The name of the property.
   *
   * @return bool
   *   True if the property name is an address field,
   *   false otherwise.
   */
  protected function isAddressField(string $propertyName): bool {
    return $propertyName == 'community_street' ||
           $propertyName == 'community_city' ||
           $propertyName == 'community_post_code' ||
           $propertyName == 'community_country';
  }

  /**
   * The isBankAccountField method.
   *
   * This method checks if a $propertyName
   * can be considered as a "bank account" field.
   *
   * @param string $propertyName
   *   The name of the property.
   *
   * @return bool
   *   True if the property name is a bank account field,
   *   false otherwise.
   */
  protected function isBankAccountField(string $propertyName): bool {
    return $propertyName == 'account_number_owner_name' ||
           $propertyName == 'account_number_ssn';
  }

  /**
   * The isBudgetField method.
   *
   * This method checks if a $propertyName
   * can be considered as a "budget" field.
   *
   * @param string $propertyName
   *   The name of the property.
   *
   * @return bool
   *   True if the property name is a budget field,
   *   false otherwise.
   */
  protected function isBudgetField(string $propertyName): bool {
    return $propertyName == 'budgetInfo';
  }

  /**
   * The isBudgetField method.
   *
   * This method checks if a $propertyName
   * can be considered as a "attachment" field.
   *
   * @param string $propertyName
   *   The name of the property.
   *
   * @return bool
   *   True if the property name is a attachment field,
   *   false otherwise.
   */
  protected function isAttachmentField(string $propertyName): bool {
    return $propertyName == 'attachments';
  }

  /**
   * The isRegularField method.
   *
   * This method checks if a $propertyName
   * can be considered as a "regular" field.
   *
   * @param string $propertyName
   *   The name of the property.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The Webform we are extracting data from.
   *
   * @return bool
   *   True if the property name is a budget field,
   *   false otherwise.
   */
  protected function isRegularField(string $propertyName, Webform $webform): bool {
    $webformElement = $webform->getElement($propertyName);
    return $propertyName !== 'form_update' &&
           $propertyName !== 'messages' &&
           $propertyName !== 'status_updates' &&
           $propertyName !== 'events' &&
           ($webformElement !== NULL ||
            $this->isAddressField($propertyName) ||
            $this->isBankAccountField($propertyName) ||
            $this->isBudgetField($propertyName)
           );
  }

  /**
   * The valueIsEmpty method.
   *
   * This method checks whether a $itemValue
   * is empty and if it has a default value.
   *
   * @param string $propertyType
   *   The type of the property.
   * @param mixed $itemValue
   *   The value of the item.
   * @param mixed $defaultValue
   *   The default value of the item.
   * @param mixed $skipZeroValue
   *   A flag indicating if an item is to be skipped
   *   if the value is empty.
   *
   * @return bool
   *  True if a $itemValue is empty and has no default value,
   *  false otherwise.
   */
  protected function valueIsEmpty(
    string $propertyType,
    mixed $itemValue,
    mixed $defaultValue,
    ?bool $skipZeroValue): bool {
    $numericTypes = ['integer', 'double', 'float'];
    if (in_array($propertyType, $numericTypes) &&
       ($itemValue === '0' && $defaultValue === NULL && $skipZeroValue)) {
      return TRUE;
    }
    if ($itemValue === '' && $defaultValue === NULL) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * The getWebformElements method.
   *
   * This method returns a webform main element
   * and a webform label element depending on the
   * property name that is passed in.
   *
   * @param string $propertyName
   *   The name of the property.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform we are extracting data from.
   *
   * @return array
   *   A webform main and label element.
   */
  protected function getWebformElements(string $propertyName, Webform $webform): array {
    $webformElement = $webform->getElement($propertyName);
    $webformMainElement = $webformElement;
    $webformLabelElement = $webformElement;

    if ($this->isAddressField($propertyName)) {
      $webformMainElement = $webform->getElement('community_address');
      $webformLabelElement = $webformMainElement['#webform_composite_elements'][$propertyName];
    }
    if ($this->isBankAccountField($propertyName)) {
      $webformMainElement = $webform->getElement('bank_account');
      $webformLabelElement = $webformMainElement['#webform_composite_elements'][$propertyName];
    }
    return ['webformMainElement' => $webformMainElement, 'webformLabelElement' => $webformLabelElement];
  }

  /**
   * The getValueArray method.
   *
   * The method populates a value array that is used
   * for the final document structure.
   *
   * @param string $elementName
   *   The name (aka ID) of the element.
   * @param mixed $itemValue
   *   The value of the element.
   * @param string $jsonType
   *   The elements json type.
   * @param mixed $label
   *   The elements label.
   * @param array $metaData
   *   Metadata related to the element.
   * @return array
   */
  protected function getValueArray(
    mixed $elementName,
    mixed $itemValue,
    string $jsonType,
    mixed $label,
    array $metaData): array {
    return [
      'ID' => $elementName,
      'value' => $itemValue,
      'valueType' => $jsonType,
      'label' => $label,
      'meta' => json_encode($metaData, JSON_UNESCAPED_UNICODE),
    ];
  }

  /**
   * The extractLabel method.
   *
   * This method extracts a label for the value array.
   * The returned value depends on the passed in item name,
   * and the items main webform element.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The definition of the item.
   * @param array $webformMainElement
   *   The items main webform element.
   * @param string $itemName
   *   The name of the item we are iterating over.
   *
   * @return string
   *   A label.
   */
  protected function extractLabel(
    DataDefinitionInterface $definition,
    array $webformMainElement,
    string $itemName): string {

    if ($itemName == 'issuerName') {
      $itemName = 'issuer_name';
    }

    if ($itemName == 'fileName') {
      return $this->t('File name', [], ['context' => 'grants_metadata']);
    }

    $label = $definition->getLabel();
    if (isset($webformMainElement['#webform_composite_elements'][$itemName]['#title'])) {
      $titleElement = $webformMainElement['#webform_composite_elements'][$itemName]['#title'];
      if (is_string($titleElement)) {
        $label = $titleElement;
      }
      else {
        $label = $titleElement->render();
      }
    }
    return $label;
  }

  /**
   * The getFieldValuesFromPropertyItem method.
   *
   * This method extracts values from a nested field
   * property for the value array.
   *
   * @param mixed $item
   *   The item we are iterating over.
   * @param array $webformMainElement
   *   The items main webform element.
   * @param mixed $defaultValue
   *   The items default value.
   * @param array $hiddenFields
   *   An array of hidden fields.
   * @param array $metaData
   *   An array of metadata related to the item.
   *
   * @return array
   *   An array constructed by getValueArray().
   */
  protected function getFieldValuesFromPropertyItem(
    DataType $item,
    array $webformMainElement,
    mixed $defaultValue,
    array $hiddenFields,
    array $metaData): array {
    $fieldValues = [];
    $propertyItem = $item->getValue();
    $itemDataDefinition = $item->getDataDefinition();
    $itemValueDefinitions = $itemDataDefinition->getPropertyDefinitions();

    foreach ($itemValueDefinitions as $itemName => $itemValueDefinition) {
      $itemTypes = $this->getJsonTypeForDataType($itemValueDefinition);
      $label = $this->extractLabel($itemValueDefinition, $webformMainElement, $itemName);

      if (isset($propertyItem[$itemName])) {
        $itemSkipEmpty = $itemValueDefinition->getSetting('skipEmptyValue');
        $propertyValueCallback = $itemValueDefinition->getSetting('valueCallback');

        $itemValue = $propertyItem[$itemName];
        $itemValue = $this->getItemValue($itemTypes, $itemValue, $defaultValue, $propertyValueCallback);

        if (empty($itemValue) && $itemSkipEmpty === TRUE) {
          continue;
        }
        $metaData['element']['label'] = $label;
        $metaData['element']['hidden'] = in_array($itemName, $hiddenFields);
        $fieldValues[] = $this->getValueArray($itemName, $itemValue, $itemTypes['jsonType'], $label, $metaData);
      }
    }
    return $fieldValues;
  }


  /**
   * The buildStructureArrayWithPropertyStructureCallback method.
   *
   * This method builds a structure array for regular fields that
   * have a property structure callback.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typedData
   *   The property we are handling.
   * @param mixed $propertyStructureCallback
   *   The property structure callback.
   * @param array $webformMainElement
   *   The items main webform element.
   * @param array $pages
   *   The pages of the webform.
   * @param array $elements
   *   The elements of the webform.
   * @param array $hiddenFields
   *   An array of hidden fields.
   *
   * @return array
   *   A structure array with encoded metadata.
   */
  protected function buildStructureArrayWithPropertyStructureCallback(
    TypedDataInterface $property,
    mixed $propertyStructureCallback,
    array $webformMainElement,
    array $pages,
    array $elements,
    array $hiddenFields
  ): array {
    $structureArray = self::getFieldValuesFromFullItemCallback(
      $propertyStructureCallback,
      $property
    );
    $pageKeys = array_keys($pages);
    $elementKeys = array_keys($elements);
    $elementWeight = 0;

    foreach ($structureArray["compensation"] as $propertyArrayKey => $propertyArray) {
      foreach ($propertyArray as $propertyKey => $property) {
        if (!isset($property['ID'])) {
          continue;
        }

        $name = $property['ID'];
        $pageId = $webformMainElement['#webform_parents'][0];
        $pageLabel = $pages[$pageId]['#title'];
        $pageNumber = array_search($pageId, $pageKeys) + 1;

        $sectionId = $webformMainElement['#webform_parents'][1];
        $sectionLabel = $elements[$sectionId]['#title'];
        $sectionWeight = array_search($sectionId, $elementKeys);

        $hidden = in_array($name, $hiddenFields);
        $label = $property['label'];

        if (isset($webformMainElement['#webform_composite_elements'][$name]['#title'])) {
          $titleElement = $webformMainElement['#webform_composite_elements'][$name]['#title'];
          if (is_string($titleElement)) {
            $label = $titleElement;
          }
          else {
            $label = $titleElement->render();
          }
        }
        $page = [
          'id' => $pageId,
          'label' => $pageLabel,
          'number' => $pageNumber,
        ];
        $section = [
          'id' => $sectionId,
          'label' => $sectionLabel,
          'weight' => $sectionWeight,
        ];
        $element = [
          'label' => $label,
          'weight' => $elementWeight,
          'hidden' => $hidden,
        ];
        $elementWeight++;
        $metaData = self::getMetaData($page, $section, $element);
        $jsonEncodedMetaData = json_encode($metaData, JSON_UNESCAPED_UNICODE);
        $structureArray["compensation"][$propertyArrayKey][$propertyKey]['meta'] = $jsonEncodedMetaData;
      }
    }
    return $structureArray;
  }

  /**
   * The extractMetadataFromWebform method.
   *
   * This method extracts metadata from a webforms
   * main and label element.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typedData
   *   The property we are handling.
   * @param string $propertyName
   *   The name of the property.
   * @param array $webformMainElement
   *   The items main webform element.
   * @param array $webformLabelElement
   *   The items webform label element.
   * @param array $pages
   *   The pages of the webform.
   * @param array $elements
   *   The elements of the webform.
   *
   * @return array[]
   *   An associative array of webform metadata.
   */
  protected function extractMetadataFromWebform(
    TypedDataInterface $property,
    string $propertyName,
    array $webformMainElement,
    array $webformLabelElement,
    array $pages,
    array $elements
  ): array {
    $pageKeys = array_keys($pages);
    $elementKeys = array_keys($elements);

    $pageId = $webformMainElement['#webform_parents'][0];
    $pageLabel = $pages[$pageId]['#title'];
    $pageNumber = array_search($pageId, $pageKeys) + 1;

    $sectionId = $webformMainElement['#webform_parents'][1];
    $sectionLabel = $elements[$sectionId]['#title'];
    $sectionWeight = array_search($sectionId, $elementKeys);

    $hidden = $this->isFieldHidden($property);
    $label = $webformLabelElement['#title'];
    $weight = array_search($propertyName, $elementKeys);

    $fieldsetId = $webformMainElement['#webform_parents'][2] ?? NULL;
    $fieldSetLabel = '';
    if ($fieldsetId && $elements[$fieldsetId]['#type'] === 'fieldset') {
      $fieldSetLabel = $elements[$fieldsetId]['#title'] . ': ';
    }

    $page = [
      'id' => $pageId,
      'label' => $pageLabel,
      'number' => $pageNumber,
    ];
    $section = [
      'id' => $sectionId,
      'label' => $sectionLabel,
      'weight' => $sectionWeight,
    ];
    $element = [
      'label' => $fieldSetLabel . $label,
      'weight' => $weight,
      'hidden' => $hidden,
    ];
    return ['page' => $page, 'section' => $section, 'element' => $element];
  }

  /**
   * The writeJsonFile method.
   *
   * This method writes a json file of the final
   * document structure in typedDataToDocumentContentWithWebform.
   *
   * @param $documentStructure
   *   The whole document structure.
   * @param $webformId
   *   The webform ID.
   */
  protected function writeJsonFile($documentStructure, $webformId): void {
    $jsonString = json_encode($documentStructure);
    $filePath = $webformId . '-data.json';
    $file = fopen($filePath, 'w');

    if ($file) {
      fwrite($file, $jsonString);
      fclose($file);
    }
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

    $elements = $webform->getElementsDecodedAndFlattened();
    $documentStructure = [];

    foreach ($typedData as $property) {

      $propertyName = $property->getName();
      $propertyName = $this->modifyPropertyName($propertyName, true);

      $definition = $property->getDataDefinition();
      $addConditionallyConfig = $definition->getSetting('addConditionally');
      $skipZeroValue = $definition->getSetting('skipZeroValue');
      $jsonPath = $definition->getSetting('jsonPath');
      $requiredInJson = $definition->getSetting('requiredInJson');
      $defaultValue = $definition->getSetting('defaultValue');
      $valueCallback = $definition->getSetting('valueCallback');
      $fullItemValueCallback = $definition->getSetting('fullItemValueCallback');
      $propertyStructureCallback = $definition->getSetting('propertyStructureCallback');
      $hiddenFields = $definition->getSetting('hiddenFields') ?? [];

      $propertyType = $definition->getDataType();
      $numberOfItems = count($jsonPath);
      $elementName = array_pop($jsonPath);
      $baseIndex = count($jsonPath);

      $schema = $this->getPropertySchema($elementName, $this->structure);
      $value = self::sanitizeInput($property->getValue());
      $itemTypes = self::getJsonTypeForDataType($definition);
      $itemValue = self::getItemValue($itemTypes, $value, $defaultValue, $valueCallback);

      if ($addConditionallyConfig &&
          !$this->getConditionStatus($addConditionallyConfig, $submittedFormData, $definition)) {
        continue;
      }

      if ($jsonPath === NULL &&  $this->isRegularField($propertyName, $webform)) {
        continue;
      }

      $propertyStructureCallback = $this->modifyCallback($propertyStructureCallback, $webform, $submittedFormData);
      $fullItemValueCallback = $this->modifyCallback($fullItemValueCallback, $webform, $submittedFormData);

      if ($this->isRegularField($propertyName, $webform)) {

        $webformElements = $this->getWebformElements($propertyName, $webform);
        $webformMainElement = $webformElements['webformMainElement'];
        $webformLabelElement = $webformElements['webformLabelElement'];
        $propertyName = $this->modifyPropertyName($propertyName);

        if ($propertyStructureCallback) {
          $documentStructure = array_merge_recursive(
            $documentStructure,
            $this->buildStructureArrayWithPropertyStructureCallback(
              $property,
              $propertyStructureCallback,
              $webformMainElement,
              $pages,
              $elements,
              $hiddenFields
            )
          );
          continue;
        }

        $metaData = $this->extractMetadataFromWebform(
          $property,
          $propertyName,
          $webformMainElement,
          $webformLabelElement,
          $pages,
          $elements
        );

        $label = $webformLabelElement['#title'];
        $page = $metaData['page'];
        $section = $metaData['section'];
        $element = $metaData['element'];
        $metaData = self::getMetaData($page, $section, $element);
      }
      else {
        $label = $definition->getLabel();
        $metaData = [];

        if ($propertyStructureCallback) {
          $documentStructure = array_merge_recursive(
            $documentStructure,
            self::getFieldValuesFromFullItemCallback(
              $propertyStructureCallback,
              $property,
            )
          );
          continue;
        }
      }

      if ($this->isAttachmentField($propertyName)) {
        $webformMainElement = [];
        $webformMainElement['#webform_composite_elements'] = GrantsAttachmentsElement::getCompositeElements([]);
      }

      if ($this->valueIsEmpty($propertyType, $itemValue, $defaultValue, $skipZeroValue)) {
        continue;
      }

      if (isset($webformLabelElement['#options'][$itemValue])) {
        $valueTranslation = $webformLabelElement['#options'][$itemValue];
        if ($valueTranslation) {
          $metaData['element']['valueTranslation'] = $valueTranslation;
        }
      }

      // Build a reference to the document structure.
      $reference = &$documentStructure;
      foreach ($jsonPath as $index => $path) {
        $reference = &$reference[$path];
      }

      switch ($numberOfItems) {
        case 5:
        case 4:
        case 3:
          if (is_array($itemValue) && self::numericKeys($itemValue)) {
            if ($fullItemValueCallback) {
              $fieldValues = self::getFieldValuesFromFullItemCallback($fullItemValueCallback, $property);
              if ($fieldValues || $requiredInJson) {
                $reference[$elementName] = $fieldValues;
              }
              break;
            }
            if (empty($itemValue) && $requiredInJson) {
              $reference[$elementName] = $itemValue;
              break;
            }
            foreach ($property as $itemIndex => $item) {
              $reference[$elementName][$itemIndex] = $this->getFieldValuesFromPropertyItem(
                $item,
                $webformMainElement,
                $defaultValue,
                $hiddenFields,
                $metaData);
            }
          } else {
            $reference[] = $this->getValueArray($elementName, $itemValue, $itemTypes['jsonType'], $label, $metaData);
          }
          break;

        case 2:
          $metaData = self::getMetaData($page, $section, $element);
          if (is_array($itemValue) && self::numericKeys($itemValue)) {
            if ($propertyType == 'list') {
              foreach ($property as $itemIndex => $item) {
                $reference[$elementName][$itemIndex] = $this->getFieldValuesFromPropertyItem(
                  $item,
                  $webformMainElement,
                  $defaultValue,
                  $hiddenFields,
                  $metaData);
              }
            }
          }
          else {
            $valueArray = $this->getValueArray($elementName, $itemValue, $itemTypes['jsonType'], $label, $metaData);
            if ($schema['type'] == 'string') {
              $documentStructure[$jsonPath[$baseIndex - 1]][$elementName] = $itemValue;
            }
            else {
              $documentStructure[$jsonPath[$baseIndex - 1]][] = $valueArray;
            }
          }

          break;

        case 1:
          if ($propertyName == 'form_update') {
            if ($itemValue === 'true') {
              $reference[$elementName] = TRUE;
            }
            else {
              $reference[$elementName] = FALSE;
            }
          }
          else {
            $reference[$elementName] = $itemValue;
          }
          break;

        default:
          $this->logger->error('@field failed parsing, check setup.', ['@field' => $elementName]);
          break;
      }
    }
    if (!array_key_exists('attachmentsInfo', $documentStructure)) {
      $documentStructure['attachmentsInfo'] = [];
    }

    if (empty($documentStructure['attachmentsInfo'])) {
      $documentStructure['attachmentsInfo']['attachmentsArray'] = [];
    }
    $this->writeJsonFile($documentStructure, $webform->id());
    return $documentStructure;
  }

  /**
   * Check if the given field should be hidden from end users.
   *
   * @param Drupal\Core\TypedData\TypedDataInterface $property
   *   Field to check.
   *
   * @return bool
   *   Should the field be hidden
   */
  public static function isFieldHidden($property) {
    $definition = $property->getDataDefinition();
    $propertyName = $property->getName();
    $hide = $definition->getSetting('hidden') || FALSE;
    if ($hide) {
      return TRUE;
    }
    $parent = $property->getParent();
    if (!$parent) {
      return FALSE;
    }
    $parentDefinition = $parent->getDataDefinition();
    $hiddenFields = $definition->getSetting('hiddenFields');
    if (is_array($hiddenFields) && in_array($propertyName, $hiddenFields)) {
      return TRUE;
    }
    return FALSE;
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
    return $metaData;

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
   * Get value from document content for given element / path.
   *
   * @param array $content
   *   Decoded JSON content for document.
   * @param array $pathArray
   *   Path in JSONn document. From definition settings.
   * @param string $elementName
   *   ELement name in JSON.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface|null $definition
   *   Data definition setup.
   *
   * @return mixed
   *   Parsed typed data structure.
   */
  protected function getValueFromDocument(
    array $content,
    array $pathArray,
    string $elementName,
    ?DataDefinitionInterface $definition
  ): mixed {
    // Get new key to me evalued.
    $newKey = array_shift($pathArray);

    $type = $definition->getDataType();

    // If key exist in content array.
    if (array_key_exists($newKey, $content)) {
      // Get content for key.
      $newContent = $content[$newKey];
      // And since we're not in root element, call self
      // to drill down to desired element.
      return $this->getValueFromDocument($newContent, $pathArray, $elementName, $definition);
    }
    // If we are at the root of content, and the given element exists.
    elseif (array_key_exists($elementName, $content)) {

      $thisElement = $content[$elementName];

      $itemPropertyDefinitions = NULL;
      // Check if we have child definitions, ie itemDefinitions.
      if (method_exists($definition, 'getItemDefinition')) {
        /** @var \Drupal\Core\TypedData\ComplexDataDefinitionBase $id */
        $itemDefinition = $definition->getItemDefinition();
        if ($itemDefinition !== NULL) {
          $itemPropertyDefinitions = $itemDefinition->getPropertyDefinitions();
        }
      }
      // Check if we have child definitions, ie itemDefinitions.
      if (method_exists($definition, 'getPropertyDefinitions')) {
        $itemPropertyDefinitions = $definition->getPropertyDefinitions();
      }

      // If element is array.
      if (is_array($thisElement)) {
        $retval = [];
        // We need to loop values and structure data in array as well.
        foreach ($content[$elementName] as $key => $value) {
          foreach ($value as $key2 => $v) {
            $itemValue = NULL;
            if (is_array($v)) {

              // If we have definitions for given property.
              if (isset($v['ID']) && is_array($itemPropertyDefinitions) && isset($itemPropertyDefinitions[$v['ID']])) {
                $itemPropertyDefinition = $itemPropertyDefinitions[$v['ID']];
                // Get value extracter.
                $valueExtracterConfig = $itemPropertyDefinition->getSetting('webformValueExtracter');
                if ($valueExtracterConfig) {
                  $valueExtracterService = \Drupal::service($valueExtracterConfig['service']);
                  $method = $valueExtracterConfig['method'];
                  // And try to get value from there.
                  $itemValue = $valueExtracterService->$method($v);
                }
              }

              if (array_key_exists('value', $v)) {
                $retval[$key][$v['ID']] = $itemValue ?? $v['value'];
              }
              else {
                $retval[$key][$key2] = $itemValue ?? $v;
              }
            }
            else {
              // If we have definitions for given property.
              if (is_array($itemPropertyDefinitions) && isset($itemPropertyDefinitions[$key2])) {
                $itemPropertyDefinition = $itemPropertyDefinitions[$key2];
                // Get value extracter.
                $valueExtracterConfig = $itemPropertyDefinition->getSetting('webformValueExtracter');
                if ($valueExtracterConfig) {
                  $valueExtracterService = \Drupal::service($valueExtracterConfig['service']);
                  $method = $valueExtracterConfig['method'];
                  // And try to get value from there.
                  $itemValue = $valueExtracterService->$method($v);
                }
              }

              $retval[$key][$key2] = $itemValue ?? $v;
            }
          }
        }
        return $retval;
      }
      // If element is not array.
      else {
        // Return value as is.
        return $content[$elementName];
      }
    }
    // If keys are numeric, we know that we need to decode the last
    // item with id's / names in array.
    elseif (self::numericKeys($content)) {
      // Loop content.
      foreach ($content as $value) {
        // If content is not array, it means that content is returnable as is.
        if (!is_array($value)) {
          return $value;
        }

        // If value is an array, then we need to return desired element value.
        if ($value['ID'] == $elementName) {
          // Make sure that the element value is a string.
          $parseValue = is_string($value['value']) ? $value['value'] : '';
          $retval = htmlspecialchars_decode($parseValue);

          if ($type == 'boolean') {
            if ($retval == 'true') {
              $retval = '1';
            }
            elseif ($retval == 'false') {
              $retval = '0';
            }
            else {
              $retval = '0';
            }
          }

          return $retval;
        }
      }
    }
    else {
      // If no item is specified with given name.
      return NULL;
    }
    // shouldn't get here that often.
    return NULL;
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
      if ($itemValue === FALSE) {
        $itemValue = 'false';
      }
      if ($itemValue === '0') {
        $itemValue = 'false';
      }
      if ($itemValue === 0) {
        $itemValue = 'false';
      }
      if ($itemValue === TRUE) {
        $itemValue = 'true';
      }
      if ($itemValue === '1') {
        $itemValue = 'true';
      }
      if ($itemValue === 1) {
        $itemValue = 'true';
      }
      if ($itemValue == 'Yes') {
        $itemValue = 'true';
      }
      if ($itemValue == 'No') {
        $itemValue = 'false';
      }
    }

    if ($itemTypes['jsonType'] == 'int') {
      $itemValue = str_replace('_', '', $itemValue);
    }

    return $itemValue;
  }

  /**
   * Get field values from full item callback.
   *
   * @param array $fullItemValueCallback
   *   Callback config.
   * @param \Drupal\Core\TypedData\TypedDataInterface $property
   *   Property.
   *
   * @return array
   *   Full item callback array.
   */
  public static function getFieldValuesFromFullItemCallback(
    array $fullItemValueCallback,
    TypedDataInterface $property
  ): mixed {
    $fieldValues = [];
    if (isset($fullItemValueCallback['service'])) {
      $fullItemValueService = \Drupal::service($fullItemValueCallback['service']);
      $funcName = $fullItemValueCallback['method'];
      $fieldValues = $fullItemValueService->$funcName($property, $fullItemValueCallback['arguments'] ?? []);
    }
    else {
      if (isset($fullItemValueCallback['class'])) {
        $funcName = $fullItemValueCallback['method'];
        $fieldValues = $fullItemValueCallback['class']::$funcName(
          $property,
          $fullItemValueCallback['arguments'] ?? []
        );
      }
    }
    return $fieldValues;
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
      $fullItemValueService = \Drupal::service($fullItemValueCallback['service']);
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
   * Runs the checks to see if the element should be added to ATV Document.
   *
   * @param array $conditionArray
   *   Condition config.
   * @param array $content
   *   Content.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   Definition.
   *
   * @return bool
   *   Can the property be added to ATV Document.
   */
  public function getConditionStatus(
    array $conditionArray,
    array $content,
    DataDefinitionInterface $definition,
    ): bool {

    if (isset($conditionArray['service'])) {
      $conditionService = \Drupal::service($conditionArray['service']);
      $funcName = $conditionArray['method'];
      return $conditionService->$funcName($definition, $content);
    }
    else {
      if (isset($conditionArray['class'])) {
        $funcName = $conditionArray['method'];
        return $conditionArray['class']::$funcName($definition, $content);
      }
    }

    return TRUE;
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
   *   Assocative arrow with the results if they are found.
   */
  public static function extractDataForWebForm(array $content, array $keys): array {
    $values = [];
    if (!isset($content['compensation'])) {
      return $values;
    }

    foreach ($content['compensation'] as $key => $item) {
      if (is_numeric($key)) {
        if (in_array($item['ID'], $keys) && !in_array($item['ID'], $values)) {
          $values[$item['ID']] = $item['value'];
        }
      }
      else {
        if (!is_array($item)) {
          $values[$key] = $item;
          continue;
        }
        foreach ($item as $key2 => $item2) {
          if (!is_array($item2)) {
            $values[$key2] = $item2;
          }
          elseif (AtvSchema::numericKeys($item2)) {
            foreach ($item2 as $key3 => $item3) {
              if (AtvSchema::numericKeys($item3)) {
                foreach ($item3 as $item4) {
                  if (in_array($item4['ID'], $keys) && !array_key_exists($item4['ID'], $values)) {
                    $values[$item4['ID']] = $item4['value'];
                  }
                }
              }
              else {
                if (isset($item3['ID']) && in_array($item3['ID'], $keys) && !array_key_exists($item3['ID'], $values)) {
                  $values[$item3['ID']] = $item3['value'];
                }
              }
            }
          }
          else {
            if (is_numeric($key2)) {
              if (in_array($item2['ID'], $keys) && !in_array($item2['ID'], $values)) {
                $values[$item2['ID']] = $item2['value'];
              }
            }
          }
        }
      }
    }
    return $values;
  }

  /**
   * Extracts data from ATV document compensation field.
   *
   * @param Drupal\Core\TypedData\DataDefinitionInterface $definition
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
    $value = $this->getValueFromDocument($content, $pathArray, $elementName, $definition);
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

}
