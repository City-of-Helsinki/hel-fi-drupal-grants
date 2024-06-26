<?php

namespace Drupal\grants_handler\Kernel\TestDataConversion;

use Drupal\Component\Serialization\Json;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\grants_handler\Helpers;
use Drupal\grants_test_base\Kernel\GrantsKernelTestBase;
use Drupal\helfi_atv\AtvDocument;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Test data conversion from ATVDocument to WebformSubmission.
 */
class DataConversionTest extends GrantsKernelTestBase implements ServiceModifierInterface {
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
    'grants_attachments',
    'grants_budget_components',
    'grants_club_section',
    'grants_mandate',
    'grants_metadata',
    'grants_handler',
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

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $applicationTypes = [
      'KUVAPROJ' => [
        'id' => 'KUVAPROJ',
        'applicationTypeId' => '48',
        'dataDefinition' => [
          'definitionClass' => 'Drupal\grants_metadata\TypedData\Definition\KuvaProjektiDefinition',
          'definitionId' => 'grants_metadata_kaskoyleis',
        ],
        'labels' => [
          'fi' => 'Taide ja kulttuuri, projektiavustus',
          'en' => 'Arts and culture, project grant',
          'sv' => 'Konst och kultur, projektstöd',
        ],
      ],
    ];
    Helpers::setApplicationTypes($applicationTypes);
  }

  /**
   * Test for data conversion.
   */
  public function testDataConversion(): void {
    $this->initSession();

    $submissionStorage = $this->createMock('Drupal\grants_handler\GrantsHandlerSubmissionStorage');

    $submissionObject = WebformSubmission::create(['webform_id' => 'kuva_projekti']);
    $submissionObject->set('serial', 'TEST-1234');
    $customSettings = ['skip_available_number_check' => TRUE];
    $submissionObject->set('notes', Json::encode($customSettings));
    $submissionObject->set('in_draft', TRUE);
    $filePath = __DIR__ . "/../../data/esimerkki_48_KUVAPROJ.json";
    $content = json_decode(file_get_contents($filePath), TRUE);
    $data = [
      'id' => 'test-id',
      'type' => 'KUVAPROJ',
      'content' => $content,
      'events' => [],
    ];
    $document = AtvDocument::create($data);
    $document->setMetadata([]);
    // Do the actual data setting.
    $submissionStorage->setAtvDataToSubmission($document, $submissionObject);
    $expectedValues = [
      'members_applicant_person_global' => '50',
      'members_applicant_person_local' => '20',
      'members_applicant_community_global' => '5',
      'members_applicant_community_local' => '3',
      'kokoaikainen_henkilosto' => '50',
      'myonnetty_avustus_total' => '2800.0',
      'haettu_avustus_tieto_total' => '3000.0',
      'muu_huomioitava_panostus' => "ei sisälly mitään muuta rahanarvoista panosta tai vaihtokauppaa.",
      'extra_info' => "Tässä voi olla joku kaikkia liitteitä yhteisesti koskeva selvitys",
    ];
    // Test the values.
    foreach ($expectedValues as $field => $expectedValue) {
      $value = $submissionObject->getElementData($field);
      $this->assertEquals($expectedValue, $value);
    }
    $expectedAttachmentValues = [
      2 => [
        "description" => "Pankkitilin varmistus",
        "fileName" => "confirmation.pdf",
        "fileType" => "45",
        "integrationID" => "/LOCAL/v1/documents/9f708890-1840-408c-9fe3-277789bb1ac1/attachments/24946/",
        "isDeliveredLater" => "false",
        "isIncludedInOtherFile" => "false",
        "isNewAttachment" => "false",
        "attachmentName" => "confirmation.pdf",
      ],
      3 => [
        "description" => "Muu lite",
        "fileName" => "muu_liite.pdf",
        "fileType" => "0",
        "integrationID" => "/LOCAL/v1/documents/9f708890-1840-408c-9fe3-277789bb1ac0/attachments/24945/",
        "isDeliveredLater" => "false",
        "isIncludedInOtherFile" => "false",
        "isNewAttachment" => "false",
        "attachmentName" => "muu_liite.pdf",
      ],
    ];
    // Test attachment values.
    $attachmentData = $submissionObject->getElementData('muu_liite');
    foreach ($expectedAttachmentValues as $key => $expectedAttachmentData) {
      foreach ($expectedAttachmentData as $field => $expectedValue) {
        $value = $attachmentData[$key][$field];
        $this->assertEquals($expectedValue, $value);
      }
    }
  }

}
