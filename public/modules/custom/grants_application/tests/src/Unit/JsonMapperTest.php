<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit\Form;

use Drupal\grants_application\Mapper\JsonMapper;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the FormSettingsService::getLabels method.
 *
 * @group grants_application
 */
final class JsonMapperTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Test the default mappings.
   *
   * @return void
   */
  public function testDefaultMapping(): void {
    // Get mappings for a few fields and the datasources.
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('defaultFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    // Assert that the source data is mapped properly into target format.
    $this->assertTrue(isset($mappedData['compensation']['applicant']['user'][0]['ID']), "Assert simple value");
    $this->assertTrue($mappedData['compensation']['applicant']['user'][0]['ID'] === 'applicant_name');
    $this->assertTrue($mappedData['compensation']['applicant']['user'][1]['value'] === '1947');
  }

  public function testMultipleValueFieldMapping(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('multipleValueFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['orienteeringMapInfo']['orienteeringMapsArray'][0][0]['ID']), "Assert multiple values");
    $this->assertTrue($mappedData['compensation']['orienteeringMapInfo']['orienteeringMapsArray'][0][0]['value'] === "Peruskoulun suunnistuskartta");
  }

  /**
   * Test the complex value mapping.
   *
   * At this point there is only one which is setLabelAndValue-function.
   *
   * @return void
   */
  public function testComplexValueMapping(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('complexFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['costs']['budget']['properties'][0]['ID']), "Assert complex values");
    $this->assertTrue($mappedData['compensation']['costs']['budget']['properties'][0]['label'] === 'Toimitilat');

    $this->assertTrue(isset($mappedData['compensation']['otherCostRowsArrayStatic'][0]['ID']), 'Income component');
    $this->assertTrue(str_contains($mappedData['compensation']['otherCostRowsArrayStatic'][1]['ID'], '_1'));
  }

  public function testSimpleFieldValueMapping(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('simpleFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['additionalInformation']), "Simple field test.");
    $this->assertNotEmpty($mappedData['compensation']['additionalInformation']);
  }



  public function testEmptyValueMapping(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('emptyFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['budgetInfo']['costGroupsArrayStatic'][0]), "Assert empty value");
    $this->assertTrue(is_array($mappedData['compensation']['budgetInfo']['costGroupsArrayStatic'][0]));
    $this->assertTrue(empty($mappedData['compensation']['budgetInfo']['costGroupsArrayStatic'][0]));
  }

  public function testHardcoded(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('hardcodedFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['budgetInfo']['hardcoded']));
    $this->assertTrue($mappedData['compensation']['budgetInfo']['hardcoded'] == 'my_value');

    $this->assertTrue($mappedData['compensation']['budgetInfo']['hardcoded2'][0]['hardcoded'] == 'object');
  }

  /**
   * Combine the common datasources and the actual form into one.
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
    $commonDatasources = json_decode(file_get_contents(__DIR__ . '/../../fixtures/reactForm/commonDatasources.json'),TRUE);
    $specificDatasource = json_decode(file_get_contents(__DIR__ . '/../../fixtures/reactForm/'. $fixtureName), TRUE);
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
    $mappingFixtures = file_get_contents(__DIR__ . '/../../fixtures/reactForm/'. $fixtureName);
    return json_decode($mappingFixtures, true);
  }
}
