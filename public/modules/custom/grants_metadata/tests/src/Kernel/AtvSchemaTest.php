<?php

namespace Drupal\Tests\grants_metadata\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_metadata\TypedData\Definition\YleisavustusHakemusDefinition;
use Drupal\grants_test_base\Kernel\GrantsKernelTestBase;

/**
 * Tests AtvSchema class.
 *
 * @covers \Drupal\grants_metadata\AtvSchema
 * @group grants_metadata
 */
class AtvSchemaTest extends GrantsKernelTestBase implements ServiceModifierInterface {
  /**
   * The modules to load to run the test.
   *
   * @var array<string>
   */
  protected static $modules = [
    // Drupal modules.
    'field',
    'user',
    'file',
    'node',
    'system',
    'language',
    'locale',
    'locale_test',
    // Contribs from drupal.org.
    'webform',
    'externalauth',
    'openid_connect',
    'openid_connect_logout_redirect',
    // Contrib hel.fi modules.
    'helfi_audit_log',
    'helfi_helsinki_profiili',
    'helfi_atv',
    'helfi_api_base',
    'helfi_yjdh',
    // Project modules.
    'grants_applicant_info',
    'grants_events',
    'grants_attachments',
    'grants_budget_components',
    'grants_club_section',
    'grants_mandate',
    'grants_metadata',
    'grants_handler',
    'grants_members',
    'grants_metadata',
    'grants_orienteering_map',
    'grants_place_of_operation',
    'grants_premises',
    'grants_profile',
    // Test modules.
    'grants_test_base',
    'grants_test_webforms',
  ];

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container
      ->getDefinition('grants_profile.service')
      ->setClass('Drupal\\grants_test_base\\GrantsProfileServiceTest');
    $container
      ->getDefinition('session')
      ->setClass('Drupal\\grants_test_base\\MockSession');
  }

  /* Helper functions to prepare for testing: */

  /**
   * Create ATV Schema instance.
   */
  public static function createSchema(): AtvSchema {
    $manager = \Drupal::typedDataManager();
    $schema = new AtvSchema($manager);
    // Use relative path. It works in all environments.
    $schemaPath = __DIR__ . "/../../../../../../../conf/tietoliikennesanoma_schema.json";
    $schema->setSchema($schemaPath);
    return $schema;
  }

  /* Helper functions to prepare for testing: */

  /**
   * Load test data from data directory.
   */
  public static function loadSubmissionData($formName): array {
    if (str_contains($formName, '.')) {
      $parts = explode('.', $formName);
      $formName = $parts[0];
      $applicantType = $parts[1];

    }
    else {
      $applicantType = NULL;
    }

    $filePath = __DIR__ . "/../../data/{$formName}.data.json";
    $data = json_decode(file_get_contents($filePath), TRUE);

    if ($applicantType) {
      return $data[$applicantType];
    }
    return $data;

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
    $definitionsMappings = Mappings::DEFINITIONS;
    // Datatype plugin requires the module enablation.
    if (!isset($definitionsMappings[$formId])) {
      throw new \Exception('Unknown form id');
    }
    $dataDefinition = ($definitionsMappings[$formId]['class'])::create($definitionsMappings[$formId]['parameter']);

    $typeManager = $dataDefinition->getTypedDataManager();
    $applicationData = $typeManager->create($dataDefinition);

    $applicationData->setValue($submittedFormData);

    return $applicationData;
  }

  /* Helper methods for tests: */

  /**
   * Helper function to fetch the given field from document for tests.
   */
  protected function assertDocumentField($document, array $keys, string $fieldName, $value, $skipMetaChecks = FALSE) {
    array_unshift($keys, 'compensation');
    $arrayOfFieldData = NestedArray::getValue($document, $keys);
    $this->assertDocumentFieldArray($arrayOfFieldData, $fieldName, $value, $skipMetaChecks);
  }

  /**
   * Helper function to make test assertions for a field in document.
   */
  protected function assertDocumentFieldArray(array $fieldData, string $fieldName, $value, $skipMetaChecks = FALSE): void {
    $this->assertArrayHasKey('ID', $fieldData);
    $this->assertArrayHasKey('value', $fieldData);
    $this->assertArrayHasKey('valueType', $fieldData);
    $this->assertArrayHasKey('label', $fieldData);

    $this->assertEquals($fieldName, $fieldData['ID']);

    if ($value !== $fieldData['value']) {
      var_dump($fieldData);
    }

    $this->assertEquals($value, $fieldData['value']);

    if ($skipMetaChecks) {
      return;
    }
    $this->assertArrayHasKey('meta', $fieldData);
    $meta = json_decode($fieldData['meta'], TRUE);
    $this->assertArrayHasKey('page', $meta);
    $this->assertArrayHasKey('section', $meta);
    $this->assertArrayHasKey('element', $meta);
    $this->assertTrue(isset($meta['element']['hidden']));

  }

  /**
   * Helper function to assert compensation data.
   */
  protected function assertCompensation($document) {
    // Other compensation.
    $arrayIndex1 = 'otherCompensationsInfo';
    $arrayIndex2 = 'otherCompensationsArray';
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0, 0], 'issuer', '1');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0, 1], 'issuerName', 'Valtio');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0, 2], 'year', '2020');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0, 3], 'amount', '42');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0, 4], 'purpose', 'Selvitä elämän tarkoitus');

    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 1, 0], 'issuer', '5');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 1, 1], 'issuerName', 'Suihkulähde');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 1, 2], 'year', '2021');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 1, 3], 'amount', '69');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 1, 4], 'purpose', 'Tulla märäksi');
  }

  /**
   * Test data for a registered community.
   */
  protected function assertRegisteredCommunity($document): void {
    // Applicant info.
    $this->assertDocumentField($document, ['applicantInfoArray', 0], 'applicantType', '2');
    $this->assertDocumentField($document, ['applicantInfoArray', 1], 'companyNumber', '2036583-2');
    $this->assertDocumentField($document, ['applicantInfoArray', 2], 'registrationDate', '10.05.2006');
    $this->assertDocumentField($document, ['applicantInfoArray', 3], 'foundingYear', '1337');
    $this->assertDocumentField($document, ['applicantInfoArray', 4], 'home', 'VOIKKAA');
    $this->assertDocumentField($document, ['applicantInfoArray', 5], 'homePage', 'arieerola.example.com');
    $name = 'Maanrakennus Ari Eerola T:mi';
    $this->assertDocumentField($document, ['applicantInfoArray', 6], 'communityOfficialName', $name);
    $this->assertDocumentField($document, ['applicantInfoArray', 7], 'communityOfficialNameShort', 'AE');
    $this->assertDocumentField($document, ['applicantInfoArray', 8], 'email', 'ari.eerola@example.com');
  }

  /* Start actual test methods: */

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testAsukaPienaHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('asukasosallisuus_pienavustushake');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('asukapiena');
    $typedData = self::webformToTypedData($submissionData, 'asukapiena');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '38');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '123');
    // activitiesInfoArray.
    $this->assertDocumentField($document, ['activitiesInfoArray', 0], 'businessPurpose', 'Kuvaus');
    $this->assertDocumentField($document, ['activitiesInfoArray', 1], 'communityPracticesBusiness', 'false');
    $this->assertDocumentField($document, ['activitiesInfoArray', 2], 'membersApplicantPersonLocal', '11');
    $this->assertDocumentField($document, ['activitiesInfoArray', 3], 'membersApplicantPersonGlobal', '33');
    $this->assertDocumentField($document, ['activitiesInfoArray', 4], 'membersApplicantCommunityLocal', '2');
    $this->assertDocumentField($document, ['activitiesInfoArray', 5], 'membersApplicantCommunityGlobal', '22');
    $this->assertDocumentField($document, ['activitiesInfoArray', 6], 'feePerson', '11');
    $this->assertDocumentField($document, ['activitiesInfoArray', 7], 'feeCommunity', '111');
    // Contact Info and Address.
    $this->assertDocumentField($document, ['currentAddressInfoArray', 0], 'contactPerson', 'Ari Eerola');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 1], 'phoneNumber', '0401234567');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 2], 'street', 'Testitie 3');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 3], 'city', 'Testilä');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 4], 'postCode', '00100');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 5], 'country', 'Suomi');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testAsukaYleisToimHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('asukasosallisuus_yleis_ja_toimin');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('asukasyleistoim');
    $typedData = self::webformToTypedData($submissionData, 'asukasyleistoim');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '1');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '123');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 0], 'subventionType', '6');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 1], 'amount', '321');
    // activitiesInfoArray.
    $this->assertDocumentField($document, ['activitiesInfoArray', 0], 'businessPurpose', 'Kuvaus toiminnasta');
    $this->assertDocumentField($document, ['activitiesInfoArray', 1], 'communityPracticesBusiness', 'false');
    $this->assertDocumentField($document, ['activitiesInfoArray', 2], 'membersApplicantPersonLocal', '2');
    $this->assertDocumentField($document, ['activitiesInfoArray', 3], 'membersApplicantPersonGlobal', '12');
    $this->assertDocumentField($document, ['activitiesInfoArray', 4], 'membersApplicantCommunityLocal', '1');
    $this->assertDocumentField($document, ['activitiesInfoArray', 5], 'membersApplicantCommunityGlobal', '11');
    // generalInfoArray.
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 0], 'purpose', 'Bileet!');
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 1], 'compensationPreviousYear', 'false');
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 2], 'explanation', '');
    // ApplicantOfficialsArray.
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 0], 'name', 'Testeeje');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 1], 'role', '2');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 2], 'email', 'pp@example.com');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 3], 'phone', '1234567');
    // Other compensation.
    $arrayIndex1 = 'otherCompensationsInfo';
    $arrayIndex2 = 'otherAppliedCompensationsArray';
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0, 0], 'issuer', '3');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0, 1], 'issuerName', 'EU');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0, 2], 'year', '2024');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0, 3], 'amount', '333');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0, 4], 'purpose', 'Etkot!');
  }

  /**
   *
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testHyvinYleisHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('hyte_yleisavustus');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('hyvinyleis');
    $typedData = self::webformToTypedData($submissionData, 'hyvinyleis');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '6');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '123');
    // activitiesInfoArray.
    $this->assertDocumentField($document, ['activitiesInfoArray', 0], 'businessPurpose', 'Kuvaus toiminnasta');
    $this->assertDocumentField($document, ['activitiesInfoArray', 1], 'communityPracticesBusiness', 'false');
    $this->assertDocumentField($document, ['activitiesInfoArray', 2], 'membersApplicantPersonLocal', '50');
    $this->assertDocumentField($document, ['activitiesInfoArray', 3], 'membersApplicantPersonGlobal', '100');
    $this->assertDocumentField($document, ['activitiesInfoArray', 4], 'membersApplicantCommunityLocal', '5');
    $this->assertDocumentField($document, ['activitiesInfoArray', 5], 'membersApplicantCommunityGlobal', '10');
    // ApplicantOfficialsArray.
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 0], 'name', 'Testeeje');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 1], 'role', '2');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 2], 'email', 'pp@example.com');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 3], 'phone', '1234567');
    // compensationInfo.
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 0], 'purpose', 'Juhlat');
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 1], 'compensationPreviousYear', 'true');
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 3], 'explanation', 'Selvitystä ja lisää selvitystä');
  }

  /**
   *
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKansliaTyoHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('kaupunginkanslia_tyollisyysavust');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('kansliatyo');
    $typedData = self::webformToTypedData($submissionData, 'kansliatyo');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Activites info.
    $this->assertDocumentField($document, ['activitiesInfoArray', 0], 'businessPurpose', 'Description of activities');
    $this->assertDocumentField($document, ['activitiesInfoArray', 1], 'communityPracticesBusiness', 'false');
    $this->assertDocumentField($document, ['activitiesInfoArray', 2], 'membersApplicantPersonLocal', '120');
    $this->assertDocumentField($document, ['activitiesInfoArray', 3], 'membersApplicantPersonGlobal', '120');
    $this->assertDocumentField($document, ['activitiesInfoArray', 4], 'membersApplicantCommunityLocal', '12');
    $this->assertDocumentField($document, ['activitiesInfoArray', 5], 'membersApplicantCommunityGlobal', '12');
  }

  /**
   *
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKaskoIpLisaHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('kasko_ip_lisa');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('kaskoiplisa');
    $typedData = self::webformToTypedData($submissionData, 'kaskoiplisa');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '14');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '35');
    // Specific data for this form:
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 0], 'purpose', 'Kuaus');
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 1], 'timeFrameBegin', '2024-07-07');
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 2], 'timeFrameEnd', '2024-09-22');
  }

  /**
   *
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKaskoToimintaHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('kasvatus_ja_koulutus_toiminta_av');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('kaskotoiminta');
    $typedData = self::webformToTypedData($submissionData, 'kaskotoiminta');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '1');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '11');
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
    $this->assertRegisteredCommunity($document);
    $bankAccountConfirmationEventValues = [
      "eventType" => "HANDLER_ATT_OK",
      "eventID" => "4d0405ae-596a-4560-a375-d8f043fe7f77",
      "caseId" => "GRANTS-LOCALTEST-KASKOYLEIS-00000001",
      "eventDescription" => "Attachment uploaded.",
      "eventTarget" => "verification_file.pdf",
      "eventSource" => "GrantsApplications",
      "timeUpdated" => "2023-04-13T09:16:39",
      "timeCreated" => "2023-04-13T09:16:39",
    ];
    foreach ($bankAccountConfirmationEventValues as $key => $value) {
      $this->assertEquals($document['events'][0][$key], $value);
    }
    // Applicant officials.
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 0], 'name', 'Ari Eerola');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 1], 'role', '3');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 2], 'email', 'ari.eerola@example.com');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 3], 'phone', '0501234567');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 1, 0], 'name', 'Eero Arila');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 1, 1], 'role', '3');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 1, 2], 'email', 'eero.arila@example.com');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 1, 3], 'phone', '0507654321');
    // Contact Info and Address.
    $this->assertDocumentField($document, ['currentAddressInfoArray', 0], 'contactPerson', 'Eero Arila');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 1], 'phoneNumber', '0507654321');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 2], 'street', 'Testitie 1');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 3], 'city', 'Testilä');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 4], 'postCode', '00100');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 5], 'country', 'Suomi');
    // Application Info.
    $applicationNumber = 'GRANTS-LOCALPAK-KASKOYLEIS-00000001';
    $this->assertDocumentField($document, ['applicationInfoArray', 0], 'applicationNumber', $applicationNumber);
    $this->assertDocumentField($document, ['applicationInfoArray', 1], 'status', 'DRAFT');
    $this->assertDocumentField($document, ['applicationInfoArray', 2], 'actingYear', '2023');
    // compensationInfo.
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 0], 'compensationPreviousYear', "true");
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 1], 'totalAmount', '0', TRUE);
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 2], 'explanation', 'seliseli', TRUE);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '1');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '0');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 0], 'subventionType', '5');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 1], 'amount', '0');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 2, 0], 'subventionType', '36');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 2, 1], 'amount', '0');

    // bankAccountArray.
    $this->assertDocumentField($document, ['bankAccountArray', 0], 'accountNumber', 'FI21 1234 5600 0007 85');

    // benefitsInfoArray.
    $this->assertDocumentField($document, ['benefitsInfoArray', 0], 'loans', '13');
    $this->assertDocumentField($document, ['benefitsInfoArray', 1], 'premises', '13');
    // activitiesInfoArray.
    $this->assertDocumentField($document, ['activitiesInfoArray', 0], 'businessPurpose', 'Massin teko');
    $this->assertDocumentField($document, ['activitiesInfoArray', 1], 'membersApplicantPersonLocal', '100');
    $this->assertDocumentField($document, ['activitiesInfoArray', 2], 'membersApplicantPersonGlobal', '150');
    $this->assertDocumentField($document, ['activitiesInfoArray', 3], 'membersApplicantCommunityLocal', '10');
    $this->assertDocumentField($document, ['activitiesInfoArray', 4], 'membersApplicantCommunityGlobal', '15');
    $this->assertDocumentField($document, ['activitiesInfoArray', 5], 'feePerson', '10');
    $this->assertDocumentField($document, ['activitiesInfoArray', 6], 'feeCommunity', '200');
  }

  /**
   * Test kuvaprojekti with registered community and subventions over 5000.
   *
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function testKuvaProjektiHakemusRegistered() : void {
    $schema = self::createSchema();
    $webform = self::loadWebform('kuva_projekti');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('kuva_projekti.registered_community');
    $typedData = self::webformToTypedData($submissionData, 'kuva_projekti');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);

    // Other compensation.
    $this->assertCompensation($document);

    // Handle activities.
    $arrayIndex1 = 'activityBasisInfo';
    $arrayIndex2 = 'activityBasisArray';
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0], 'toiminta_taiteelliset_lahtokohdat', '');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 1], 'toiminta_tasa_arvo', '');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 2], 'toiminta_saavutettavuus', '');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 3], 'toiminta_yhteisollisyys', '');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 4], 'toiminta_kohderyhmat', '');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 5], 'toiminta_ammattimaisuus', '');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 6], 'toiminta_ekologisuus', '');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 7], 'toiminta_yhteistyokumppanit', '');

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
    $submissionData = self::loadSubmissionData('kuva_projekti.unregistered_community');
    $typedData = self::webformToTypedData($submissionData, 'kuva_projekti');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    $this->assertDocumentField($document, ['applicantInfoArray', 0], 'applicantType', '1');
    $this->assertDocumentField($document, ['applicantInfoArray', 1], 'communityOfficialName', 'Pöpilä');
    $this->assertDocumentField($document, ['applicantInfoArray', 2], 'email', 'mailfromprofile@example.com');

    // Address info.
    $this->assertDocumentField($document, ['currentAddressInfoArray', 0], 'street', 'Kaukotie 5');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 1], 'city', 'Helsinki');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 2], 'postCode', '01300');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 4], 'contactPerson', 'Nordea Demo');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 5], 'phoneNumber', '+35812121212121212121');
    // Applicant officials array.
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 0], 'name', 'Veijo Official');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 1], 'role', '0');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 2], 'email', 'official@example.com');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 3], 'phone', '+35812121212121212121');
    // bankAccountArray.
    $this->assertDocumentField($document, ['bankAccountArray', 0], 'accountOwnerName', 'Wii Wii');
    $this->assertDocumentField($document, ['bankAccountArray', 1], 'socialSecurityNumber', '290492-932R');
    $this->assertDocumentField($document, ['bankAccountArray', 2], 'accountNumber', 'FI2523629411259741');

    $this->assertCompensation($document);

    // Handle activities.
    $arrayIndex1 = 'activityBasisInfo';
    $arrayIndex2 = 'activityBasisArray';
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 0], 'toiminta_kohderyhmat', '');
    $this->assertDocumentField($document, [$arrayIndex1, $arrayIndex2, 1], 'toiminta_yhteistyokumppanit', '');
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
    $this->assertRegisteredCommunity($document);
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKuvaKehaHakemus() {
    $schema = self::createSchema();
    $webform = self::loadWebform('taide_ja_kulttuuri_kehittamisavu');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('kuvakeha');
    $typedData = self::webformToTypedData($submissionData, 'kuvakeha');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '44');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '12');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testKuvaPerusHakemus() {
    $schema = self::createSchema();
    $webform = self::loadWebform('taide_ja_kulttuuriavustukset_tai');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('kuvaperus');
    $typedData = self::webformToTypedData($submissionData, 'kuvaperus');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '34');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '230');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testLeiriSelvitysHakemus() {
    $schema = self::createSchema();
    $webform = self::loadWebform('leiriselvitys');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('leiriselvitys');
    $typedData = self::webformToTypedData($submissionData, 'leiriselvitys');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Budget.
    $this->assertDocumentField($document, ['budgetInfo', 'incomeGroupsArrayStatic', 0, 'otherIncomeRowsArrayStatic', 0], 'tulo_0', '100');
    $this->assertDocumentField($document, ['budgetInfo', 'costGroupsArrayStatic', 0, 'otherCostRowsArrayStatic', 0], 'meno_0', '200');

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
    // Applicant info.
    $this->assertRegisteredCommunity($document);

    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '37');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '123');

    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 0], 'name', 'Ari');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 1], 'role', '3');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 2], 'email', 'ari@example.com');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 3], 'phone', '234567');

    // Contact Info and Address.
    $this->assertDocumentField($document, ['currentAddressInfoArray', 0], 'contactPerson', 'Testaaja');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 1], 'phoneNumber', '0501234567');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 2], 'street', 'Testiti 1');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 3], 'city', 'Testi');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 4], 'postCode', '00100');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 5], 'country', 'Suomi');

    // bankAccountArray.
    $this->assertDocumentField($document, ['bankAccountArray', 0], 'accountNumber', 'FI6044581558982351');

    // participantsArray.
    $this->assertDocumentField($document, ['participantsArray', 0], 'adultsMale', '11');
    $this->assertDocumentField($document, ['participantsArray', 1], 'adultsFemale', '22');
    $this->assertDocumentField($document, ['participantsArray', 2], 'adultsOther', '33');
    $this->assertDocumentField($document, ['participantsArray', 3], 'juniorsMale', '44');
    $this->assertDocumentField($document, ['participantsArray', 4], 'juniorsFemale', '55');
    $this->assertDocumentField($document, ['participantsArray', 5], 'juniorsOther', '66');

    // eventInfoArray.
    $this->assertDocumentField($document, ['eventInfoArray', 0, 0], 'eventName', 'Event information description');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 1], 'eventTargetGroup', 'Drupal developers');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 2], 'eventPlace', 'Work from home');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 3], 'eventContent', 'Plenty of coffee');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 4], 'eventBegin', '2023-09-14');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 5], 'eventEnd', '2023-09-15');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 6], 'isEventEquality', 'true');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 7], 'eventEqualityText', 'Coffee');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 8], 'isEventCommunal', 'false');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 9], 'isEventEnvironment', 'true');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 10], 'eventEnvironmentText', 'More coffee');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 11], 'isEventNewPeopleActivating', 'false');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 12], 'isEventWorkdayActivating', 'true');
    $this->assertDocumentField($document, ['eventInfoArray', 0, 13], 'eventWorkdayActivatingText', 'You guessed it, coffee!');

    // Budget info. We need to test for labels because they are data as well.
    $keys = ['compensation', 'budgetInfo', 'incomeGroupsArrayStatic', 0, 'otherIncomeRowsArrayStatic', 0];
    $budgetOtherIncome = NestedArray::getValue($document, $keys);
    $this->assertDocumentFieldArray($budgetOtherIncome, 'budget_other_income_0', '12345');
    $this->assertEquals('Sell coffee', $budgetOtherIncome['label']);

    $keys = ['compensation', 'budgetInfo', 'costGroupsArrayStatic', 0, 'otherCostRowsArrayStatic', 0];
    $budgetOtherCost = NestedArray::getValue($document, $keys);
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
    $this->assertRegisteredCommunity($document);
    $keys = ['compensationInfo', 'premisesCompensation', 'rentCostsArray', 0];
    $this->assertDocumentField($document, $keys, 'rentCostsHours', '123');
    $this->assertDocumentField($document, ['compensationInfo', 'premisesCompensation', 'rentCostsArray', 0], 'rentCostsHours', '123');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testLiikuntaLaitosHakemus() {
    $schema = self::createSchema();
    $webform = self::loadWebform('liikunta_laitosavustushakemus');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('liikuntalaitos');
    $typedData = self::webformToTypedData($submissionData, 'liikuntalaitos');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '1');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '12');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 0], 'subventionType', '5');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 1], 'amount', '34');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 2, 0], 'subventionType', '8');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 2, 1], 'amount', '234');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 3, 0], 'subventionType', '9');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 3, 1], 'amount', '1234');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testLiikuntaSuunnistusHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('liikunta_suunnistuskartta_avustu');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('liikuntasuunnistus');
    $typedData = self::webformToTypedData($submissionData, 'liikuntasuunnistus');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    $this->assertDocumentField($document, ['orienteeringMapInfo', 'orienteeringMapsArray', 0, 0], 'mapName', 'Karttala');
    $this->assertDocumentField($document, ['orienteeringMapInfo', 'orienteeringMapsArray', 0, 1], 'size', '34');
    $this->assertDocumentField($document, ['orienteeringMapInfo', 'orienteeringMapsArray', 0, 2], 'voluntaryHours', '10');
    $this->assertDocumentField($document, ['orienteeringMapInfo', 'orienteeringMapsArray', 0, 3], 'cost', '100');
    $this->assertDocumentField($document, ['orienteeringMapInfo', 'orienteeringMapsArray', 0, 4], 'otherCompensations', '10');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testLiikuntaYleisavustusHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('liikunta_yleisavustushakemus');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('liikuntayleis');
    $typedData = self::webformToTypedData($submissionData, 'liikuntayleis');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    $this->assertDocumentField($document, ['applicationInfoArray', 0], 'applicationType', 'LIIKUNTAYLEIS', TRUE);
    $this->assertDocumentField($document, ['applicationInfoArray', 6], 'actingYear', '2024');
    // Additional information is string field.
    $this->assertEquals('Lisätiedot', $document['compensation']['additionalInformation']);
    // This field is encoded because it is included by backend magic.
    // That's why there is no metadata.
    $this->assertDocumentField($document, ['activitiesInfoArray', 0], 'businessPurpose', 'Massin teko', TRUE);
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 0], 'purpose', 'Ostetaan kohde');
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '9');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '0');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 0], 'subventionType', '31');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 1], 'amount', '0');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 2, 0], 'subventionType', '43');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 2, 1], 'amount', '125');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testNuorisoLomaHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('nuorlomaleir');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('nuorisoloma');
    $typedData = self::webformToTypedData($submissionData, 'nuorisoloma');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '12');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '4500');
    // Budget.
    $this->assertDocumentField($document, ['budgetInfo', 'incomeGroupsArrayStatic', 0, 'otherIncomeRowsArrayStatic', 0], 'tulo_0', '1000');
    $this->assertDocumentField($document, ['budgetInfo', 'costGroupsArrayStatic', 0, 'otherCostRowsArrayStatic', 0], 'meno_0', '2000');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testNuorisoProjektiHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('nuorisotoiminta_projektiavustush');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('nuorisoprojekti');
    $typedData = self::webformToTypedData($submissionData, 'nuorisoprojekti');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '4');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '230');
    // Budget.
    $this->assertDocumentField($document, [
      'budgetInfo',
      'incomeGroupsArrayStatic',
      0,
      'otherIncomeRowsArrayStatic',
      0,
    ], 'budget_other_income_0', '150');
    $this->assertDocumentField($document, [
      'budgetInfo',
      'costGroupsArrayStatic',
      0,
      'otherCostRowsArrayStatic',
      0,
    ], 'budget_other_cost_0', '160');
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testNuorisoToimintaHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('nuorisopalvelut_toiminta_ja_palk');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('nuorisotoiminta');
    $typedData = self::webformToTypedData($submissionData, 'nuorisotoiminta');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '1');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '1000');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 0], 'subventionType', '2');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 1], 'amount', '2000');

  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testNuorToimEnnakkoHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('nuorisopalvelut_toiminta_ja_palk');
    $this->assertNotNull($webform);
    $pages = $webform->getPages('edit');
    $this->initSession();
    $submissionData = self::loadSubmissionData('nuortoimennakko');
    $typedData = self::webformToTypedData($submissionData, 'nuortoimennakko');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '1');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '100');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 0], 'subventionType', '2');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 1], 'amount', '200');
    $this->assertDocumentField($document, ['compensationInfo', 'previousYearArray', 0, 0], 'subventionType', '1', TRUE);
    $this->assertDocumentField($document, ['compensationInfo', 'previousYearArray', 0, 1], 'amount', '1');
    $this->assertDocumentField($document, ['compensationInfo', 'previousYearArray', 0, 2], 'usedAmount', '2');
    $this->assertDocumentField($document, ['compensationInfo', 'previousYearArray', 1, 0], 'subventionType', '2', TRUE);
    $this->assertDocumentField($document, ['compensationInfo', 'previousYearArray', 1, 1], 'amount', '3');
    $this->assertDocumentField($document, ['compensationInfo', 'previousYearArray', 1, 2], 'usedAmount', '4');
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
    $this->assertRegisteredCommunity($document);

    // Applicant officials.
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 0], 'name', 'Ari Eerola');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 1], 'role', '3');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 2], 'email', 'ari.eerola@example.com');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 0, 3], 'phone', '0501234567');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 1, 0], 'name', 'Eero Arila');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 1, 1], 'role', '3');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 1, 2], 'email', 'eero.arila@example.com');
    $this->assertDocumentField($document, ['applicantOfficialsArray', 1, 3], 'phone', '0507654321');
    // Contact Info and Address.
    $this->assertDocumentField($document, ['currentAddressInfoArray', 0], 'contactPerson', 'Eero Arila');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 1], 'phoneNumber', '0507654321');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 2], 'street', 'Testitie 1');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 3], 'city', 'Testilä');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 4], 'postCode', '00100');
    $this->assertDocumentField($document, ['currentAddressInfoArray', 5], 'country', 'Suomi');
    // Application Info.
    $applicationNumber = 'GRANTS-LOCALPAK-ECONOMICGRANTAPPLICATION-00000001';
    $this->assertDocumentField($document, ['applicationInfoArray', 0], 'applicationNumber', $applicationNumber);
    $this->assertDocumentField($document, ['applicationInfoArray', 1], 'status', 'DRAFT');
    $this->assertDocumentField($document, ['applicationInfoArray', 2], 'actingYear', '2023');
    // compensationInfo.
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 0], 'compensationPreviousYear', "true");
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 1], 'totalAmount', '0', TRUE);
    $this->assertDocumentField($document, ['compensationInfo', 'generalInfoArray', 2], 'explanation', 'seliseli', TRUE);
    // Handle subventions.
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 0], 'subventionType', '1');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 0, 1], 'amount', '0');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 0], 'subventionType', '5');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 1, 1], 'amount', '0');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 2, 0], 'subventionType', '36');
    $this->assertDocumentField($document, ['compensationInfo', 'compensationArray', 2, 1], 'amount', '0');

    // bankAccountArray.
    $this->assertDocumentField($document, ['bankAccountArray', 0], 'accountNumber', 'FI21 1234 5600 0007 85');

    // benefitsInfoArray.
    $this->assertDocumentField($document, ['benefitsInfoArray', 0], 'loans', '13');
    $this->assertDocumentField($document, ['benefitsInfoArray', 1], 'premises', '13');
    // activitiesInfoArray.
    $this->assertDocumentField($document, ['activitiesInfoArray', 0], 'businessPurpose', 'Massin teko');
    $this->assertDocumentField($document, ['activitiesInfoArray', 1], 'membersApplicantPersonLocal', '100');
    $this->assertDocumentField($document, ['activitiesInfoArray', 2], 'membersApplicantPersonGlobal', '150');
    $this->assertDocumentField($document, ['activitiesInfoArray', 3], 'membersApplicantCommunityLocal', '10');
    $this->assertDocumentField($document, ['activitiesInfoArray', 4], 'membersApplicantCommunityGlobal', '15');
    $this->assertDocumentField($document, ['activitiesInfoArray', 5], 'feePerson', '10');
    $this->assertDocumentField($document, ['activitiesInfoArray', 6], 'feeCommunity', '200');

    // Attachment info lives outside compensation array.
    $attachmentOne = $document['attachmentsInfo']['attachmentsArray'][0];
    $this->assertCount(6, $attachmentOne);
    $fieldData = $attachmentOne[0];
    $this->assertDocumentFieldArray($fieldData, 'description', 'Yhteisön säännöt (uusi hakija tai säännöt muuttuneet)');
    $fieldData = $attachmentOne[1];
    $this->assertDocumentFieldArray($fieldData, 'fileName', 'truck_clipart_15144.jpg');
    $fieldData = $attachmentOne[2];
    $this->assertDocumentFieldArray($fieldData, 'fileType', '7');
    $fieldData = $attachmentOne[3];
    $integrationId = '/LOCAL/v1/documents/4f3d41b8-e133-4ac7-b31a-9ece0aeba114/attachments/7657/';
    $this->assertDocumentFieldArray($fieldData, 'integrationID', $integrationId);
    $fieldData = $attachmentOne[4];
    $this->assertDocumentFieldArray($fieldData, 'isDeliveredLater', 'false');
    $fieldData = $attachmentOne[5];
    $this->assertDocumentFieldArray($fieldData, 'isIncludedInOtherFile', 'false');

    $attachmentTwo = $document['attachmentsInfo']['attachmentsArray'][1];
    $this->assertCount(5, $attachmentTwo);
    $fieldData = $attachmentTwo[0];
    $this->assertDocumentFieldArray($fieldData, 'description', 'Toimintakertomus');
    // We are also testing that there is no file name data.
    $fieldData = $attachmentTwo[1];
    $this->assertDocumentFieldArray($fieldData, 'fileType', '7');
    $fieldData = $attachmentTwo[2];
    $this->assertDocumentFieldArray($fieldData, 'integrationID', '');
    $fieldData = $attachmentTwo[3];
    $this->assertDocumentFieldArray($fieldData, 'isDeliveredLater', 'false');
    $fieldData = $attachmentTwo[4];
    $this->assertDocumentFieldArray($fieldData, 'isIncludedInOtherFile', 'true');
    // Additional information is string field.
    $this->assertEquals('Lisätietoja hakemuksesta', $document['compensation']['additionalInformation']);
    // Test requiredInJson setting.
    $fieldExists = isset($document['compensation']['otherCompensationsInfo']['otherCompensationsArray']);
    $this->assertEquals(TRUE, $fieldExists);
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
    $this->assertDocumentField($document, ['activitiesInfoArray', 0], 'businessPurpose', 'Massin teko');
    $level6Exists = isset($document['compensation']['activitiesInfoArray']['level3']);
    $this->assertEquals(FALSE, $level6Exists);
    $this->assertDocumentField($document, ['activitiesInfoArray', 1], 'membersApplicantPersonGlobal', '150');
    $this->assertDocumentField($document, ['activitiesInfoArray', 2], 'membersApplicantCommunityLocal', '10');
    $this->assertDocumentField($document, ['activitiesInfoArray', 3], 'membersApplicantCommunityGlobal', '15');
    $this->assertDocumentField($document, ['activitiesInfoArray', 4], 'feePerson', '10');
    $this->assertDocumentField($document, ['activitiesInfoArray', 5], 'feeCommunity', '200');
    // Test skipZeroValue setting.
    $fieldExists = isset($document['compensation']['shouldNotExist']);
    $this->assertEquals(FALSE, $fieldExists);
  }

  /**
   * @covers \Drupal\grants_metadata\AtvSchema::typedDataToDocumentContentWithWebform
   */
  public function testYmparistoYleisHakemus(): void {
    $schema = self::createSchema();
    $webform = self::loadWebform('ymparistopalvelut_yleisavustus');
    $pages = $webform->getPages('edit');
    $this->assertNotNull($webform);
    $this->initSession();
    $submissionData = self::loadSubmissionData('ymparistopalvelut_yleisavustus');
    $typedData = self::webformToTypedData($submissionData, 'ymparistopalvelut_yleisavustus');
    // Run the actual data conversion.
    $document = $schema->typedDataToDocumentContentWithWebform($typedData, $webform, $pages, $submissionData);
    // Applicant info.
    $this->assertRegisteredCommunity($document);
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
    // Search attachment data.
    $attachmentField = $this->getAttachmentField($applicationData);
    $this->assertNotNull($attachmentField);

    // Handle attachment data.
    $definition = $attachmentField->getDataDefinition();
    $defaultValue = $definition->getSetting('defaultValue');
    $valueCallback = $definition->getSetting('valueCallback');
    $hiddenFields = $definition->getSetting('hiddenFields');
    foreach ($attachmentField as $item) {
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
        if (!isset($propertyItem[$itemName])) {
          continue;
        }
        // What to do with empty values.
        $itemSkipEmpty = $itemValueDefinition->getSetting('skipEmptyValue');

        $itemValue = $propertyItem[$itemName];
        $itemValue = ATVSchema::getItemValue($itemTypes, $itemValue, $defaultValue, $valueCallback);
        // If no value and skip is setting, then skip.
        if (empty($itemValue) && $itemSkipEmpty === TRUE) {
          continue;
        }
        $metaData = ATVSchema::getMetaData(NULL, NULL, $element);
        // Test that correct fields are hidden.
        $shouldBeHidden = ($itemName == 'integrationID' || $itemName == 'fileType');
        $this->assertEquals($shouldBeHidden, $metaData['element']['hidden']);
      }
    }
  }

  /**
   * Return attachments data.
   */
  protected function getAttachmentField(ComplexDataInterface $applicationData) {
    $attachmentField = NULL;
    foreach ($applicationData as $field) {
      $name = $field->getName();
      if ($name !== 'attachments') {
        continue;
      }
      $attachmentField = $field;
      break;
    }
    return $attachmentField;
  }

}
