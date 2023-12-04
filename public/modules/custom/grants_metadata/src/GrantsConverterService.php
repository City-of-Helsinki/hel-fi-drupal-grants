<?php

namespace Drupal\grants_metadata;

/**
 * Provide useful helper for converting values.
 */
class GrantsConverterService {

  const DEFAULT_DATETIME_FORMAT = 'c';

  /**
   * Format dates to a given or default format.
   *
   * @param string $value
   *   Input value.
   * @param array $arguments
   *   Arguments, dateFormat is used.
   *
   * @return string
   *   Formatted datetime string.
   */
  public function convertDates(string $value, array $arguments): string {

    try {
      if ($value === NULL || $value === '' || !isset($value)) {
        $retval = '';
      }
      else {
        $dateObject = new \DateTime($value);
        if (isset($arguments['dateFormat'])) {
          $retval = $dateObject->format($arguments['dateFormat']);
        }
        else {
          $retval = $dateObject->format(self::DEFAULT_DATETIME_FORMAT);
        }
      }
    }
    catch (\Exception $e) {
      $retval = '';
    }

    return $retval;
  }

  /**
   * Extract & process subvention amount field value.
   *
   * @param array|string $value
   *   Value from JSON data.
   *
   * @return string
   *   Processed field value.
   */
  public function extractFloatValue(array|string $value): string {
    if (is_array($value)) {
      return str_replace('.', ',', $value['value']);
    }

    return str_replace('.', ',', $value);
  }

  /**
   * Convert "dot" float to "comma" float.
   *
   * @param array|null $value
   *   Value to be converted.
   *
   * @return string|null
   *   Comman floated value.
   */
  public function convertToCommaFloat(array $value): ?string {
    $fieldValue = $value['value'] ?? '';
    $fieldValue = str_replace(['€', '.', ' '], ['', ',', ''], $fieldValue);
    return $fieldValue;
  }

}
