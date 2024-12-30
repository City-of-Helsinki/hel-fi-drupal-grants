<?php

declare(strict_types = 1);

namespace Drupal\grants_metadata;

/**
 * Helper class for conversion functions.
 */
final class ConvertHelper {

  /**
   * Convert EUR format value to "int" .
   *
   * @param string|null $value
   *   Value to be converted.
   *
   * @return int|null
   *   Int value.
   */
  public static function convertToInt(?string $value = ''): ?int {
    if (is_null($value)) {
      return NULL;
    }

    if ($value === '') {
      return NULL;
    }

    $value = str_replace(['€', ',', ' ', '_'], ['', '.', '', ''], $value);
    $value = (int) $value;
    return $value;
  }

  /**
   * Convert EUR format value to float.
   *
   * @param string|null $value
   *   Value to be converted.
   *
   * @return float|null
   *   Floated value.
   */
  public static function convertToFloat(?string $value = ''): ?float {
    if (is_null($value)) {
      return NULL;
    }

    if ($value === '') {
      return NULL;
    }

    $value = str_replace(['€', ',', ' '], ['', '.', ''], $value);
    return (float) $value;
  }

}
