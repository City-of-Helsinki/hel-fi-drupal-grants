<?php

namespace Drupal\grants_metadata;

/**
 *
 */
class InputmaskHandler {

  /**
   * Converts value to input mask format if input mask definition is fouund.
   *
   * @param mixed $value
   *   Element original value.
   * @param array $metaData
   *   Element metadata.
   *
   * @return mixed
   *   Original value or number_formated value if metadata was found.
   */
  public static function convertPossibleInputmaskValue($value, array $metaData) {
    $retval = $value;

    if (isset($metaData['element']['input_mask'])) {
      $inputMask = $metaData['element']['input_mask'];

      // Force currency to 2 digits.
      if (isset($inputMask['alias']) && $inputMask['alias'] === 'currency') {
        $inputMask['digits'] = 2;
      }

      $number = (float) str_replace(',', '.', $retval);
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
   *
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
