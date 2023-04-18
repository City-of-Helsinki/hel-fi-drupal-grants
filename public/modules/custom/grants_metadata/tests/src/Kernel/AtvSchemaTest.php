<?php

namespace Drupal\Tests\grants_metadata\Kernel;

use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_metadata\TypedData\Definition\YleisavustusHakemusDefinition;
use Drupal\webform\Entity\Webform;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests AtvSchema class.
 *
 * @covers DefaultClass \Drupal\grants_metadata\AtvSchema
 * @group grants_metadata
 */
class AtvSchemaTest extends KernelTestBase {
  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  /**
   * Protected static $modules = [
   * Drupal modules
   * 'field', // For webform
   * 'user', // For webform
   * Contribs from drupal.org
   * 'webform',
   *
   * Test modules
   * 'grants_metadata_test_webforms',
   * ];
   */
  protected static $modules = [
    // Drupal modules.
    'field',
    'user',
    'file',
    'node',
    // Contribs from drupal.org.
    'webform',
    'openid_connect',
    // Contrib hel.fi modules.
    'helfi_audit_log',
    'helfi_helsinki_profiili',
    'helfi_atv',
    'helfi_api_base',
    'helfi_yjdh',
    // Project modules.
    'grants_metadata',
    'grants_handler',
    'grants_profile',
    // Test modules.
    'grants_metadata_test_webforms',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Basis for installing webform.
    $this->installSchema('webform', ['webform']);
    // Install test webforms.
    $this->installConfig(['grants_metadata_test_webforms']);
  }

  /**
   *
   */
  public static function loadWebform(string $webformId) {
    return Webform::load($webformId);
  }

  /**
   *
   */
  public static function createSchema(): AtvSchema {
    $logger = \Drupal::service('logger.factory');
    $manager = \Drupal::typedDataManager();
    $schema = new AtvSchema($manager, $logger);
    $schema->setSchema('/app/conf/tietoliikennesanoma_schema.json');
    return $schema;
  }

  /**
   *
   */
  public static function loadSubmissionData($formName): array {
    $json = json_decode(file_get_contents(__DIR__ . "/../../data/${formName}.data.json"), TRUE);
    return $json;
  }

  /**
   * Get typed data object for webform data.
   *
   * This is ripped off from ApplicationHandler class.
   *
   * @param array $submittedFormData
   *   Form data.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *   Typed data with values set.
   */
  public static function webformToTypedData(
    array $submittedFormData
  ): TypedDataInterface {

    // Datatype plugin requires the module enablation.
    $dataDefinition = YleisavustusHakemusDefinition::create('grants_metadata_yleisavustushakemus');
    $typeManager = $dataDefinition->getTypedDataManager();
    $applicationData = $typeManager->create($dataDefinition);

    $applicationData->setValue($submittedFormData);

    return $applicationData;
  }

  protected function assertDocumentField($document, string $arrayName, int $index, string $fieldName, $fieldValue) {
    $arrayOfFieldData = $document['compensation'][$arrayName][$index];
    $this->assertDocumentFieldArray($arrayOfFieldData, $fieldName, $fieldValue);
  }

  protected function assertDocumentCompositeField($document, string $arrayName, $index, $compositeIndex, string $fieldName, $fieldValue) {
    $arrayOfFieldData = $document['compensation'][$arrayName][$index][$compositeIndex];
    $this->assertDocumentFieldArray($arrayOfFieldData, $fieldName, $fieldValue);
  }

  protected function assertDocumentFieldArray($arrayOfFieldData, string $fieldName, $fieldValue) {
    $this->assertArrayHasKey('ID', $arrayOfFieldData);
    $this->assertArrayHasKey('value', $arrayOfFieldData);
    $this->assertArrayHasKey('valueType', $arrayOfFieldData);
    $this->assertArrayHasKey('label', $arrayOfFieldData);

    $this->assertEquals($fieldName, $arrayOfFieldData['ID']);
    $this->assertEquals($fieldValue, $arrayOfFieldData['value']);
  }

