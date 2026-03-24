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
final class FormTest extends UnitTestCase {

  /**
   * Test ID58, no added files.
   */
  public function testMultipleForms() {
    // Result is whatever was sent to avus2 when you sent the application
    // for the first time, set in json file.
    // phpcs:disable
    $forms = [
      ['id' => 'ID58', 'form_identifier' => 'liikunta_suunnistuskartta_avustu', 'form_data' => 'form58-nofiles-formdata', 'result' => 'form58-nofiles-result'],
      // ['id' => 'ID58', 'form_identifier' => 'liikunta_suunnistuskartta_avustu', 'form_data' => 'form58-file-formdata', 'result' => 'form58-file-result'],
      ['id' => 'ID70', 'form_identifier' => 'promoting_safer_club_activities', 'form_data' => 'form70-safer-nofiles-formdata', 'result' => 'form70-safer-nofiles-result', 'settings' => 'form70-settings'],
    ];
    // phpcs:enable

    foreach ($forms as $info) {
      $commonMappings = $this->getRealMapping('common', 'registered_community');
      $realMapping = $this->getRealMapping($info['id'], $info['form_identifier']);
      $mapping = array_merge($commonMappings, $realMapping);

      $dataSources = $this->getAllDatasources($info['form_data']);
      if (isset($info['settings'])) {
        $settings = json_decode(file_get_contents(__DIR__ . "/../../fixtures/reactForm/{$info['settings']}.json"), TRUE);
        $dataSources['form_settings']['settings'] = $settings;
      }

      $mapper = new JsonMapper();
      $mapper->setMappings($mapping);
      $fields = $mapper->map($dataSources);
      $files = $mapper->mapFiles($dataSources);

      // Running mapper should always return same values.
      $originalResult = json_decode(file_get_contents(__DIR__ . '/../../fixtures/reactForm/' . $info['result'] . '.json'), TRUE);
      $this->assertEquals($originalResult, $fields, "asserting {$info['form_identifier']}, {$info['result']}");
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
    $specificDatasource = json_decode(file_get_contents(__DIR__ . '/../../fixtures/reactForm/' . $fixtureName . '.json'), TRUE);
    $commonDatasources['form_data'] = $specificDatasource;

    return $commonDatasources;
  }

  /**
   * Get the real mappings.
   *
   * @param string $form_id
   *   Form type id.
   * @param string $form_identifier
   *   Form identifier.
   *
   * @return array
   *   The real mapping from module mappings.
   */
  private function getRealMapping(string $form_id, string $form_identifier): array {
    $mappingFixtures = file_get_contents(__DIR__ . "/../../../src/Mapper/Mappings/$form_id/$form_identifier.json");
    return json_decode($mappingFixtures, TRUE);
  }

}
