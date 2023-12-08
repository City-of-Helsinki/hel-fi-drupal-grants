<?php

namespace Drupal\grants_metadata;

/**
 * Class to handle Inputmask data in elements metadata.
 */
class InputmaskHandler {

  /**
   * Converts value to input mask format if input mask definition is fouund.
   *
   * @param mixed $value
   *   Element original value.
   * @param mixed $metaData
   *   Element metadata.
   *
   * @return mixed
   *   Original value or number_formated value if metadata was found.
   */
  public static function convertPossibleInputmaskValue($value, $metaData = []) {
    $retval = $value;

    if (empty($value) || !is_array($metaData)) {
      return $value;
    }

    if (isset($metaData['element']['input_mask'])) {
      $inputMask = $metaData['element']['input_mask'];

      // Force currency to 2 digits.
      if (isset($inputMask['alias']) && $inputMask['alias'] === 'currency') {
        $inputMask['digits'] = 2;
      }

      $number = (float) str_replace([',', ' '], ['.', ''], $retval);
      $retval = number_format(
        $number,
        $inputMask['digits'] ?? 0,
        ',',
        ' '
      );
    }

    return $retval;
  }

  /**
   * Add possible inputmask to metadata.
   *
   * @param array $elementMeta
   *   Current element metadata.
   * @param mixed $elementInfo
   *   Possible element info and attributes from webform.
   */
  public static function addInputmaskToMetadata(array &$elementMeta, $elementInfo) {

    if (!isset($elementInfo['#input_mask']) || !is_array($elementInfo)) {
      return;
    }

    $inputMask = $elementInfo['#input_mask'];
    $inputMaskData = '{' . str_replace('\'', '"', $inputMask) . '}';
    $decodedMaskData = json_decode($inputMaskData);
    $elementMeta['input_mask'] = $decodedMaskData;
  }

}
