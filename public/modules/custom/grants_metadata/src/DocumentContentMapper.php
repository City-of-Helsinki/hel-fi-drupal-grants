<?php

namespace Drupal\grants_metadata;

use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\grants_attachments\AttachmentHandler;

/**
 * Provides DocumentContentMapper class.
 *
 * Maps document data to typed data.
 */
class DocumentContentMapper {

  /**
   * Attachment file types.
   *
   * Saved statically to prevent too many queries.
   *
   * @var array
   */
  private static array $attachmentFileTypes = [];

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
   *   Mapped data from document.
   */
  public static function documentContentToTypedData(
    array $documentData,
    ComplexDataDefinitionInterface $typedDataDefinition,
    ?array $metadata = [],
  ): array {
    $documentContent = self::extractDocumentContent($documentData);
    $propertyDefinitions = $typedDataDefinition->getPropertyDefinitions();
    $typedDataValues = self::mapPropertiesToTypedData($propertyDefinitions, $documentData, $documentContent);

    self::processStatusUpdates($typedDataValues);
    self::processAttachments($typedDataValues);
    self::fixIssuerNameCase($typedDataValues);
    self::processCommunityAddress($typedDataValues);
    self::processBankAccount($typedDataValues);
    self::processBusinessPractice($typedDataValues);
    self::processApplicantType($typedDataValues);

    $typedDataValues['metadata'] = $metadata;
    return $typedDataValues;
  }

  /**
   * Extracts the document content.
   *
   * @param array $documentData
   *   Document as array.
   *
   * @return array
   *   Extracted document content.
   */
  private static function extractDocumentContent(array $documentData): array {
    return isset($documentData['content']) && is_array($documentData['content']) ? $documentData['content'] : $documentData;
  }

  /**
   * Maps properties to typed data.
   *
   * @param array $propertyDefinitions
   *   Property definitions from typed data definition.
   * @param array $documentData
   *   Document data.
   * @param array $documentContent
   *   Extracted document content.
   *
   * @return array
   *   Mapped typed data values.
   */
  private static function mapPropertiesToTypedData(array $propertyDefinitions, array $documentData, array $documentContent): array {
    $typedDataValues = [];
    foreach ($propertyDefinitions as $definitionKey => $definition) {
      $jsonPath = $definition->getSetting('jsonPath');
      $webformDataExtractor = $definition->getSetting('webformDataExtracter');

      if ($webformDataExtractor) {
        $arguments = $webformDataExtractor['arguments'] ?? [];
        $extractedValues = self::getWebformDataFromContent($webformDataExtractor, $documentData, $definition, $arguments);
        if (!empty($webformDataExtractor['mergeResults'])) {
          $typedDataValues = array_merge($typedDataValues, $extractedValues);
        }
        else {
          $typedDataValues[$definitionKey] = $extractedValues;
        }
      }
      elseif (is_array($jsonPath)) {
        $elementName = array_pop($jsonPath);
        $typedDataValues[$definitionKey] = DocumentValueExtractor::getValueFromDocument(
          $documentContent, $jsonPath, $elementName, $definition);
      }
    }
    return $typedDataValues;
  }

  /**
   * Processes status updates in the typed data values.
   *
   * @param array &$typedDataValues
   *   Typed data values.
   */
  private static function processStatusUpdates(array &$typedDataValues): void {
    if (isset($typedDataValues['status_updates']) && is_array($typedDataValues['status_updates'])) {
      foreach ($typedDataValues['status_updates'] as $status) {
        $typedDataValues['status'] = $status['citizenCaseStatus'];
      }
    }
  }

