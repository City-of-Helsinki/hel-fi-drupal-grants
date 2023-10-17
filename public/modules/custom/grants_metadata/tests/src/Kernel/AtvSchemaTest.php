<?php

namespace Drupal\Tests\grants_metadata\Kernel;

use Drupal\Component\Serialization\Json;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_metadata\TypedData\Definition\KaskoYleisavustusDefinition;
use Drupal\grants_metadata\TypedData\Definition\KuvaProjektiDefinition;
use Drupal\grants_metadata\TypedData\Definition\LiikuntaTapahtumaDefinition;
use Drupal\grants_metadata\TypedData\Definition\YleisavustusHakemusDefinition;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Tests AtvSchema class.
 *
 * @covers \Drupal\grants_metadata\AtvSchema
 * @group grants_metadata
 */
class AtvSchemaTest extends KernelTestBase {
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

      default:
        throw new \Exception('Unknown form id');
    }

    $typeManager = $dataDefinition->getTypedDataManager();
    $applicationData = $typeManager->create($dataDefinition);

    $applicationData->setValue($submittedFormData);

    return $applicationData;
  }

  /**
   * Helper function to return web form page structure.
   */
  protected function getPages($webform): array {
    /* If there ends up being different type of page structures this
     * can be extracted from webform data
     */
    $elements = $webform->getElementsDecoded();
    $pageIds = array_keys($elements);
    $pages = [];
    foreach ($pageIds as $pageId) {
      $pages[$pageId] = [
        "#title" => $elements[$pageId]["#title"],
      ];
    }
    return $pages;
  }

  /**
   * Helper function to fetch the given field from document.
   */
  protected function assertDocumentField($document, string $arrayName, int $index, string $fieldName, $fieldValue, $skipMetaChecks = FALSE) {
    $arrayOfFieldData = $document['compensation'][$arrayName][$index];
    $this->assertDocumentFieldArray($arrayOfFieldData, $fieldName, $fieldValue, $skipMetaChecks);
  }

  /**
   * Helper function to fetch the given composite field from document.
   */
  protected function assertDocumentCompositeField($document, string $arrayName, $index, $compositeIndex, string $fieldName, $fieldValue, $skipMetaChecks = FALSE) {
    $arrayOfFieldData = $document['compensation'][$arrayName][$index][$compositeIndex];
    $this->assertDocumentFieldArray($arrayOfFieldData, $fieldName, $fieldValue, $skipMetaChecks);
  }

  /**
   * Helper function to fetch given composite array field from document.
   */
  protected function assertDocumentCompositeArrayField($document, string $arrayName, $index, $compositeArrayIndex, $compositeIndex, string $fieldName, $fieldValue, $skipMetaChecks = FALSE) {
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
    $pages = self::getPages($webform);
    $submissionData = self::loadSubmissionData('yleisavustushakemus');
    $typedData = self::webformToTypedData($submissionData, 'yleisavustushakemus');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertDocumentField($document, 'applicantInfoArray', 0, 'applicantType', '2');
    $this->assertDocumentField($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2');
    $this->assertDocumentField($document, 'applicantInfoArray', 2, 'registrationDate', '2006-05-10T00:00:00.000+00:00');
    $this->assertDocumentField($document, 'applicantInfoArray', 3, 'foundingYear', '1337');
    $this->assertDocumentField($document, 'applicantInfoArray', 4, 'home', 'VOIKKAA');
    $this->assertDocumentField($document, 'applicantInfoArray', 5, 'homePage', 'arieerola.example.com');
    $this->assertDocumentField($document, 'applicantInfoArray', 6, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentField($document, 'applicantInfoArray', 7, 'communityOfficialNameShort', 'AE');

    // Applicant officials.
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 0, 'name', 'Ari Eerola');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 1, 'role', '3');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 2, 'email', 'ari.eerola@example.com');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 3, 'phone', '0501234567');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 0, 'name', 'Eero Arila');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 1, 'role', '3');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 2, 'email', 'eero.arila@example.com');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 3, 'phone', '0507654321');
    // Contact Info and Address.
    $this->assertDocumentField($document, 'currentAddressInfoArray', 0, 'contactPerson', 'Eero Arila');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 1, 'phoneNumber', '0507654321');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 2, 'street', 'Testitie 1');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 3, 'city', 'Testilä');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 4, 'postCode', '00100');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 5, 'country', 'Suomi');
    // Application Info.
    $this->assertDocumentField($document, 'applicationInfoArray', 0, 'applicationNumber', 'GRANTS-LOCALPAK-ECONOMICGRANTAPPLICATION-00000001');
    $this->assertDocumentField($document, 'applicationInfoArray', 1, 'status', 'DRAFT');
    $this->assertDocumentField($document, 'applicationInfoArray', 2, 'actingYear', '2023');
    // compensationInfo.
    $this->assertDocumentCompositeField($document, 'compensationInfo', 'generalInfoArray', 0, 'compensationPreviousYear', '');
    $this->assertDocumentCompositeField($document, 'compensationInfo', 'generalInfoArray', 1, 'totalAmount', '0', TRUE);
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
    $this->assertDocumentField($document, 'bankAccountArray', 0, 'accountNumber', 'FI21 1234 5600 0007 85');

    // benefitsInfoArray.
    $this->assertDocumentField($document, 'benefitsInfoArray', 0, 'loans', '13');
    $this->assertDocumentField($document, 'benefitsInfoArray', 1, 'premises', '13');
    // activitiesInfoArray.
    $this->assertDocumentField($document, 'activitiesInfoArray', 0, 'businessPurpose', 'Massin teko');
    $this->assertDocumentField($document, 'activitiesInfoArray', 1, 'membersApplicantPersonLocal', '100');
    $this->assertDocumentField($document, 'activitiesInfoArray', 2, 'membersApplicantPersonGlobal', '150');
    $this->assertDocumentField($document, 'activitiesInfoArray', 3, 'membersApplicantCommunityLocal', '10');
    $this->assertDocumentField($document, 'activitiesInfoArray', 4, 'membersApplicantCommunityGlobal', '15');
    $this->assertDocumentField($document, 'activitiesInfoArray', 5, 'feePerson', '10');
    $this->assertDocumentField($document, 'activitiesInfoArray', 6, 'feeCommunity', '200');

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
   * Create session for GrantsProfileService.
   */
  protected function initSession(): void {
    $session = new Session();
    \Drupal::service('grants_profile.service')->setSession($session);
    \Drupal::service('grants_profile.service')->setApplicantType('registered_community');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKaskoYleisAvustusHakemus() : void {
    $schema = self::createSchema();
    $webform = self::loadWebform('kasvatus_ja_koulutus_yleisavustu');
    $pages = self::getPages($webform);
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('kasvatus_ja_koulutus_yleisavustu');
    $typedData = self::webformToTypedData($submissionData, 'kasvatus_ja_koulutus_yleisavustu');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertDocumentField($document, 'applicantInfoArray', 0, 'applicantType', '2');
    $this->assertDocumentField($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2');
    $this->assertDocumentField($document, 'applicantInfoArray', 2, 'registrationDate', '2006-05-10T00:00:00.000+00:00');
    $this->assertDocumentField($document, 'applicantInfoArray', 3, 'foundingYear', '1337');
    $this->assertDocumentField($document, 'applicantInfoArray', 4, 'home', 'VOIKKAA');
    $this->assertDocumentField($document, 'applicantInfoArray', 5, 'homePage', 'arieerola.example.com');
    $this->assertDocumentField($document, 'applicantInfoArray', 6, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentField($document, 'applicantInfoArray', 7, 'communityOfficialNameShort', 'AE');

    $this->assertDocumentField($document, 'applicantInfoArray', 8, 'email', 'ari.eerola@example.com');

    // Applicant officials.
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 0, 'name', 'Ari Eerola');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 1, 'role', '3');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 2, 'email', 'ari.eerola@example.com');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 3, 'phone', '0501234567');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 0, 'name', 'Eero Arila');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 1, 'role', '3');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 2, 'email', 'eero.arila@example.com');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 1, 3, 'phone', '0507654321');
    // Contact Info and Address.
    $this->assertDocumentField($document, 'currentAddressInfoArray', 0, 'contactPerson', 'Eero Arila');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 1, 'phoneNumber', '0507654321');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 2, 'street', 'Testitie 1');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 3, 'city', 'Testilä');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 4, 'postCode', '00100');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 5, 'country', 'Suomi');
    // Application Info.
    $this->assertDocumentField($document, 'applicationInfoArray', 0, 'applicationNumber', 'GRANTS-LOCALPAK-KASKOYLEIS-00000001');
    $this->assertDocumentField($document, 'applicationInfoArray', 1, 'status', 'DRAFT');
    $this->assertDocumentField($document, 'applicationInfoArray', 2, 'actingYear', '2023');
    // compensationInfo.
    $this->assertDocumentCompositeField($document, 'compensationInfo', 'generalInfoArray', 0, 'compensationPreviousYear', '');
    $this->assertDocumentCompositeField($document, 'compensationInfo', 'generalInfoArray', 1, 'totalAmount', '0', TRUE);
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
    $this->assertDocumentField($document, 'bankAccountArray', 0, 'accountNumber', 'FI21 1234 5600 0007 85');

    // benefitsInfoArray.
    $this->assertDocumentField($document, 'benefitsInfoArray', 0, 'loans', '13');
    $this->assertDocumentField($document, 'benefitsInfoArray', 1, 'premises', '13');
    // activitiesInfoArray.
    $this->assertDocumentField($document, 'activitiesInfoArray', 0, 'businessPurpose', 'Massin teko');
    $this->assertDocumentField($document, 'activitiesInfoArray', 1, 'membersApplicantPersonLocal', '100');
    $this->assertDocumentField($document, 'activitiesInfoArray', 2, 'membersApplicantPersonGlobal', '150');
    $this->assertDocumentField($document, 'activitiesInfoArray', 3, 'membersApplicantCommunityLocal', '10');
    $this->assertDocumentField($document, 'activitiesInfoArray', 4, 'membersApplicantCommunityGlobal', '15');
    $this->assertDocumentField($document, 'activitiesInfoArray', 5, 'feePerson', '10');
    $this->assertDocumentField($document, 'activitiesInfoArray', 6, 'feeCommunity', '200');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKuvaProjektiHakemus() : void {
    $schema = self::createSchema();
    $webform = self::loadWebform('kuva_projekti');
    $pages = self::getPages($webform);
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('kuva_projekti');
    $typedData = self::webformToTypedData($submissionData, 'kuva_projekti');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    $this->assertDocumentField($document, 'applicantInfoArray', 0, 'applicantType', '2');
    $this->assertDocumentField($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2');
    $this->assertDocumentField($document, 'applicantInfoArray', 2, 'registrationDate', '2006-05-10T00:00:00.000+00:00');
    $this->assertDocumentField($document, 'applicantInfoArray', 3, 'foundingYear', '1345');
    $this->assertDocumentField($document, 'applicantInfoArray', 4, 'home', 'VOIKKAA');
    $this->assertDocumentField($document, 'applicantInfoArray', 5, 'homePage', 'arieerola.example.com');
    $this->assertDocumentField($document, 'applicantInfoArray', 6, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentField($document, 'applicantInfoArray', 7, 'communityOfficialNameShort', 'AE');

    // Other compensation.
    $this->assertDocumentCompositeArrayField($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 0, 'issuer', '1');
    $this->assertDocumentCompositeArrayField($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 1, 'issuerName', 'Valtio');
    $this->assertDocumentCompositeArrayField($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 2, 'year', '2020');
    $this->assertDocumentCompositeArrayField($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 3, 'amount', '42');
    $this->assertDocumentCompositeArrayField($document, 'otherCompensationsInfo', 'otherCompensationsArray', 0, 4, 'purpose', 'Selvitä elämän tarkoitus');

    $this->assertDocumentCompositeArrayField($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 0, 'issuer', '5');
    $this->assertDocumentCompositeArrayField($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 1, 'issuerName', 'Suihkulähde');
    $this->assertDocumentCompositeArrayField($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 2, 'year', '2021');
    $this->assertDocumentCompositeArrayField($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 3, 'amount', '69');
    $this->assertDocumentCompositeArrayField($document, 'otherCompensationsInfo', 'otherCompensationsArray', 1, 4, 'purpose', 'Tulla märäksi');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testLiikuntaTapahtumaHakemus() : void {
    $schema = self::createSchema();
    $webform = self::loadWebform('liikunta_tapahtuma');
    $pages = self::getPages($webform);
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('liikunta_tapahtuma');
    $typedData = self::webformToTypedData($submissionData, 'liikunta_tapahtuma');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    $this->assertDocumentField($document, 'applicantInfoArray', 0, 'applicantType', '2');
    $this->assertDocumentField($document, 'applicantInfoArray', 1, 'companyNumber', '2036583-2');
    $this->assertDocumentField($document, 'applicantInfoArray', 2, 'registrationDate', '10.05.2006');
    $this->assertDocumentField($document, 'applicantInfoArray', 3, 'foundingYear', '1345');
    $this->assertDocumentField($document, 'applicantInfoArray', 4, 'home', 'VOIKKAA');
    $this->assertDocumentField($document, 'applicantInfoArray', 5, 'homePage', 'yle.fi');
    $this->assertDocumentField($document, 'applicantInfoArray', 6, 'communityOfficialName', 'Maanrakennus Ari Eerola T:mi');
    $this->assertDocumentField($document, 'applicantInfoArray', 7, 'communityOfficialNameShort', 'AE');
    $this->assertDocumentField($document, 'applicantInfoArray', 8, 'email', 'lokaali@testi.fi');

    $arrayOfFieldData = $document['compensation']['compensationInfo']['compensationArray'][0][0];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'subventionType', '37');
    $arrayOfFieldData = $document['compensation']['compensationInfo']['compensationArray'][0][1];
    $this->assertDocumentFieldArray($arrayOfFieldData, 'amount', '123');

    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 0, 'name', 'Ari');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 1, 'role', '3');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 2, 'email', 'ari@example.com');
    $this->assertDocumentCompositeField($document, 'applicantOfficialsArray', 0, 3, 'phone', '234567');

    // Contact Info and Address.
    $this->assertDocumentField($document, 'currentAddressInfoArray', 0, 'contactPerson', 'Testaaja');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 1, 'phoneNumber', '0501234567');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 2, 'street', 'Testiti 1');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 3, 'city', 'Testi');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 4, 'postCode', '00100');
    $this->assertDocumentField($document, 'currentAddressInfoArray', 5, 'country', 'Suomi');

    // bankAccountArray.
    $this->assertDocumentField($document, 'bankAccountArray', 0, 'accountNumber', 'FI6044581558982351');

    // activitiesInfoArray.
    $this->assertDocumentField($document, 'activitiesInfoArray', 0, 'businessPurpose', 'Tuohen vuoleminen', TRUE);

    // participantsArray.
    $this->assertDocumentField($document, 'participantsArray', 0, 'adultsMale', '11');
    $this->assertDocumentField($document, 'participantsArray', 1, 'adultsFemale', '22');
    $this->assertDocumentField($document, 'participantsArray', 2, 'adultsOther', '33');
    $this->assertDocumentField($document, 'participantsArray', 3, 'juniorsMale', '44');
    $this->assertDocumentField($document, 'participantsArray', 4, 'juniorsFemale', '55');
    $this->assertDocumentField($document, 'participantsArray', 5, 'juniorsOther', '66');

    // eventInfoArray.
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 0, 'eventName', 'Event information description');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 1, 'eventTargetGroup', 'Drupal developers');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 2, 'eventPlace', 'Work from home');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 3, 'eventContent', 'Plenty of coffee');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 4, 'eventBegin', '2023-09-14');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 5, 'eventEnd', '2023-09-15');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 6, 'isEventEquality', 'true');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 7, 'eventEqualityText', 'Coffee');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 8, 'isEventCommunal', 'false');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 9, 'isEventEnvironment', 'true');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 10, 'eventEnvironmentText', 'More coffee');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 11, 'isEventNewPeopleActivating', 'false');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 12, 'isEventWorkdayActivating', 'true');
    $this->assertDocumentCompositeField($document, 'eventInfoArray', 0, 13, 'eventWorkdayActivatingText', 'You guessed it, coffee!');

    // Budget info.
    $budgetOtherIncome = $document['compensation']['budgetInfo']['incomeGroupsArrayStatic'][0]['otherIncomeRowsArrayStatic'][0];
    $this->assertDocumentFieldArray($budgetOtherIncome, 'budget_other_income_0', '12345');
    $this->assertEquals('Sell coffee', $budgetOtherIncome['label']);

    $budgetOtherCost = $document['compensation']['budgetInfo']['costGroupsArrayStatic'][0]['otherCostRowsArrayStatic'][0];
    $this->assertDocumentFieldArray($budgetOtherCost, 'budget_other_cost_0', '54321');
    $this->assertEquals('Buy coffee', $budgetOtherCost['label']);
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
      $propertyType = $definition->getDataType();
      $hiddenFields = $definition->getSetting('hiddenFields');
      foreach ($field as $itemIndex => $item) {
        $fieldValues = [];
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

            $idValue = $itemName;
            $valueArray = [
              'ID' => $idValue,
              'value' => $itemValue,
              'valueType' => $itemTypes['jsonType'],
              'label' => $label,
              'meta' => json_encode($metaData),
            ];
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

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::getPropertySchema
   */
  public function testGetPropertySchema() : void {

    // This data structure is a map of elements and their types.
    // The data structure is based on the data from the old getPropertySchema()
    // method before refactoring took place.
    $elementTypeMap = [
      "compensationArray" => "array",
      "email" => "object",
      "applicantOfficialsArray" => "array",
      "contactPerson" => "object",
      "phoneNumber" => "object",
      "street" => "object",
      "city" => "object",
      "postCode" => "object",
      "country" => "object",
      "applicationType" => "object",
      "applicationTypeID" => "object",
      "formTimeStamp" => "object",
      "createdFormTimeStamp" => "object",
      "submittedFormTimeStamp" => "object",
      "applicationNumber" => "object",
      "status" => "object",
      "actingYear" => "object",
      "accountNumber" => "object",
      "otherCompensationsArray" => "array",
      "otherAppliedCompensationsArray" => "array",
      "otherCompensationsTotal" => "object",
      "otherAppliedCompensationsTotal" => "object",
      "loans" => "object",
      "premises" => "object",
      "businessPurpose" => "object",
      "communityPracticesBusiness" => "object",
      "additionalInformation" => "string",
      "firstname" => "object",
      "lastname" => "object",
      "personID" => "string",
      "userID" => "string",
      "attachmentsArray" => "string",
      "extraInfo" => "string",
      "formUpdate" => "string",
      "statusUpdates" => "string",
      "events" => "string",
      "messages" => "string",
      "membersApplicantPersonLocal" => "object",
      "membersApplicantPersonGlobal" => "object",
      "membersApplicantCommunityLocal" => "object",
      "membersApplicantCommunityGlobal" => "object",
      "purpose" => "object",
      "compensationPreviousYear" => "object",
      "totalAmount" => "object",
      "explanation" => "object",
      "feePerson" => "object",
      "feeCommunity" => "object",
      "primaryArt" => "object",
      "nameOfEvent" => "object",
      "isFestival" => "object",
      "staffPeopleFulltime" => "object",
      "staffPeopleParttime" => "object",
      "staffPeopleVoluntary" => "object",
      "staffManyearsFulltime" => "object",
      "staffManyearsParttime" => "object",
      "toiminta_kohderyhmat" => "string",
      "toiminta_yhteistyokumppanit" => "string",
      "eventDaysCountHki" => "object",
      "performanceCountHki" => "object",
      "performanceCountAll" => "object",
      "exhibitionCountHki" => "object",
      "exhibitionCountAll" => "object",
      "workshopCountHki" => "object",
      "workshopCountAll" => "object",
      "firstPublicPerformancesCount" => "object",
      "premiereCountHki" => "object",
      "firstPublicEventLocationHki" => "object",
      "firstPublicEventLocationPostCode" => "object",
      "isOwnedByCity" => "object",
      "eventsVisitorsHkiTotal" => "object",
      "eventsVisitorsTotal" => "object",
      "firstPublicOccasionDate" => "object",
      "projectStartDate" => "object",
      "projectEndDate" => "object",
      "eventOrFestivalDates" => "object",
      "detailedProjectDescription" => "object",
      "plannedPremisesArray" => "array",
      "isPartOfVOS" => "object",
      "membersPersonLocal" => "object",
      "membersPersonGlobal" => "object",
      "membersCommunityLocal" => "object",
      "membersCommunityGlobal" => "object",
      "otherValuables" => "object",
      "adultsMale" => "object",
      "adultsFemale" => "object",
      "adultsOther" => "object",
      "juniorsMale" => "object",
      "juniorsFemale" => "object",
      "juniorsOther" => "object",
      "eventName" => "object",
      "eventTargetGroup" => "object",
      "eventPlace" => "object",
      "eventContent" => "object",
      "eventBegin" => "object",
      "eventEnd" => "object",
      "isEventEquality" => "object",
      "eventEqualityText" => "object",
      "isEventCommunal" => "object",
      "eventCommunalText" => "object",
      "isEventEnvironment" => "object",
      "eventEnvironmentText" => "object",
      "isEventNewPeopleActivating" => "object",
      "eventNewPeopleActivatingText" => "object",
      "isEventWorkdayActivating" => "object",
      "eventWorkdayActivatingText" => "object",
    ];

    // Setup schema.
    $schema = self::createSchema();
    $schemaStructure = file_get_contents('/app/conf/tietoliikennesanoma_schema.json');
    $schemaStructure = Json::decode($schemaStructure);

    // Loop through $elementTypeMap and assert for the type.
    foreach ($elementTypeMap as $elementName => $elementType) {
      $propertySchema = $schema->getPropertySchema($elementName, $schemaStructure);
      $this->assertEquals($elementType, $propertySchema['type']);
    }
  }

}
