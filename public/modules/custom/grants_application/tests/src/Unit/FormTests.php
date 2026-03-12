<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit\Form;

use Drupal\grants_application\Mapper\JsonMapper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the form mapping.
 *
 * Manually fill a form and add the form_data and avus2-data as fixtures-json.
 * Test runs the mapping code, compares the result to the expected Avus2-json.
 */
final class FormTests extends UnitTestCase {

  /**
   * Test ID58, no added files.
   */
  public function testForm58NoFiles() {
    $forms = [
      'ID58' => 'liikunta_suunnistuskartta_avustu',
      'ID70' => 'promoting_safer_club_activities',
    ];

    foreach ($forms as $formId => $formIdentifier) {
      $commonMappings = $this->getRealMapping('common', 'registered_community');
      $realMapping = $this->getRealMapping($formId, $formIdentifier);
      $mapping = array_merge($commonMappings, $realMapping);

      $dataSources = $this->getAllDatasources('form58-nofiles-formdata.json');

      $mapper = new JsonMapper();
      $mapper->setMappings($mapping);
      $fields = $mapper->map($dataSources);
      $files = $mapper->mapFiles($dataSources);

      // Running mapper should always return same values.
      $originalResult = json_decode(file_get_contents(__DIR__ . '/../../fixtures/reactForm/form58-nofiles-result.json'), TRUE);
      $this->assertEquals($originalResult, $fields);
      $this->assertCount(0, $files);
    }
  }

  /**
   * Combine the common data sources and the actual form into one.
   *
   * The end result contains data from react-form, user profile,...
   *
   * @param string $fixtureName
   *   The name of the fixture file.
   *
   * @return array
   *   Common datasource data and the form-data combined.
   */
  private function getAllDatasources(string $fixtureName): array {
    $commonDatasources = json_decode(file_get_contents(__DIR__ . '/../../fixtures/reactForm/commonDatasources.json'), TRUE);
    $specificDatasource = json_decode(file_get_contents(__DIR__ . '/../../fixtures/reactForm/' . $fixtureName), TRUE);
    $commonDatasources['form_data'] = $specificDatasource;

    return $commonDatasources;
  }

  /**
   * Get the mapping definitions.
   *
   * The mapping definition contains the data, how to turn the source data into
   * target data.
   *
   * @param string $fixtureName
   *   The fixture name.
   *
   * @return array
   *   The mapping file.
   */
  private function getMapping(string $fixtureName): array {
    $mappingFixtures = file_get_contents(__DIR__ . '/../../fixtures/reactForm/' . $fixtureName);
    return json_decode($mappingFixtures, TRUE);
  }

  /**
   * Get the real mappings.
   *
   * @param string $form_id
   *   Form type id.
   * @param string $form_identifier
   *   Form identifier.
   *
   * @return mixed
   *
   */
  private function getRealMapping(string $form_id, string $form_identifier): array {
    $mappingFixtures = file_get_contents(__DIR__ . "/../../../src/Mapper/Mappings/$form_id/$form_identifier.json");
    return json_decode($mappingFixtures, TRUE);
  }

}
