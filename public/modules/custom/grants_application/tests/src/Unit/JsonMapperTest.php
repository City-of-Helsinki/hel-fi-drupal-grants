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

  /**
   * Test the default mappings.
   */
  public function testDefaultMapping(): void {
    // Get mappings for a few fields and the datasources.
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('defaultFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper();
    $mapper->setMappings($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    // Assert that the source data is mapped properly into target format.
    $this->assertTrue(isset($mappedData['compensation']['applicant']['user'][0]['ID']), "Assert simple value");
    $this->assertTrue($mappedData['compensation']['applicant']['user'][0]['ID'] === 'applicant_name');
    $this->assertTrue($mappedData['compensation']['applicant']['user'][1]['value'] === '1947');
  }

  /**
   * Test multiple value field mapping.
   *
   * User can add multivalue -fieldgroup multiple times.
   */
  public function testMultipleValueFieldMapping(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('multipleValueFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper();
    $mapper->setMappings($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['orienteeringMapInfo']['orienteeringMapsArray'][0][0]['ID']), "Assert multiple values");
    $this->assertEquals("Peruskoulun suunnistuskartta", $mappedData['compensation']['orienteeringMapInfo']['orienteeringMapsArray'][0][0]['value']);

    $this->assertTrue(isset($mappedData['compensation']['orienteeringMapInfo']['orienteeringMapsArray'][0][2]['ID']), "Assert nested multiple values");
    $this->assertEquals("creatorFirstname", $mappedData['compensation']['orienteeringMapInfo']['orienteeringMapsArray'][0][2]['ID']);
    $this->assertEquals("Keijo", $mappedData['compensation']['orienteeringMapInfo']['orienteeringMapsArray'][0][2]['value']);
  }

  /**
   * Test the complex value mapping.
   *
   * At this point there is only one which is setLabelAndValue-function.
   */
  public function testComplexValueMapping(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('complexFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper();
    $mapper->setMappings($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['costs']['budget']['properties'][0]['ID']), "Assert complex values");
    $this->assertTrue($mappedData['compensation']['costs']['budget']['properties'][0]['label'] === 'Toimitilat');

    $this->assertTrue(isset($mappedData['compensation']['otherCostRowsArrayStatic'][0]['ID']), 'Income component');
    $this->assertTrue(str_contains($mappedData['compensation']['otherCostRowsArrayStatic'][1]['ID'], '_1'));
  }

  /**
   * Test simple values mapping.
   *
   * Simple value is Key: value where key is hardcoded, value comes from data.
   */
  public function testSimpleFieldValueMapping(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('simpleFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper();
    $mapper->setMappings($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['additionalInformation']), "Simple field test.");
    $this->assertNotEmpty($mappedData['compensation']['additionalInformation']);
  }

  /**
   * Test empty values.
   */
  public function testEmptyValueMapping(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('emptyFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper();
    $mapper->setMappings($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['budgetInfo']['costGroupsArrayStatic'][0]), "Assert empty value");
    $this->assertTrue(is_array($mappedData['compensation']['budgetInfo']['costGroupsArrayStatic'][0]));
    $this->assertTrue(empty($mappedData['compensation']['budgetInfo']['costGroupsArrayStatic'][0]));
  }

  /**
   * Test hardcoded values.
   */
  public function testHardcoded(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('hardcodedFieldForm.json');

    // Perform mapping from source data to target data.
    $mapper = new JsonMapper();
    $mapper->setMappings($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['budgetInfo']['hardcoded']));
    $this->assertTrue($mappedData['compensation']['budgetInfo']['hardcoded'] == 'my_value');

    $this->assertTrue($mappedData['compensation']['budgetInfo']['hardcoded2'][0]['hardcoded'] == 'object');
  }

  /**
   * Tests file mapping.
   */
  public function testFileMapping(): void {
    $mappings = [
      "attachmentsInfo.attachmentsArray.0" => [
        "datasource" => "form_data",
        "source" => "attachments_step.attachments_section.attachment_without_file.file",
        "mapping_type" => "file",
        "data" => [
          "description" => ["ID" => "description", "value" => "", "valueType" => "string", "label" => "Liitteen kuvaus"],
          "fileName" => ["ID"  => "fileName", "value" => "", "valueType" => "string", "label" => "Tiedostonimi"],
          "fileType" => ["ID"  => "fileType", "value" => "10", "valueType" => "int", "label" => "fileType"],
          "integrationID" => ["ID"  => "integrationID", "value" => "", "valueType" => "string", "label" => "integrationID"],
          "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
          "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
        ]
      ],
      "attachmentsInfo.attachmentsArray.n" => [
        "datasource" => "form_data",
        "source" => "attachments_step.attachments_section.other_attachment_fieldset.files",
        "mapping_type" => "multiple_files",
        "data" => [
          "description" => ["ID" => "description", "value" => "", "valueType" => "string", "label" => "Liitteen kuvaus"],
          "fileName" => ["ID"  => "fileName", "value" => "", "valueType" => "string", "label" => "Tiedostonimi"],
          "fileType" => ["ID"  => "fileType", "value" => "1", "valueType" => "int", "label" => "fileType"],
          "integrationID" => ["ID"  => "integrationID", "value" => "", "valueType" => "string", "label" => "integrationID"],
          "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
          "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
        ]
      ]
    ];

    $dataSources = [
      'form_data' => [
        'attachments_step' => [
          'attachments_section' => [
            'attachment_without_file' => [
              'file' => [
                'isDeliveredLater' => TRUE,
                'isIncludedInOtherFile' => FALSE
              ],
            ],
            'other_attachment_fieldset' => [
              'files' => [
                'description' => 'kuvaus liitetiedostosta tulee tänne',
                'fileName' => 'testfile.pdf',
                'integrationID' => 'https://example.com/v1/documents/aaaaaaaa-1111-2222-3333-bbbbbbcccccc/attachments/999999/',
                'isDeliveredLater' => FALSE,
                'isIncludedInOtherFile' => FALSE,
                'isNewAttachment' => TRUE,
                'size' => 13264,
                'fileId' => 323335
              ],
            ],
          ],
        ],
      ],
    ];

    $mapper = new JsonMapper();
    $mapper->setMappings($mappings);
    $mappedFiles = $mapper->mapFiles($dataSources);

    $this->assertTrue(count($mappedFiles['attachmentsInfo']['attachmentsArray']) === 2, 'Both files exists.');
    $this->assertEquals('true', $mappedFiles['attachmentsInfo']['attachmentsArray'][0][2]['value']);
    $this->assertEquals('fileName', $mappedFiles['attachmentsInfo']['attachmentsArray'][1][1]['ID']);
    $this->assertEquals('testfile.pdf', $mappedFiles['attachmentsInfo']['attachmentsArray'][1][1]['value']);
  }

  /**
   * Test the logic that handles files on patch request
   *
   * Must have the bank file.
   * Must have all and any "other attachments" whenever they are added.
   * Must have all "sent later" -marked files
   * Must have all the new files that overwrites the "sent later" -files
   */
  public function testHandleFilesOnPatchRequest(): void {
    $oldFiles = [
      // Bank file.
      [
        "description" => ["ID" => "description", "value" => "Vahvistus tilinumerolle FIxxxxxxxxxx", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileName" => ["ID"  => "fileName", "value" => "", "valueType" => "string", "label" => "Tiedostonimi"],
        "fileType" => ["ID"  => "fileType", "value" => "1", "valueType" => "int", "label" => "fileType"],
        "integrationID" => ["ID"  => "integrationID", "value" => "", "valueType" => "string", "label" => "integrationID"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
      // Another required file.
      [
        "description" => ["ID" => "description", "value" => "Joku toinen liite", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileName" => ["ID"  => "fileName", "value" => "Testi.pdf", "valueType" => "string", "label" => "Tiedostonimi"],
        "fileType" => ["ID"  => "fileType", "value" => "2", "valueType" => "int", "label" => "fileType"],
        "integrationID" => ["ID"  => "integrationID", "value" => "", "valueType" => "string", "label" => "integrationID"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "false", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "false", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
      // One "other attachments", filetype 0.
      [
        "description" => ["ID" => "description", "value" => "Eka random tiedosto", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileName" => ["ID"  => "fileName", "value" => "random1.pdf", "valueType" => "string", "label" => "Tiedostonimi"],
        "fileType" => ["ID"  => "fileType", "value" => "0", "valueType" => "int", "label" => "fileType"],
        "integrationID" => ["ID"  => "integrationID", "value" => "url-to-atv-plus-file-id-12345", "valueType" => "string", "label" => "integrationID"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "false", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "false", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
      // Two required files which are marked as "delivered later".
      [
        "description" => ["ID" => "description", "value" => "", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileType" => ["ID"  => "fileType", "value" => "3", "valueType" => "int", "label" => "fileType"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "true", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "false", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
      [
        "description" => ["ID" => "description", "value" => "To be overwritten", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileType" => ["ID"  => "fileType", "value" => "4", "valueType" => "int", "label" => "fileType"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "true", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "false", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
    ];

    $newFiles = [
      // Bank file.
      [
        "description" => ["ID" => "description", "value" => "Vahvistus tilinumerolle FIxxxxxxxxxx", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileName" => ["ID"  => "fileName", "value" => "", "valueType" => "string", "label" => "Tiedostonimi"],
        "fileType" => ["ID"  => "fileType", "value" => "1", "valueType" => "int", "label" => "fileType"],
        "integrationID" => ["ID"  => "integrationID", "value" => "", "valueType" => "string", "label" => "integrationID"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
      // Previously sent Another required file.
      [
        "description" => ["ID" => "description", "value" => "Joku toinen liite", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileName" => ["ID"  => "fileName", "value" => "Testi.pdf", "valueType" => "string", "label" => "Tiedostonimi"],
        "fileType" => ["ID"  => "fileType", "value" => "2", "valueType" => "int", "label" => "fileType"],
        "integrationID" => ["ID"  => "integrationID", "value" => "", "valueType" => "string", "label" => "integrationID"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "false", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "false", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
      // Previously sent "other attachment".
      [
        "description" => ["ID" => "description", "value" => "Eka random tiedosto", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileName" => ["ID"  => "fileName", "value" => "random1.pdf", "valueType" => "string", "label" => "Tiedostonimi"],
        "fileType" => ["ID"  => "fileType", "value" => "0", "valueType" => "int", "label" => "fileType"],
        "integrationID" => ["ID"  => "integrationID", "value" => "url-to-atv-plus-file-id-12345", "valueType" => "string", "label" => "integrationID"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "false", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "false", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
      // Previously set, One of the "to be seent later" file.
      [
        "description" => ["ID" => "description", "value" => "", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileType" => ["ID"  => "fileType", "value" => "3", "valueType" => "int", "label" => "fileType"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "true", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "false", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
      // Overwritten One of the two "to be sent later" -files is now added.
      [
        "description" => ["ID" => "description", "value" => "Overwritten 'sent later'-file", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileName" => ["ID"  => "fileName", "value" => "Testi-4.pdf", "valueType" => "string", "label" => "Tiedostonimi"],
        "fileType" => ["ID"  => "fileType", "value" => "4", "valueType" => "int", "label" => "fileType"],
        "integrationID" => ["ID"  => "integrationID", "value" => "", "valueType" => "string", "label" => "integrationID"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "false", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "false", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
      // Other attachment added on patch request
      [
        "description" => ["ID" => "description", "value" => "Other attachment added on patch request", "valueType" => "string", "label" => "Liitteen kuvaus"],
        "fileName" => ["ID"  => "fileName", "value" => "random-2.pdf", "valueType" => "string", "label" => "Tiedostonimi"],
        "fileType" => ["ID"  => "fileType", "value" => "0", "valueType" => "int", "label" => "fileType"],
        "integrationID" => ["ID"  => "integrationID", "value" => "url-to-atv-plus-file-id-67890", "valueType" => "string", "label" => "integrationID"],
        "isDeliveredLater" => ["ID"  => "isDeliveredLater", "value" => "false", "valueType" => "bool", "label" => "Liite toimitetaan myöhemmin"],
        "isIncludedInOtherFile" => ["ID"  => "isIncludedInOtherFile", "value" => "false", "valueType" => "bool", "label" => "Liite on toimitettu yhtenä tiedostona tai toisen hakemuksen yhteydessä"]
      ],
    ];

    $mapper = new JsonMapper();
    $mappedFiles = $mapper->patchMappedFiles($oldFiles, $newFiles);

    $this->assertCount(6, $mappedFiles);
    $this->assertEquals(1, $mappedFiles[0]['fileType']['value']);
    $this->assertEquals(2, $mappedFiles[1]['fileType']['value']);
    $this->assertEquals(0, $mappedFiles[2]['fileType']['value']);
    // Third file is delivered later.
    $this->assertEquals(3, $mappedFiles[3]['fileType']['value']);
    $this->assertEquals('true', $mappedFiles[3]['isDeliveredLater']['value']);
    // Fourth file is overwritten on patch request.
    $this->assertEquals(4, $mappedFiles[4]['fileType']['value']);
    $this->assertEquals('Overwritten \'sent later\'-file', $mappedFiles[4]['description']['value']);
    // New file added on patch request.
    $this->assertEquals(0, $mappedFiles[5]['fileType']['value']);
    $this->assertEquals("Other attachment added on patch request", $mappedFiles[5]['description']['value']);
  }

  /**
   * Tests Multiple files from single field mapping.
   */
  public function testMultipleFilesMapping(): void {
    $defaultMappings = $this->getMapping('filemappings.json');
    $dataSources = $this->getAllDatasources('multipleFilesFieldForm.json');

    $mapper = new JsonMapper();
    $mapper->setMappings($defaultMappings);
    $mappedFiles = $mapper->mapFiles($dataSources);

    $this->assertTrue(count($mappedFiles['attachmentsInfo']['attachmentsArray']) === 2, 'All files exist.');

    $descriptionExists = FALSE;
    $descriptionValue = FALSE;
    foreach ($mappedFiles['attachmentsInfo']['attachmentsArray'][0] as $singleFile) {
      if (isset($singleFile['ID']) && $singleFile['ID'] === 'description') {
        $descriptionExists = TRUE;
        $descriptionValue = $singleFile['value'];
        break;
      }
    }

    $this->assertTrue($descriptionExists, 'Description exists.');
    $this->assertEquals('kuvaus liitetiedostosta tulee tänne', $descriptionValue, 'default value given in mappings should have been overwritten by form value');
    $this->assertNotEquals('Yhteisön säännöt', $descriptionValue, 'default value overwrite works');
    $this->assertTrue($mappedFiles['attachmentsInfo']['attachmentsArray'][0][1]['ID'] === 'fileName', 'Second field: fileName');
    $this->assertEquals('file1.pdf', $mappedFiles['attachmentsInfo']['attachmentsArray'][0][1]['value']);
  }

  /**
   * Test ID58, no uploaded files.
   */
  public function testForm58NoFiles() {
    $commonMappings = $this->getRealMapping('common', 'registered_community');
    $realMapping = $this->getRealMapping('ID58', 'liikunta_suunnistuskartta_avustu');
    $mapping = array_merge($commonMappings, $realMapping);

    $dataSources = $this->getAllDatasources('form58-nofiles-formdata.json');

    $mapper = new JsonMapper();
    $mapper->setMappings($mapping);
    $fields = $mapper->map($dataSources);
    $files = $mapper->mapFiles($dataSources);

    // Mapping the same form values should always result in
    // similar Avus2-json-data.
    $originalResult = json_decode(file_get_contents(__DIR__ . '/../../fixtures/reactForm/form58-nofiles-result.json'), TRUE);
    $this->assertEquals($originalResult, $fields);
    $this->assertCount(0, $files);
  }

  /**
   * Tests enumToLabel custom handler mapping for a string enum value.
   */
  public function testEnumToLabelMapping(): void {
    $defaultMappings = $this->getMapping('mappings.json');
    $dataSources = $this->getAllDatasources('enumLabelForm.json');

    $mapper = new JsonMapper();
    $mapper->setMappings($defaultMappings);
    $mappedData = $mapper->map($dataSources);

    $this->assertTrue(isset($mappedData['compensation']['grantDuration']['value']), 'enumToLabel result exists');
    $this->assertEquals('1-3 vuotta', $mappedData['compensation']['grantDuration']['value'], 'Enum value "2" maps to label');
  }

  /**
   * Tests enumToLabel custom handler mapping for boolean true and false values.
   */
  public function testEnumToLabelBoolMapping(): void {
    $defaultMappings = $this->getMapping('mappings.json');

    $mapper = new JsonMapper();
    $mapper->setMappings($defaultMappings);

    $trueData = $this->getAllDatasources('enumLabelForm.json');
    $trueMapped = $mapper->map($trueData);
    $this->assertEquals('Kyllä', $trueMapped['compensation']['isExtension']['value'], 'Bool "1" maps to Kyllä');

    $falseData = $this->getAllDatasources('enumLabelBoolForm.json');
    $falseMapped = $mapper->map($falseData);
    $this->assertEquals('Ei', $falseMapped['compensation']['isExtension']['value'], 'Bool "" maps to Ei');
  }

  /**
   * Double values should be mapped with dot.
   */
  public function testDoubleCommaToDotMapping(): void {
    $mapping = [
      "compensation.my_numbers.double_with_dot" => [
        'datasource' => 'form_data',
        'source' => 'number_data.double_with_comma',
        'mapping_type' => 'default',
        'data' => [
          'ID' => 'justADoubleField',
          'valueType' => 'double',
          'value' => '',
          'label' => 'Comma should be replaced by dot',
        ],
      ],
      "compensation.my_numbers.float_with_comma" => [
        'datasource' => 'form_data',
        'source' => 'number_data.float_with_comma',
        'mapping_type' => 'default',
        'data' => [
          'ID' => 'justAnotherNumericValue',
          'valueType' => 'float',
          'value' => '',
          'label' => 'Float should not be affected',
        ],
      ],
      "compensation.my_numbers.income_with_dot" => [
        'datasource' => 'form_data',
        'source' => 'number_data.income_section.income',
        'mapping_type' => 'custom',
        'custom_handler' => 'income',
        'data' => [
          'ID' => 'incomeLabel',
          'valueType' => 'double',
          'value' => '',
          'label' => 'This is overwritten',
        ],
      ],
      "compensation.my_numbers.income_with_comma" => [
        'datasource' => 'form_data',
        'source' => 'number_data.income_section.income',
        'mapping_type' => 'custom',
        'custom_handler' => 'income',
        'data' => [
          'ID' => 'incomeLabel',
          'valueType' => 'float',
          'value' => '',
          'label' => 'This is overwritten',
        ],
      ],
    ];

    $formData = [
      'form_data' => [
        'number_data' => [
          'double_with_comma' => '133,7',
          'float_with_comma' => '12,0',
          'income_section' => [
            'income' => [
              ['label' => 'the label', 'amount' => '123,45'],
              ['label' => 'the label 2', 'amount' => 123],
              ['label' => 'the label 3', 'amount' => ""]
            ]
          ]
        ],
      ],
    ];

    $mapper = new JsonMapper();
    $mapper->setMappings($mapping);
    $mappedData = $mapper->map($formData);

    $this->assertEquals('133.7', $mappedData['compensation']['my_numbers']['double_with_dot']['value']);
    $this->assertEquals('12,0', $mappedData['compensation']['my_numbers']['float_with_comma']['value']);
    $this->assertEquals('123.45', $mappedData['compensation']['my_numbers']['income_with_dot'][0]['value']);
    $this->assertEquals('the label', $mappedData['compensation']['my_numbers']['income_with_dot'][0]['label']);
    $this->assertEquals('123', $mappedData['compensation']['my_numbers']['income_with_dot'][1]['value']);
    $this->assertEquals('0', $mappedData['compensation']['my_numbers']['income_with_dot'][2]['value']);

    // Float is still comma, only double is changed to dot.
    $this->assertEquals('123,45', $mappedData['compensation']['my_numbers']['income_with_comma'][0]['value']);
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
   * @return array
   *   A real mapping.
   */
  private function getRealMapping(string $form_id, string $form_identifier): array {
    $mappingFixtures = file_get_contents(__DIR__ . "/../../../src/Mapper/Mappings/$form_id/$form_identifier.json");
    return json_decode($mappingFixtures, TRUE);
  }

}
