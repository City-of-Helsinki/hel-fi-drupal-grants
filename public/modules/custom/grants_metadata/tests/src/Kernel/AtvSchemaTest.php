<?php

namespace Drupal\Tests\grants_metadata\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_metadata\TypedData\Definition\KaskoYleisavustusDefinition;
use Drupal\grants_metadata\TypedData\Definition\KuvaProjektiDefinition;
use Drupal\grants_metadata\TypedData\Definition\KuvaToimintaDefinition;
use Drupal\grants_metadata\TypedData\Definition\LiikuntaTapahtumaDefinition;
use Drupal\grants_metadata\TypedData\Definition\LiikuntaTilankayttoDefinition;
use Drupal\grants_metadata\TypedData\Definition\YleisavustusHakemusDefinition;
use Drupal\grants_metadata_test_webforms\TypedData\Definition\FailedDataDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Tests AtvSchema class.
 *
 * @covers \Drupal\grants_metadata\AtvSchema
 * @group grants_metadata
 */
class AtvSchemaTest extends KernelTestBase implements ServiceModifierInterface {
  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    // Drupal modules.
    'field',
    'user',
    'file',
    'node',
    'system',
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
    'grants_applicant_info',
    'grants_budget_components',
    'grants_club_section',
    'grants_metadata',
    'grants_handler',
    'grants_premises',
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
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container
      ->getDefinition('grants_profile.service')
      ->setClass('Drupal\\grants_metadata_test_webforms\\GrantsProfileServiceTest');
  }

  /**
   * Load webform based on given id.
   */
  public static function loadWebform(string $webformId) {
    return Webform::load($webformId);
  }

  /**
   * Create ATV Schema instance.
   */
  public static function createSchema(): AtvSchema {
    $logger = \Drupal::service('logger.factory');
    $manager = \Drupal::typedDataManager();
    $schema = new AtvSchema($manager, $logger);
    // Use relative path. It works in all environments.
    $schemaPath = __DIR__ . "/../../../../../../../conf/tietoliikennesanoma_schema.json";
    $schema->setSchema($schemaPath);
    return $schema;
  }

  /**
   * Load test data from data directory.
   */
  public static function loadSubmissionData($formName): array {
    $filePath = __DIR__ . "/../../data/${formName}.data.json";
    return json_decode(file_get_contents($filePath), TRUE);
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
  public static function webformToTypedData(array $submittedFormData, string $formId): TypedDataInterface {

    // Datatype plugin requires the module enablation.
    switch ($formId) {
      case 'yleisavustushakemus':
        $dataDefinition = YleisavustusHakemusDefinition::create('grants_metadata_yleisavustushakemus');
        break;

      case 'kasvatus_ja_koulutus_yleisavustu':
        $dataDefinition = KaskoYleisavustusDefinition::create('grants_metadata_kaskoyleis');
        break;

      case 'kuva_projekti':
        $dataDefinition = KuvaProjektiDefinition::create('grants_metadata_kaskoyleis');
        break;

      case 'liikunta_tapahtuma':
        $dataDefinition = LiikuntaTapahtumaDefinition::create('grants_metadata_liikuntatapahtuma');
        break;

      case 'kuva_toiminta':
        $dataDefinition = KuvaToimintaDefinition::create('grants_metadata_kuvatoiminta');
        break;

      case 'liikunta_toiminta_ja_tilankaytto':
        $dataDefinition = LiikuntaTilankayttoDefinition::create('grants_metadata_kuvatoiminta');
        break;

      case 'failed':
        $dataDefinition = FailedDataDefinition::create('grants_metadata_yleisavustushakemus');
        break;

      default:
        throw new \Exception('Unknown form id');
    }

    $typeManager = $dataDefinition->getTypedDataManager();
    $applicationData = $typeManager->create($dataDefinition);

    $applicationData->setValue($submittedFormData);

    return $applicationData;
  }

  /**
   * Helper function to fetch the given field from document.
   */
  protected function assertDocumentFieldAtLevelTwo(
    $document,
    string $arrayName,
    int $index,
    string $fieldName,
    $fieldValue,
    $skipMetaChecks = FALSE) {
    $arrayOfFieldData = $document['compensation'][$arrayName][$index];
    $this->assertDocumentFieldArray($arrayOfFieldData, $fieldName, $fieldValue, $skipMetaChecks);
  }

  /**
   * Helper function to fetch the given composite field from document.
   */
  protected function assertDocumentFieldAtLevelThree($document, string $arrayName, $index, $compositeIndex, string $fieldName, $fieldValue, $skipMetaChecks = FALSE) {
    $arrayOfFieldData = $document['compensation'][$arrayName][$index][$compositeIndex];
    $this->assertDocumentFieldArray($arrayOfFieldData, $fieldName, $fieldValue, $skipMetaChecks);
  }

  /**
   * Helper function to fetch given composite array field from document.
   */
  protected function assertDocumentFieldAtLevelFour(
    $document,
    string $arrayName,
    $index,
    $compositeArrayIndex,
    $compositeIndex,
    string $fieldName,
    $fieldValue,
    $skipMetaChecks = FALSE
    ) {
    $arrayOfFieldData = $document['compensation'][$arrayName][$index][$compositeArrayIndex][$compositeIndex];
    $this->assertDocumentFieldArray($arrayOfFieldData, $fieldName, $fieldValue, $skipMetaChecks);
  }

  /**
   * Helper function to make asserions for a field in document.
   */
  protected function assertDocumentFieldArray($arrayOfFieldData, string $fieldName, $fieldValue, $skipMetaChecks = FALSE) {
    $this->assertArrayHasKey('ID', $arrayOfFieldData);
    $this->assertArrayHasKey('value', $arrayOfFieldData);
    $this->assertArrayHasKey('valueType', $arrayOfFieldData);
    $this->assertArrayHasKey('label', $arrayOfFieldData);
    $this->assertArrayHasKey('meta', $arrayOfFieldData);

    $this->assertEquals($fieldName, $arrayOfFieldData['ID']);
    $this->assertEquals($fieldValue, $arrayOfFieldData['value']);
    if ($skipMetaChecks) {
      return;
    }
    $meta = json_decode($arrayOfFieldData['meta'], TRUE);
    $this->assertArrayHasKey('page', $meta);
    $this->assertArrayHasKey('section', $meta);
    $this->assertArrayHasKey('element', $meta);
    $this->assertTrue(isset($meta['element']['hidden']));

  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function testYleisAvustusHakemus() : void {
    $schema = self::createSchema();
    $webform = self::loadWebform('yleisavustushakemus');
    $this->initSession();
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $submissionData = self::loadSubmissionData('yleisavustushakemus');
    $typedData = self::webformToTypedData($submissionData, 'yleisavustushakemus');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 0, 'applicantType', '2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 2, 'registrationDate', '2006-05-10T00:00:00.000+00:00');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 3, 'foundingYear', '1337');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 4, 'home', 'VOIKKAA');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 5, 'homePage', 'arieerola.example.com');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 6, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 7, 'communityOfficialNameShort', 'AE');

    // Applicant officials.
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 0, 'name', 'Ari Eerola');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 1, 'role', '3');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 2, 'email', 'ari.eerola@example.com');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 3, 'phone', '0501234567');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 1, 0, 'name', 'Eero Arila');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 1, 1, 'role', '3');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 1, 2, 'email', 'eero.arila@example.com');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 1, 3, 'phone', '0507654321');
    // Contact Info and Address.
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 0, 'contactPerson', 'Eero Arila');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 1, 'phoneNumber', '0507654321');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 2, 'street', 'Testitie 1');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 3, 'city', 'Testilä');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 4, 'postCode', '00100');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 5, 'country', 'Suomi');
    // Application Info.
    $this->assertDocumentFieldAtLevelTwo($document, 'applicationInfoArray', 0, 'applicationNumber', 'GRANTS-LOCALPAK-ECONOMICGRANTAPPLICATION-00000001');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicationInfoArray', 1, 'status', 'DRAFT');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicationInfoArray', 2, 'actingYear', '2023');
    // compensationInfo.
    $this->assertDocumentFieldAtLevelThree($document, 'compensationInfo', 'generalInfoArray', 0, 'compensationPreviousYear', '');
    $this->assertDocumentFieldAtLevelThree($document, 'compensationInfo', 'generalInfoArray', 1, 'totalAmount', '0', TRUE);
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

    // bankAccountArray.
    $this->assertDocumentFieldAtLevelTwo($document, 'bankAccountArray', 0, 'accountNumber', 'FI21 1234 5600 0007 85');

    // benefitsInfoArray.
    $this->assertDocumentFieldAtLevelTwo($document, 'benefitsInfoArray', 0, 'loans', '13');
    $this->assertDocumentFieldAtLevelTwo($document, 'benefitsInfoArray', 1, 'premises', '13');
    // activitiesInfoArray.
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 0, 'businessPurpose', 'Massin teko');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 1, 'membersApplicantPersonLocal', '100');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 2, 'membersApplicantPersonGlobal', '150');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 3, 'membersApplicantCommunityLocal', '10');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 4, 'membersApplicantCommunityGlobal', '15');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 5, 'feePerson', '10');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 6, 'feeCommunity', '200');

    // Attachment info lives outside compensation array.
    $attachmentOne = $document['attachmentsInfo']['attachmentsArray'][0];
    $this->assertCount(6, $attachmentOne);
    $arrayOfFieldData = $attachmentOne[0];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'description', 'Yhteisön säännöt (uusi hakija tai säännöt muuttuneet)');
    $arrayOfFieldData = $attachmentOne[1];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'fileName', 'truck_clipart_15144.jpg');
    $arrayOfFieldData = $attachmentOne[2];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'fileType', '7');
    $arrayOfFieldData = $attachmentOne[3];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'integrationID', '/LOCAL/v1/documents/4f3d41b8-e133-4ac7-b31a-9ece0aeba114/attachments/7657/');
    $arrayOfFieldData = $attachmentOne[4];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'isDeliveredLater', 'false');
    $arrayOfFieldData = $attachmentOne[5];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'isIncludedInOtherFile', 'false');

    $attachmentTwo = $document['attachmentsInfo']['attachmentsArray'][1];
    $this->assertCount(5, $attachmentTwo);
    $arrayOfFieldData = $attachmentTwo[0];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'description', 'Toimintakertomus');
    // We are also testing that there is no file name data.
    $arrayOfFieldData = $attachmentTwo[1];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'fileType', '7');
    $arrayOfFieldData = $attachmentTwo[2];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'integrationID', '');
    $arrayOfFieldData = $attachmentTwo[3];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'isDeliveredLater', 'false');
    $arrayOfFieldData = $attachmentTwo[4];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'isIncludedInOtherFile', 'true');

  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function testYleisAvustusHakemusWithFailingDefinition() : void {
    $schema = self::createSchema();
    $webform = self::loadWebform('yleisavustushakemus');
    $this->initSession();
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $submissionData = self::loadSubmissionData('yleisavustushakemus');
    $typedData = self::webformToTypedData($submissionData, 'failed');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // activitiesInfoArray.
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 0, 'businessPurpose', 'Massin teko');
    $level6Exists = isset($document['compensation']['activitiesInfoArray']['level3']);
    $this->assertEquals(FALSE, $level6Exists);
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 1, 'membersApplicantPersonGlobal', '150');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 2, 'membersApplicantCommunityLocal', '10');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 3, 'membersApplicantCommunityGlobal', '15');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 4, 'feePerson', '10');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 5, 'feeCommunity', '200');

  }

  /**
   * Create session for GrantsProfileService.
   */
  protected function initSession($role = 'registered_community'): void {
    $session = new Session();
    \Drupal::service('grants_profile.cache')->setSession($session);
    \Drupal::service('grants_profile.service')->setApplicantType($role);
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKaskoYleisAvustusHakemus() : void {
    $schema = self::createSchema();
    $webform = self::loadWebform('kasvatus_ja_koulutus_yleisavustu');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('kasvatus_ja_koulutus_yleisavustu');
    $typedData = self::webformToTypedData($submissionData, 'kasvatus_ja_koulutus_yleisavustu');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 0, 'applicantType', '2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 2, 'registrationDate', '2006-05-10T00:00:00.000+00:00');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 3, 'foundingYear', '1337');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 4, 'home', 'VOIKKAA');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 5, 'homePage', 'arieerola.example.com');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 6, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 7, 'communityOfficialNameShort', 'AE');

    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 8, 'email', 'ari.eerola@example.com');

    // Applicant officials.
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 0, 'name', 'Ari Eerola');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 1, 'role', '3');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 2, 'email', 'ari.eerola@example.com');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 3, 'phone', '0501234567');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 1, 0, 'name', 'Eero Arila');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 1, 1, 'role', '3');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 1, 2, 'email', 'eero.arila@example.com');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 1, 3, 'phone', '0507654321');
    // Contact Info and Address.
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 0, 'contactPerson', 'Eero Arila');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 1, 'phoneNumber', '0507654321');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 2, 'street', 'Testitie 1');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 3, 'city', 'Testilä');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 4, 'postCode', '00100');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 5, 'country', 'Suomi');
    // Application Info.
    $this->assertDocumentFieldAtLevelTwo($document, 'applicationInfoArray', 0, 'applicationNumber', 'GRANTS-LOCALPAK-KASKOYLEIS-00000001');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicationInfoArray', 1, 'status', 'DRAFT');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicationInfoArray', 2, 'actingYear', '2023');
    // compensationInfo.
    $this->assertDocumentFieldAtLevelThree($document, 'compensationInfo', 'generalInfoArray', 0, 'compensationPreviousYear', '');
    $this->assertDocumentFieldAtLevelThree($document, 'compensationInfo', 'generalInfoArray', 1, 'totalAmount', '0', TRUE);
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

    // bankAccountArray.
    $this->assertDocumentFieldAtLevelTwo($document, 'bankAccountArray', 0, 'accountNumber', 'FI21 1234 5600 0007 85');

    // benefitsInfoArray.
    $this->assertDocumentFieldAtLevelTwo($document, 'benefitsInfoArray', 0, 'loans', '13');
    $this->assertDocumentFieldAtLevelTwo($document, 'benefitsInfoArray', 1, 'premises', '13');
    // activitiesInfoArray.
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 0, 'businessPurpose', 'Massin teko');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 1, 'membersApplicantPersonLocal', '100');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 2, 'membersApplicantPersonGlobal', '150');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 3, 'membersApplicantCommunityLocal', '10');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 4, 'membersApplicantCommunityGlobal', '15');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 5, 'feePerson', '10');
    $this->assertDocumentFieldAtLevelTwo($document, 'activitiesInfoArray', 6, 'feeCommunity', '200');
  }

  /**
   * Test kuvaprojekti with registered community and subventions over 5000.
   *
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKuvaProjektiHakemusRegistered() : void {
    $schema = self::createSchema();
    $webform = self::loadWebform('kuva_projekti');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('kuva_projekti');
    $typedData = self::webformToTypedData($submissionData, 'kuva_projekti');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 0, 'applicantType', '2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 2, 'registrationDate', '2006-05-10T00:00:00.000+00:00');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 3, 'foundingYear', '1345');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 4, 'home', 'VOIKKAA');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 5, 'homePage', 'arieerola.example.com');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 6, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 7, 'communityOfficialNameShort', 'AE');

    // Other compensation.
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 0, 'issuer', '1');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 1, 'issuerName', 'Valtio');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 2, 'year', '2020');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 3, 'amount', '42');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 4, 'purpose', 'Selvitä elämän tarkoitus');

    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 0, 'issuer', '5');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 1, 'issuerName', 'Suihkulähde');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 2, 'year', '2021');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 3, 'amount', '69');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 4, 'purpose', 'Tulla märäksi');

    // Handle activities.
    $this->assertDocumentFieldAtLevelThree($document, 'activityBasisInfo', 'activityBasisArray', 0, 'toiminta_taiteelliset_lahtokohdat', '');
    $this->assertDocumentFieldAtLevelThree($document, 'activityBasisInfo', 'activityBasisArray', 1, 'toiminta_tasa_arvo', '');
    $this->assertDocumentFieldAtLevelThree($document, 'activityBasisInfo', 'activityBasisArray', 2, 'toiminta_saavutettavuus', '');
    $this->assertDocumentFieldAtLevelThree($document, 'activityBasisInfo', 'activityBasisArray', 3, 'toiminta_yhteisollisyys', '');
    $this->assertDocumentFieldAtLevelThree($document, 'activityBasisInfo', 'activityBasisArray', 4, 'toiminta_kohderyhmat', '');
    $this->assertDocumentFieldAtLevelThree($document, 'activityBasisInfo', 'activityBasisArray', 5, 'toiminta_ammattimaisuus', '');
    $this->assertDocumentFieldAtLevelThree($document, 'activityBasisInfo', 'activityBasisArray', 6, 'toiminta_ekologisuus', '');
    $this->assertDocumentFieldAtLevelThree($document, 'activityBasisInfo', 'activityBasisArray', 7, 'toiminta_yhteistyokumppanit', '');

  }

  /**
   * Test kuvaprojekti with unregistered community and subventions under 5000.
   *
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKuvaProjektiHakemusUnregistered() : void {
    $schema = self::createSchema();
    $webform = self::loadWebform('kuva_projekti');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession('unregistered_community');
    $submissionData = self::loadSubmissionData('kuva_projekti.unregistered');
    $typedData = self::webformToTypedData($submissionData, 'kuva_projekti');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 0, 'applicantType', '1');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 1, 'communityOfficialName', 'Pöpilä');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 2, 'email', 'mailfromprofile@example.com');

    // Address info.
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 0, 'street', 'Kaukotie 5');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 1, 'city', 'Helsinki');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 2, 'postCode', '01300');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 4, 'contactPerson', 'Nordea Demo');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 5, 'phoneNumber', '+35812121212121212121');
    // Applicant officials array.
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 0, 'name', 'Veijo Official');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 1, 'role', '0');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 2, 'email', 'official@example.com');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 3, 'phone', '+35812121212121212121');
    // bankAccountArray.
    $this->assertDocumentFieldAtLevelTwo($document, 'bankAccountArray', 0, 'accountOwnerName', 'Wii Wii');
    $this->assertDocumentFieldAtLevelTwo($document, 'bankAccountArray', 1, 'socialSecurityNumber', '290492-932R');
    $this->assertDocumentFieldAtLevelTwo($document, 'bankAccountArray', 2, 'accountNumber', 'FI2523629411259741');

    // Other compensation.
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 0, 'issuer', '1');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 1, 'issuerName', 'Valtio');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 2, 'year', '2020');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 3, 'amount', '42');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 4, 'purpose', 'Selvitä elämän tarkoitus');

    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 0, 'issuer', '5');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 1, 'issuerName', 'Suihkulähde');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 2, 'year', '2021');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 3, 'amount', '69');
    $this->assertDocumentFieldAtLevelFour($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 4, 'purpose', 'Tulla märäksi');

    // Handle activities.
    $this->assertDocumentFieldAtLevelThree($document, 'activityBasisInfo', 'activityBasisArray', 0, 'toiminta_kohderyhmat', '');
    $this->assertDocumentFieldAtLevelThree($document, 'activityBasisInfo', 'activityBasisArray', 1, 'toiminta_yhteistyokumppanit', '');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKuvaToimintaHakemus() {
    $schema = self::createSchema();
    $webform = self::loadWebform('kuva_toiminta');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('kuva_toiminta');
    $typedData = self::webformToTypedData($submissionData, 'kuva_toiminta');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 0, 'applicantType', '2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 2, 'registrationDate', '10.05.2006');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 3, 'foundingYear', '1345');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 4, 'home', 'VOIKKAA');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 5, 'homePage', 'arieerola.example.com');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 6, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 7, 'communityOfficialNameShort', 'AE');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 8, 'email', 'ari.eerola@example.com');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testLiikuntaTapahtumaHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('liikunta_tapahtuma');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('liikunta_tapahtuma');
    $typedData = self::webformToTypedData($submissionData, 'liikunta_tapahtuma');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 0, 'applicantType', '2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 2, 'registrationDate', '10.05.2006');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 3, 'foundingYear', '1345');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 4, 'home', 'VOIKKAA');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 5, 'homePage', 'yle.fi');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 6, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 7, 'communityOfficialNameShort', 'AE');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 8, 'email', 'lokaali@testi.fi');

    $arrayOfFieldData = $document['compensation']['compensationInfo']['compensationArray'][0][0];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'subventionType', '37');
    $arrayOfFieldData = $document['compensation']['compensationInfo']['compensationArray'][0][1];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'amount', '123');

    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 0, 'name', 'Ari');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 1, 'role', '3');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 2, 'email', 'ari@example.com');
    $this->assertDocumentFieldAtLevelThree($document, 'applicantOfficialsArray', 0, 3, 'phone', '234567');

    // Contact Info and Address.
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 0, 'contactPerson', 'Testaaja');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 1, 'phoneNumber', '0501234567');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 2, 'street', 'Testiti 1');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 3, 'city', 'Testi');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 4, 'postCode', '00100');
    $this->assertDocumentFieldAtLevelTwo($document, 'currentAddressInfoArray', 5, 'country', 'Suomi');

    // bankAccountArray.
    $this->assertDocumentFieldAtLevelTwo($document, 'bankAccountArray', 0, 'accountNumber', 'FI6044581558982351');

    // participantsArray.
    $this->assertDocumentFieldAtLevelTwo($document, 'participantsArray', 0, 'adultsMale', '11');
    $this->assertDocumentFieldAtLevelTwo($document, 'participantsArray', 1, 'adultsFemale', '22');
    $this->assertDocumentFieldAtLevelTwo($document, 'participantsArray', 2, 'adultsOther', '33');
    $this->assertDocumentFieldAtLevelTwo($document, 'participantsArray', 3, 'juniorsMale', '44');
    $this->assertDocumentFieldAtLevelTwo($document, 'participantsArray', 4, 'juniorsFemale', '55');
    $this->assertDocumentFieldAtLevelTwo($document, 'participantsArray', 5, 'juniorsOther', '66');

    // eventInfoArray.
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 0, 'eventName', 'Event information description');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 1, 'eventTargetGroup', 'Drupal developers');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 2, 'eventPlace', 'Work from home');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 3, 'eventContent', 'Plenty of coffee');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 4, 'eventBegin', '2023-09-14');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 5, 'eventEnd', '2023-09-15');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 6, 'isEventEquality', 'true');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 7, 'eventEqualityText', 'Coffee');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 8, 'isEventCommunal', 'false');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 9, 'isEventEnvironment', 'true');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 10, 'eventEnvironmentText', 'More coffee');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 11, 'isEventNewPeopleActivating', 'false');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 12, 'isEventWorkdayActivating', 'true');
    $this->assertDocumentFieldAtLevelThree($document, 'eventInfoArray', 0, 13, 'eventWorkdayActivatingText', 'You guessed it, coffee!');

    // Budget info.
    $budgetOtherIncome = $document['compensation']['budgetInfo']['incomeGroupsArrayStatic'][0]['otherIncomeRowsArrayStatic'][0];
    $this->assertDocumentFieldArray($budgetOtherIncome, 'budget_other_income_0', '12345');
    $this->assertEquals('Sell coffee', $budgetOtherIncome['label']);

    $budgetOtherCost = $document['compensation']['budgetInfo']['costGroupsArrayStatic'][0]['otherCostRowsArrayStatic'][0];
    $this->assertDocumentFieldArray($budgetOtherCost, 'budget_other_cost_0', '54321');
    $this->assertEquals('Buy coffee', $budgetOtherCost['label']);

    $this->assertEquals(TRUE, $document['formUpdate']);
    $this->assertCount(1, $document['events']);
    $this->assertCount(8, $document['events'][0]);
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testLiikuntaToimintaHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('liikunta_toiminta_ja_tilankaytto');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('liikunta_toiminta_ja_tilankaytto');
    $typedData = self::webformToTypedData($submissionData, 'liikunta_toiminta_ja_tilankaytto');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 0, 'applicantType', '2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 2, 'registrationDate', '10.05.2006');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 3, 'foundingYear', '1345');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 4, 'home', 'VOIKKAA');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 5, 'homePage', 'arieerola.example.com');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 6, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentFieldAtLevelTwo($document, 'applicantInfoArray', 7, 'communityOfficialNameShort', 'AE');
    // Test a field with depth of five.
    $rentCostsHours = $document['compensation']['compensationInfo']['premisesCompensation']['rentCostsArray'][0];
    $this->assertDocumentFieldArray($rentCostsHours, 'rentCostsHours', '123');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testAttachments() : void {
    $this->initSession();
    $dataDefinition = YleisavustusHakemusDefinition::create('grants_metadata_yleisavustushakemus');
    $submissionData = self::loadSubmissionData('yleisavustushakemus');
    $typeManager = $dataDefinition->getTypedDataManager();
    $applicationData = $typeManager->create($dataDefinition);

    $applicationData->setValue($submissionData);

    foreach ($applicationData as $field) {
      $definition = $field->getDataDefinition();
      $name = $field->getName();
      if ($name !== 'attachments') {
        continue;
      }
      $defaultValue = $definition->getSetting('defaultValue');
      $valueCallback = $definition->getSetting('valueCallback');
      $hiddenFields = $definition->getSetting('hiddenFields');
      foreach ($field as $item) {
        $propertyItem = $item->getValue();
        $itemDataDefinition = $item->getDataDefinition();
        $itemValueDefinitions = $itemDataDefinition->getPropertyDefinitions();
        foreach ($itemValueDefinitions as $itemName => $itemValueDefinition) {
          // Backup label.
          $label = $itemValueDefinition->getLabel();
          $hidden = in_array($itemName, $hiddenFields);
          $element = [
            'weight' => 1,
            'label' => $label,
            'hidden' => $hidden,
          ];
          $itemTypes = ATVSchema::getJsonTypeForDataType($itemValueDefinition);
          if (isset($propertyItem[$itemName])) {
            // What to do with empty values.
            $itemSkipEmpty = $itemValueDefinition->getSetting('skipEmptyValue');

            $itemValue = $propertyItem[$itemName];
            $itemValue = ATVSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
            // If no value and skip is setting, then skip.
            if (empty($itemValue) && $itemSkipEmpty === TRUE) {
              continue;
            }
            $metaData = ATVSchema::getMetaData(NULL, NULL, $element);
            if ($itemName == 'integrationID' || $itemName == 'fileType') {
              $this->assertEquals(TRUE, $metaData['element']['hidden']);
            }
            else {
              $this->assertEquals(FALSE, $metaData['element']['hidden']);
            }
          }
        }
      }
    }
  }

}
