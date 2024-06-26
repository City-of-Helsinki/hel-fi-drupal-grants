<?php

namespace Drupal\grants_metadata\Tests;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Tests\grants_metadata\Kernel\Mappings;

class TestDataRetriever {

  private static array $applicantTypes = [
    'private_person',
    'registered_community',
    'unregistered_community'
  ];

  /**
   * Load test data from data directory.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function loadTestData(): array {
    /** @var ModuleHandlerInterface $moduleHandler */

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

  protected static function anyKeyExists(array $keys, array $array): bool {
    $flippedKeys = array_flip($keys);
    $commonKeys = array_intersect_key($flippedKeys, $array);
    return count($commonKeys) > 0;
  }


  /**
   * Get typed data object for webform data.
   *
   * This is ripped off from ApplicationHandler class.
   *
   * @param array $submittedFormData
   *   Form data.
   * @param string $formId
   *   Webform id.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   Typed data with values set.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  private function webformToTypedData(array $submittedFormData, string $formId): TypedDataInterface {
    $definitionsMappings = Mappings::DEFINITIONS;
    // Datatype plugin requires the module enablation.
    if (!isset($definitionsMappings[$formId])) {
      throw new \Exception('Unknown form id');
    }

    /** @var TypedDataInterface $dataDefinition */
    $dataDefinition = ($definitionsMappings[$formId]['class'])::create($definitionsMappings[$formId]['parameter']);

    $applicationData = $this->typedDataManager->create($dataDefinition);

    $applicationData->setValue($submittedFormData);

    return $applicationData;
  }

}
