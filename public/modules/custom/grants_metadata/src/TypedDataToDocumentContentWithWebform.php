<?php

namespace Drupal\grants_metadata;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\grants_attachments\Element\GrantsAttachments as GrantsAttachmentsElement;
use Drupal\webform\Entity\Webform;

/**
 * Provides a TypedDataToDocumentContentWithWebform class.
 *
 * This class is used for converting webform data
 * to document content data.
 */
class TypedDataToDocumentContentWithWebform {

  /**
   * The getTypedDataToDocumentContentWithWebform method.
   *
   * This is the main method of this class. The method is used
   * for converting webform data to document content data.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $typedData
   *   The typed data that we are converting.
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform the typed data is coming form.
   * @param array $pages
   *   Page structure of the webform.
   * @param array $submittedFormData
   *   The submitted data from the webform.
   * @param array $structure
   *   The schema structure.
   *
   * @return array
   *   Document structure based on schema.
   */
  public static function getTypedDataToDocumentContentWithWebform(
    TypedDataInterface $typedData,
    Webform $webform,
    array $pages,
    array $submittedFormData,
    array $structure): array {

    $elements = $webform->getElementsDecodedAndFlattened();
    $documentStructure = [];

    foreach ($typedData as $property) {

      // Get the property name and modify it the first time.
      $propertyName = $property->getName();
      $propertyName = self::modifyPropertyName($propertyName, TRUE);

      // Load various settings.
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

      // Load the item value.
      $value = AtvSchema::sanitizeInput($property->getValue());
      $itemTypes = AtvSchema::getJsonTypeForDataType($definition);
      $itemValue = AtvSchema::getItemValue($itemTypes, $value, $defaultValue, $valueCallback);

      // Reset these variables to avoid unexpected behaviour inside loop.
      $webformMainElement = [];
      $webformLabelElement = [];

      // Check for additional configurations.
      if ($addConditionallyConfig &&
          !self::getConditionStatus($addConditionallyConfig, $submittedFormData, $definition)) {
        continue;
      }

      // Check that the json path is not null if we have a regular field.
      if ($jsonPath === NULL && self::isRegularField($propertyName, $webform)) {
        continue;
      }

      // Check if the callbacks need to be modified.
      $propertyStructureCallback = self::modifyCallback($propertyStructureCallback, $webform, $submittedFormData);
      $fullItemValueCallback = self::modifyCallback($fullItemValueCallback, $webform, $submittedFormData);

      // Handle regular fields.
      if (self::isRegularField($propertyName, $webform)) {
        $webformElements = self::getWebformElements($propertyName, $webform);
        $webformMainElement = $webformElements['webformMainElement'];
        $webformLabelElement = $webformElements['webformLabelElement'];
        $propertyName = self::modifyPropertyName($propertyName);

        // Handle regular fields with a property structure callback.
        if ($propertyStructureCallback) {
          $documentStructure = array_merge_recursive(
            $documentStructure,
            self::buildStructureArrayWithPropertyStructureCallback(
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

        // Load metadata. This should not be done here since
        // it is causing weird behaviours. This metadata can be
        // loaded here on iteration X of the loop, and the used
        // further down in the method on iteration Y of the loop.
        $extractedMetaData = self::extractMetadataFromWebform(
          $property,
          $propertyName,
          $webformMainElement,
          $webformLabelElement,
          $pages,
          $elements
        );

        $label = $webformLabelElement['#title'];
        $page = $extractedMetaData['page'];
        $section = $extractedMetaData['section'];
        $element = $extractedMetaData['element'];

        // InputmaskHandler::addInputmaskToMetadata(
        // $element,
        // $webformMainElement ?? [],
        // );.
        $metaData = AtvSchema::getMetaData($page, $section, $element);
      }
      // Handle other types of fields.
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

      // Special case for attachment fields.
      if (self::isAttachmentField($propertyName)) {
        $webformMainElement = [];
        $webformMainElement['#webform_composite_elements'] = GrantsAttachmentsElement::getCompositeElements([]);
      }

      // Add value translations.
      if (isset($webformLabelElement['#options'][$itemValue])) {
        $valueTranslation = $webformLabelElement['#options'][$itemValue];
        if ($valueTranslation) {
          $metaData['element']['valueTranslation'] = $valueTranslation;
        }
      }

      // Gather settings for parsing the json structure.
      $propertyType = $definition->getDataType();
      $numberOfItems = count($jsonPath);
      $elementName = array_pop($jsonPath);
      $baseIndex = count($jsonPath);
      $schema = PropertySchema::getPropertySchema($elementName, $structure);

      // Continue if the value is empty.
      if (self::valueIsEmpty($propertyType, $itemValue, $defaultValue, $skipZeroValue)) {
        continue;
      }

      // Continue of the json structure is too long.
      if ($numberOfItems > 5) {
        \Drupal::logger('grants_metadata')
          ->error('@field failed parsing, check setup.', ['@field' => $elementName]);
        continue;
      }

      // Build a reference to the document structure.
      $reference = &$documentStructure;
      foreach ($jsonPath as $path) {
        if (!isset($reference[$path]) || !is_array($reference[$path])) {
          $reference[$path] = [];
        }
        $reference = &$reference[$path];
      }

      // Handle a json structure of the size 5-3.
      if ($numberOfItems >= 3) {
        if (is_array($itemValue) && AtvSchema::numericKeys($itemValue)) {
          if ($fullItemValueCallback) {
            self::handleFullItemValueCallback($reference, $elementName, $fullItemValueCallback, $property, $requiredInJson);
            self::handlePossibleEmptyArray($documentStructure, $reference, $jsonPath);
            continue;
          }
          if (empty($itemValue) && $requiredInJson) {
            $reference[$elementName] = $itemValue;
            continue;
          }
          self::handlePropertyItems($reference, $elementName, $property, $webformMainElement, $defaultValue, $hiddenFields, $metaData);
          self::handlePossibleEmptyArray($documentStructure, $reference, $jsonPath);
          continue;
        }
        $valueArray = self::getValueArray($elementName, $itemValue, $itemTypes['jsonType'], $label, $metaData);
        $reference[] = $valueArray;
        continue;
      }

      // Handle a json structure of the size 2.
      if ($numberOfItems == 2) {
        $metaData = AtvSchema::getMetaData($page, $section, $element);
        if (is_array($itemValue) && AtvSchema::numericKeys($itemValue) && $propertyType == 'list') {
          self::handlePropertyItems($reference, $elementName, $property, $webformMainElement, $defaultValue, $hiddenFields, $metaData);
          self::handlePossibleEmptyArray($documentStructure, $reference, $jsonPath);
          continue;
        }
        if ($schema['type'] == 'string') {
          $documentStructure[$jsonPath[$baseIndex - 1]][$elementName] = $itemValue;
          continue;
        }
        $valueArray = self::getValueArray($elementName, $itemValue, $itemTypes['jsonType'], $label, $metaData);
        $documentStructure[$jsonPath[$baseIndex - 1]][] = $valueArray;
        continue;
      }

      // Handle a json structure of the size 1.
      if ($numberOfItems == 1) {
        if ($propertyName == 'form_update') {
          $reference[$elementName] = $itemValue === 'true';
          continue;
        }
        $reference[$elementName] = $itemValue;
      }
    }

    // Handle cases when no attachments info has been added.
    if (!array_key_exists('attachmentsInfo', $documentStructure)) {
      $documentStructure['attachmentsInfo'] = [];
    }

    if (empty($documentStructure['attachmentsInfo'])) {
      $documentStructure['attachmentsInfo']['attachmentsArray'] = [];
    }

    // Optionally writ the data to a .json file. Used for testing.
    return $documentStructure;
  }

  /**
   * The handlePropertyItems method.
   *
   * This method loops through the properties,
   * calls getFieldValuesFromPropertyItem(), and
   * adds them to the document structure.
   *
   * @param array $reference
   *   A document structure reference.
   * @param string $elementName
   *   The name (aka ID) of the element.
   * @param \Drupal\Core\TypedData\TypedDataInterface $property
   *   The property.
   * @param array $webformMainElement
   *   The items main webform element.
   * @param mixed $defaultValue
   *   The items default value.
   * @param array $hiddenFields
   *   An array of hidden fields.
   * @param array $metaData
   *   An array of metadata related to the item.
   */
  protected static function handlePropertyItems(
    array &$reference,
    string $elementName,
    TypedDataInterface $property,
    array $webformMainElement,
    mixed $defaultValue,
    array $hiddenFields,
    array $metaData): void {
    foreach ($property as $itemIndex => $item) {
      $reference[$elementName][$itemIndex] = self::getFieldValuesFromPropertyItem(
        $item,
        $webformMainElement,
        $defaultValue,
        $hiddenFields,
        $metaData
      );
    }
  }

  /**
   * The handleFullItemValueCallback method.
   *
   * This method handles items with a full item value callback
   * by calling getFieldValuesFromFullItemCallback(). If values
   * are returned or $requiredInJson is set to true,
   * the passed in reference is altered.
   *
   * @param array $reference
   *   A document structure reference.
   * @param string $elementName
   *   The name (aka ID) of the element.
   * @param array $fullItemValueCallback
   *   The callback.
   * @param \Drupal\Core\TypedData\TypedDataInterface $property
   *   The property.
   * @param mixed $requiredInJson
   *   A flag indicating if the value is required in the json structure.
   */
  protected static function handleFullItemValueCallback(
    array &$reference,
    string $elementName,
    array $fullItemValueCallback,
    TypedDataInterface $property,
    mixed $requiredInJson): void {
    $fieldValues = self::getFieldValuesFromFullItemCallback($fullItemValueCallback, $property);
    if ($fieldValues || $requiredInJson) {
      $reference[$elementName] = $fieldValues;
    }
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
  protected static function modifyCallback(?array $callback, Webform $webform, array $submittedFormData): ?array {
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
  protected static function modifyPropertyName(string $propertyName, ?bool $strictTarget = FALSE): string {
    if ($strictTarget) {
      if ($propertyName === 'account_number') {
        $propertyName = 'bank_account';
      }
      return $propertyName;
    }
    if (self::isAddressField($propertyName)) {
      $propertyName = 'community_address';
    }
    if (self::isBankAccountField($propertyName)) {
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
  protected static function isAddressField(string $propertyName): bool {
    return $propertyName === 'community_street' ||
           $propertyName === 'community_city' ||
           $propertyName === 'community_post_code' ||
           $propertyName === 'community_country';
  }

  /**
   * The isBankAccountField method.
   *
   * This method checks if $propertyName
   * can be considered as a "bank account" field.
   *
   * @param string $propertyName
   *   The name of the property.
   *
   * @return bool
   *   True if the property name is a bank account field,
   *   false otherwise.
   */
  protected static function isBankAccountField(string $propertyName): bool {
    return $propertyName === 'account_number_owner_name' ||
           $propertyName === 'account_number_ssn';
  }

  /**
   * The isBudgetField method.
   *
   * This method checks if $propertyName
   * can be considered as a "budget" field.
   *
   * @param string $propertyName
   *   The name of the property.
   *
   * @return bool
   *   True if the property name is a budget field,
   *   false otherwise.
   */
  protected static function isBudgetField(string $propertyName): bool {
    return $propertyName === 'budgetInfo';
  }

  /**
   * The isBudgetField method.
   *
   * This method checks if $propertyName
   * can be considered as an "attachment" field.
   *
   * @param string $propertyName
   *   The name of the property.
   *
   * @return bool
   *   True if the property name an attachment field,
   *   false otherwise.
   */
  protected static function isAttachmentField(string $propertyName): bool {
    return $propertyName === 'attachments';
  }

  /**
   * The isRegularField method.
   *
   * This method checks if $propertyName
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
  protected static function isRegularField(string $propertyName, Webform $webform): bool {
    $webformElement = $webform->getElement($propertyName);
    return $propertyName !== 'form_update' &&
           $propertyName !== 'messages' &&
           $propertyName !== 'status_updates' &&
           $propertyName !== 'events' &&
           ($webformElement !== NULL ||
            self::isAddressField($propertyName) ||
            self::isBankAccountField($propertyName) ||
            self::isBudgetField($propertyName));
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
   *   True if a $itemValue is empty and has no default value,
   *   false otherwise.
   */
  protected static function valueIsEmpty(
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
  protected static function getWebformElements(string $propertyName, Webform $webform): array {
    $webformElement = $webform->getElement($propertyName);
    $webformMainElement = $webformElement;
    $webformLabelElement = $webformElement;

    if (self::isAddressField($propertyName)) {
      $webformMainElement = $webform->getElement('community_address');
      $webformLabelElement = $webformMainElement['#webform_composite_elements'][$propertyName];
    }
    if (self::isBankAccountField($propertyName)) {
      $webformMainElement = $webform->getElement('bank_account');
      $webformLabelElement = $webformMainElement['#webform_composite_elements'][$propertyName];
    }
    return [
      'webformMainElement' => $webformMainElement,
      'webformLabelElement' => $webformLabelElement,
    ];
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
   *
   * @return array
   *   The value array.
   */
  protected static function getValueArray(
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
   * @return string|null
   *   A label.
   */
  protected static function extractLabel(
    DataDefinitionInterface $definition,
    array $webformMainElement,
    string $itemName): string|null {

    if ($itemName == 'issuerName') {
      $itemName = 'issuer_name';
    }

    if ($itemName == 'fileName') {
      return t('File name', [], ['context' => 'grants_metadata']);
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
  protected static function getFieldValuesFromPropertyItem(
    mixed $item,
    array $webformMainElement,
    mixed $defaultValue,
    array $hiddenFields,
    array $metaData): array {
    $fieldValues = [];
    $propertyItem = $item->getValue();
    $itemDataDefinition = $item->getDataDefinition();
    $itemValueDefinitions = $itemDataDefinition->getPropertyDefinitions();

    foreach ($itemValueDefinitions as $itemName => $itemValueDefinition) {
      $itemTypes = AtvSchema::getJsonTypeForDataType($itemValueDefinition);
      $label = self::extractLabel($itemValueDefinition, $webformMainElement, $itemName);

      if (isset($propertyItem[$itemName])) {
        $itemSkipEmpty = $itemValueDefinition->getSetting('skipEmptyValue');
        $propertyValueCallback = $itemValueDefinition->getSetting('valueCallback');

        $itemValue = $propertyItem[$itemName];
        $itemValue = AtvSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $propertyValueCallback);

        if (empty($itemValue) && $itemSkipEmpty === TRUE) {
          continue;
        }

        $metaData['element']['label'] = $label;
        $metaData['element']['hidden'] = in_array($itemName, $hiddenFields);

        // InputmaskHandler::addInputmaskToMetadata(
        // $metaData['element'],
        // $webformMainElement['#webform_composite_elements'][$itemName] ?? [],
        // );.
        $fieldValues[] = self::getValueArray($itemName, $itemValue, $itemTypes['jsonType'], $label, $metaData);
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
   * @param \Drupal\Core\TypedData\TypedDataInterface $property
   *   The property we are handling.
   * @param mixed $propertyStructureCallback
   *   The property structure callback.
   * @param mixed $webformMainElement
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
  protected static function buildStructureArrayWithPropertyStructureCallback(
    TypedDataInterface $property,
    mixed $propertyStructureCallback,
    mixed $webformMainElement,
    array $pages,
    array $elements,
    array $hiddenFields): array {
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
        $metaData = AtvSchema::getMetaData($page, $section, $element);
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
   * @param \Drupal\Core\TypedData\TypedDataInterface $property
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
  protected static function extractMetadataFromWebform(
    TypedDataInterface $property,
    string $propertyName,
    array $webformMainElement,
    array $webformLabelElement,
    array $pages,
    array $elements): array {
    $pageKeys = array_keys($pages);
    $elementKeys = array_keys($elements);

    $pageId = $webformMainElement['#webform_parents'][0];
    $pageLabel = $pages[$pageId]['#title'];
    $pageNumber = array_search($pageId, $pageKeys) + 1;

    $sectionId = $webformMainElement['#webform_parents'][1];
    $sectionLabel = $elements[$sectionId]['#title'];
    $sectionWeight = array_search($sectionId, $elementKeys);

    $hidden = self::isFieldHidden($property);
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
  protected static function getConditionStatus(
    array $conditionArray,
    array $content,
    DataDefinitionInterface $definition): bool {

    if (isset($conditionArray['service'])) {
      $conditionService = \Drupal::service($conditionArray['service']);
      $funcName = $conditionArray['method'];
      return $conditionService->$funcName($definition, $content);
    }

    if (isset($conditionArray['class'])) {
      $funcName = $conditionArray['method'];
      return $conditionArray['class']::$funcName($definition, $content);
    }

    return TRUE;
  }

  /**
   * Check if the given field should be hidden from end users.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $property
   *   Field to check.
   *
   * @return bool
   *   Should the field be hidden
   */
  protected static function isFieldHidden(TypedDataInterface $property): bool {
    $definition = $property->getDataDefinition();
    $propertyName = $property->getName();
    $hide = $definition->getSetting('hidden');
    if ($hide) {
      return TRUE;
    }
    $parent = $property->getParent();
    if (!$parent) {
      return FALSE;
    }
    $hiddenFields = $definition->getSetting('hiddenFields');
    if (is_array($hiddenFields) && in_array($propertyName, $hiddenFields)) {
      return TRUE;
    }
    return FALSE;
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
  protected static function getFieldValuesFromFullItemCallback(
    array $fullItemValueCallback,
    TypedDataInterface $property): mixed {
    $fieldValues = [];
    if (isset($fullItemValueCallback['service'])) {
      $fullItemValueService = \Drupal::service($fullItemValueCallback['service']);
      $funcName = $fullItemValueCallback['method'];
      $fieldValues = $fullItemValueService->$funcName($property, $fullItemValueCallback['arguments'] ?? []);
    }
    if (isset($fullItemValueCallback['class'])) {
      $funcName = $fullItemValueCallback['method'];
      $fieldValues = $fullItemValueCallback['class']::$funcName(
        $property,
        $fullItemValueCallback['arguments'] ?? []
      );
    }
    return $fieldValues;
  }

  /**
   * The writeJsonFile method.
   *
   * This method writes a json file of the final
   * document structure in typedDataToDocumentContentWithWebform.
   *
   * @param array $documentStructure
   *   The whole document structure.
   * @param string $webformId
   *   The webform ID.
   */
  protected static function writeJsonFile(array $documentStructure, string $webformId): void {
    $jsonString = json_encode($documentStructure);
    $time = time();
    $filePath = $time . '-' . $webformId . '-data.json';
    $file = fopen($filePath, 'w');

    if ($file) {
      fwrite($file, $jsonString);
      fclose($file);
    }
  }

  /**
   * Check and handle empty array values.
   *
   * @param array $documentStructure
   *   The whole document structure.
   * @param array $reference
   *   Array to check.
   * @param array $jsonPath
   *   Json path of the given element.
   */
  protected static function handlePossibleEmptyArray(
    array &$documentStructure,
    array $reference,
    array $jsonPath
  ) {
    if (is_array($reference) && empty($reference)) {
      NestedArray::unsetValue($documentStructure, $jsonPath);
    }
  }

}
