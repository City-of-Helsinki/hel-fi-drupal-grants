<?php

namespace Drupal\grants_metadata\Tests;

/**
 * Get test data.
 */
class TestDataRetriever {

  /**
   * Applicant types.
   *
   * @var array
   */
  private static array $applicantTypes = [
    'private_person',
    'registered_community',
    'unregistered_community',
  ];

  /**
   * Load test data.
   *
   * @return array
   *   Test data.
   */
  public function loadTestData(): array {
    $modulePath = __DIR__ . '/../../tests/data';

    $testData = [];

    $files = scandir($modulePath);
    unset($files[0], $files[1]);
    foreach ($files as $file) {
      $fkey = str_replace('.data.json', '', $file);
      if (is_file("$modulePath/$file")) {
        $jsonDataString = file_get_contents("$modulePath/$file");
        $jsonData = json_decode($jsonDataString, TRUE);
        if (self::anyKeyExists(array_keys($jsonData), self::$applicantTypes)) {
          foreach ($jsonData as $applicantType => $applicantData) {
            $testData[$fkey][$applicantType] = $applicantData;
          }
        }
        else {
          $testData[$fkey]['anytype']['json_data'] = $jsonData;
        }
      }
    }

    return $testData;
  }

  /**
   * Check if any key exists in array.
   *
   * @param array $keys
   *   Keys to check.
   * @param array $array
   *   Array to check.
   *
   * @return bool
   *   True if any key exists.
   */
  protected static function anyKeyExists(array $keys, array $array): bool {
    $flippedKeys = array_flip($keys);
    $commonKeys = array_intersect_key($flippedKeys, $array);
    return count($commonKeys) > 0;
  }

}
