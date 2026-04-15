<?php

namespace Drupal\helfi_helsinki_profiili\Helper;

use Drupal\Component\Utility\Xss;

/**
 * Helpers for filtering data.
 */
final class Filters {

  /**
   * Fill primaryPhone field from edge nodes, if it is missing.
   *
   * @param array<mixed> $data
   *   Data array.
   *
   * @return array<mixed>
   *   Modified array
   */
  public static function checkPrimaryFields(array $data): array {
    static $fieldMapping = [
      'phone' => [
        'primary_field_key' => 'primaryPhone',
        'field_key' => 'phones',
      ],
      'email' => [
        'primary_field_key' => 'primaryEmail',
        'field_key' => 'emails',
      ],
      'address' => [
        'primary_field_key' => 'primaryAddress',
        'field_key' => 'addresses',
      ],
    ];

    foreach ($fieldMapping as $mapping) {
      [
        'primary_field_key' => $primaryFieldKey,
        'field_key' => $fieldKey,
      ] = $mapping;

      $primaryField = $data['myProfile'][$primaryFieldKey];
      if ($primaryField === NULL) {
        /*
         * Loop the edges. Get first node with verified flag, or
         * the first edge if none is verified.
         */
        foreach ($data['myProfile'][$fieldKey]['edges'] as $edge) {
          if ($edge['node']['primary']) {
            $primaryField = $edge['node'];
            break;
          }
        }

        // No primary flagged. Try to get first edge number.
        if ($primaryField === NULL) {
          $primaryField = $data['myProfile'][$fieldKey]['edges'][0]['node'] ?? NULL;
        }

        // If we have a edge, let's add it to the data array.
        if ($primaryField !== NULL) {
          $data['myProfile'][$primaryFieldKey] = $primaryField;
        }
      }
    }

    return $data;
  }

  /**
   * Runs the array items through Xss::filter function.
   *
   * @param array<mixed> $data
   *   Input array.
   *
   * @return array<mixed>
   *   Filtered data.
   */
  public static function filterData(array $data): array {
    // Make sure that data coming from HP is sanitized and does not contain
    // anything worth removing.
    array_walk_recursive(
      $data,
      function (&$item) {
        if (is_string($item)) {
          $item = Xss::filter($item);
        }
      }
    );

    return $data;
  }

}