  /**
   * @covers ::__construct
   * @covers ::getOrigin
   * @covers ::getMessage
   * @covers ::isValid
   * @covers \Drupal\grants_metadata\AuditLogEvent::__construct
   */
  public function testYleisAvustusHakemus() : void {
    $schema = self::createSchema();
    $webform = self::loadWebform('yleisavustushakemus');
    $pages = [
      "1_hakijan_tiedot" => [
        "#title" => "1. Applicant details",
        "#prev_button_label" => "< Previous",
        "#next_button_label" => "Next >",
        "#type" => "page",
        "#access" => TRUE,
      ],
      "2_avustustiedot" => [
        "#title" => "2. Grant details",
        "#prev_button_label" => "< Previous",
        "#next_button_label" => "Next >",
        "#type" => "page",
        "#access" => TRUE,
      ],
      "3_yhteison_tiedot" => [
        "#title" => "3. Activities of the community",
        "#prev_button_label" => "< Previous",
        "#next_button_label" => "Next >",
        "#type" => "page",
        "#access" => TRUE,
      ],
      "lisatiedot_ja_liitteet" => [
        "#title" => "4. Additional information and appendices",
        "#type" => "page",
        "#access" => TRUE,
      ],
    ];
    $this->assertNotNull($webform);
    $submissionData = self::loadSubmissionData('yleisavustushakemus');
    $typedData = self::webformToTypedData($submissionData);
    // Run the actual data conversion
    $document = $schema->_typedDataToDocumentContent($typedData, $webform, $pages);
    // Applicant info
    $this->assertDocumentField($document, 'applicantInfoArray', 0, 'applicantType', 'registered_community');
    $this->assertDocumentField($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2'); 
    $this->assertDocumentField($document, 'applicantInfoArray', 2, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentField($document, 'applicantInfoArray', 3, 'communityOfficialNameShort', 'AE'); 
    $this->assertDocumentField($document, 'applicantInfoArray', 4, 'registrationDate', '2006-05-10T00:00:00'); 
    $this->assertDocumentField($document, 'applicantInfoArray', 5, 'foundingYear', '1337'); 
    $this->assertDocumentField($document, 'applicantInfoArray', 6, 'home', 'VOIKKAA');
    $this->assertDocumentField($document, 'applicantInfoArray', 7, 'homePage', 'arieerola.example.com'); 
    $this->assertDocumentField($document, 'applicantInfoArray', 8, 'email', 'ari.eerola@example.com'); 
    
    // Applicant officials
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 0, 'name', 'Ari Eerola');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 1, 'role', '3');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 2, 'email', 'ari.eerola@example.com');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 3, 'phone', '0501234567');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 0, 'name', 'Eero Arila');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 1, 'role', '3');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 2, 'email', 'eero.arila@example.com');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 3, 'phone', '0507654321');
    // Contact Info and Address 
    $this->assertDocumentField($document, 'currentAddressInfoArray', 0, 'contactPerson', 'Eero Arila');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 1, 'phoneNumber', '0507654321'); 
    $this->assertDocumentField($document, 'currentAddressInfoArray', 2, 'street', 'Testitie 1');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 3, 'city', 'Testilä'); 
    $this->assertDocumentField($document, 'currentAddressInfoArray', 4, 'postCode', '00100'); 
    $this->assertDocumentField($document, 'currentAddressInfoArray', 5, 'country', 'Suomi'); 
    // Application Info
    $this->assertDocumentField($document, 'applicationInfoArray', 0, 'applicationNumber', 'GRANTS-LOCALPAK-KASKOYLEIS-00000019');
    $this->assertDocumentField($document, 'applicationInfoArray', 1, 'status', 'DRAFT'); 
    $this->assertDocumentField($document, 'applicationInfoArray', 2, 'actingYear', '2023');
    // compensationInfo
    $this->assertDocumentCompositeField($document, 'compensationInfo', 'generalInfoArray', 0, 'purpose', '');
    $this->assertDocumentCompositeField($document, 'compensationInfo', 'generalInfoArray', 1, 'compensationPreviousYear', '');
    $this->assertDocumentCompositeField($document, 'compensationInfo', 'generalInfoArray', 2, 'explanation', '');
    // Handle subventions.
    $arrayOfFieldData = $document['compensation']['compensationInfo']['compensationArray'][0][0];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'subventionType', '1');
    $arrayOfFieldData = $document['compensation']['compensationInfo']['compensationArray'][0][1];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'amount', '0');
    $arrayOfFieldData = $document['compensation']['compensationInfo']['compensationArray'][1][0];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'subventionType', '5');
    $arrayOfFieldData = $document['compensation']['compensationInfo']['compensationArray'][1][1];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'amount', '0');
    $arrayOfFieldData = $document['compensation']['compensationInfo']['compensationArray'][2][0];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'subventionType', '36');
    $arrayOfFieldData = $document['compensation']['compensationInfo']['compensationArray'][2][1];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'amount', '0');

    // bankAccountArray
    $this->assertDocumentField($document, 'bankAccountArray', 0, 'accountNumber', 'FI21 1234 5600 0007 85');

    // benefitsInfoArray
    $this->assertDocumentField($document, 'benefitsInfoArray', 0, 'loans', '13');
    $this->assertDocumentField($document, 'benefitsInfoArray', 1, 'premises', '13');
    // activitiesInfoArray
    $this->assertDocumentField($document, 'activitiesInfoArray', 0, 'feePerson', '10');
    $this->assertDocumentField($document, 'activitiesInfoArray', 1, 'feeCommunity', '200'); 
    $this->assertDocumentField($document, 'activitiesInfoArray', 2, 'membersApplicantPersonLocal', '100');
    $this->assertDocumentField($document, 'activitiesInfoArray', 3, 'membersApplicantPersonGlobal', '150'); 
    $this->assertDocumentField($document, 'activitiesInfoArray', 4, 'membersApplicantCommunityLocal', '10'); 
    $this->assertDocumentField($document, 'activitiesInfoArray', 5, 'membersApplicantCommunityGlobal', '15'); 
    $this->assertDocumentField($document, 'activitiesInfoArray', 6, 'communityPracticesBusiness', '');
  }
}