  /**
   * Processes attachments in the typed data values.
   *
   * @param array $typedDataValues
   *   Typed data values.
   */
  private static function processAttachments(array &$typedDataValues): void {
    $other_attachments = [];

    $applicationNumber = $typedDataValues["application_number"];

    // Check if the static variable is already populated
    // for the given application number.
    if (!isset(self::$attachmentFileTypes[$applicationNumber])) {
      // If not, populate it.
      self::$attachmentFileTypes[$applicationNumber] =
        AttachmentHandler::getAttachmentFieldNames(
          $applicationNumber,
          TRUE
        );
    }

    if (!isset($typedDataValues["attachments"])) {
      $typedDataValues["attachments"] = [];
    }

    foreach ($typedDataValues["attachments"] as $key => $attachment) {
      $fileType = $attachment["fileType"];
      // Get fieldname for the attachment.
      $fieldName = array_search($fileType, self::$attachmentFileTypes[$applicationNumber]);
      $newValues = $attachment;

      if (!empty($attachment["fileName"])) {
        $newValues["isNewAttachment"] = 'false';
        $newValues['attachmentName'] = $attachment['fileName'];
      }

      if ($fieldName === 'muu_liite' || (int) $fileType === 45) {
        $other_attachments[$key] = $newValues;
        unset($typedDataValues["attachments"][$key]);
      }
      else {
        $typedDataValues[$fieldName] = $newValues;
      }
    }

    $typedDataValues['muu_liite'] = $other_attachments;
  }

  /**
   * Fixes the case of issuer name fields.
   *
   * @param array &$typedDataValues
   *   Typed data values.
   */
  private static function fixIssuerNameCase(array &$typedDataValues): void {
    self::updateIssuerNameCase($typedDataValues, 'myonnetty_avustus');
    self::updateIssuerNameCase($typedDataValues, 'haettu_avustus_tieto');
  }

  /**
   * Updates the case of issuer name fields for a specific key.
   *
   * @param array &$typedDataValues
   *   Typed data values.
   * @param string $key
   *   The key to update issuer name case for.
   */
  private static function updateIssuerNameCase(array &$typedDataValues, string $key): void {
    if (isset($typedDataValues[$key])) {
      foreach ($typedDataValues[$key] as $subKey => $avustus) {
        if (isset($avustus['issuerName'])) {
          $typedDataValues[$key][$subKey]['issuer_name'] = $avustus['issuerName'];
        }
      }
    }
  }

  /**
   * Processes the community address fields.
   *
   * @param array &$typedDataValues
   *   Typed data values.
   */
  private static function processCommunityAddress(array &$typedDataValues): void {
    $community_address = [];
    foreach ([
      'community_street',
      'community_city',
      'community_post_code',
      'community_country',
    ] as $field) {
      if (isset($typedDataValues[$field])) {
        $community_address[$field] = $typedDataValues[$field];
        unset($typedDataValues[$field]);
      }
    }
    $typedDataValues['community_address'] = $community_address;
  }

  /**
   * Processes the bank account fields.
   *
   * @param array &$typedDataValues
   *   Typed data values.
   */
  private static function processBankAccount(array &$typedDataValues): void {
    if (isset($typedDataValues['account_number'])) {
      $typedDataValues['bank_account'] = [
        'account_number' => $typedDataValues['account_number'],
        'account_number_select' => $typedDataValues['account_number'],
        'account_number_ssn' => $typedDataValues['account_number_ssn'] ?? NULL,
        'account_number_owner_name' => $typedDataValues['account_number_owner_name'] ?? NULL,
      ];
    }
  }

  /**
   * Processes the community business practice field.
   *
   * @param array &$typedDataValues
   *   Typed data values.
   */
  private static function processBusinessPractice(array &$typedDataValues): void {
    if (isset($typedDataValues['community_practices_business'])) {
      $typedDataValues['community_practices_business'] = $typedDataValues['community_practices_business'] === 'true' ? 1 : 0;
    }
  }

  /**
   * Processes the applicant type field.
   *
   * @param array &$typedDataValues
   *   Typed data values.
   */
  private static function processApplicantType(array &$typedDataValues): void {
    if (isset($typedDataValues['hakijan_tiedot']['applicantType'])) {
      $typedDataValues['applicant_type'] = $typedDataValues['hakijan_tiedot']['applicantType'];
    }
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
  private static function getWebformDataFromContent(
    array $fullItemValueCallback,
    array $content,
    DataDefinitionInterface $definition,
    array $arguments,
  ): mixed {
    $fieldValues = [];
    if (isset($fullItemValueCallback['service'])) {
      $fullItemValueService = AtvSchema::getDynamicService($fullItemValueCallback['service']);
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

}
