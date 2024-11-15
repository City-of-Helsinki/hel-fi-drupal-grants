<?php

namespace Drupal\Tests\grants_handler\Unit;

use Drupal\grants_handler\ApplicationHelpers;
use Drupal\Tests\UnitTestCase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\grants_handler\ApplicationHelpers
 * @group grants_handler
 */
class ApplicationHelpersTest extends UnitTestCase {

  /**
   * Example of setting up the environment in the test case.
   */
  public function setUp(): void {
    parent::setUp();
    // Set the environment variable to the expected value.
    putenv('APP_ENV=ENV');
  }

  /**
   * Test the setFieldOptions method.
   *
   * @covers \Drupal\grants_handler\ApplicationHelpers::updateFieldOptions
   */
  public function testUpdateFieldOptions() {
    $form = [
      "elements" => [
        "2_avustustiedot" => [
          "muut_samaan_tarkoitukseen_myonnetyt_avustukset" => [
            "myonnetty_avustus" => [
              "#element" => [
                "issuer" => [
                  "#options" => [],
                ],
              ],
            ],
          ],
          "muut_samaan_tarkoitukseen_haetut_avustukset" => [
            "haettu_avustus_tieto" => [
              "#element" => [
                "issuer" => [
                  "#options" => [],
                ],
              ],
            ],
          ],
        ],
      ],
    ];

    $fieldStructureMyonnetty = [
      'myonnetty_avustus',
      'issuer',
    ];

    $fieldStructureHaettu = [
      'haettu_avustus_tieto',
      'issuer',
    ];

    $newOptions = [
      1 => 'State',
      3 => 'EU',
      4 => 'Other',
      5 => 'Foundation',
    ];

    // Test with a single field name.
    ApplicationHelpers::updateFieldOptions($form, $newOptions, $fieldStructureMyonnetty);
    $this->assertEquals($newOptions,
      $form['elements']['2_avustustiedot']['muut_samaan_tarkoitukseen_myonnetyt_avustukset']['myonnetty_avustus']['#element']['issuer']['#options']);

    ApplicationHelpers::updateFieldOptions($form, $newOptions, $fieldStructureHaettu);
    $this->assertEquals($newOptions,
      $form['elements']['2_avustustiedot']['muut_samaan_tarkoitukseen_haetut_avustukset']['haettu_avustus_tieto']['#element']['issuer']['#options']);

  }

  /**
   * Test the createApplicationNumber method.
   *
   * @covers ::createApplicationNumber
   * @covers ::getApplicationNumberInEnvFormat
   * @covers ::getApplicationNumberInEnvFormatOldFormat
   * @covers \Drupal\grants_handler\Helpers::getAppEnv
   */
  public function testCreateApplicationNumber() {
    // Set the environment variable to the expected value.
    putenv('APP_ENV=ENV');

    $webform = $this->createMock(Webform::class);
    $webform->method('getThirdPartySettings')
      ->will($this->onConsecutiveCalls(
        [
          'applicationType' => 'TYPE',
          'applicationTypeID' => '001',
        ],
        [
          'applicationType' => 'TYPE',
          'applicationTypeID' => '001',
        ],
        [
          'applicationType' => 'TYPE',
          'applicationTypeID' => '001',
        ],
        [
          'applicationType' => 'TYPE',
          'applicationTypeID' => '001',
        ],
        [
          'applicationType' => 'NEWTYPE',
          'applicationTypeID' => '002',
        ]
      ));

    $submission = $this->createMock(WebformSubmission::class);
    $submission->method('serial')->will($this->onConsecutiveCalls(123, 123, 456, 456, 456, 456));
    $submission->method('getWebform')->willReturn($webform);

    // Ensure the method is called with the correct parameters.
    $this->assertEquals('ENV-001-0000123', ApplicationHelpers::createApplicationNumber($submission, FALSE));
    $this->assertEquals('GRANTS-ENV-TYPE-00000123', ApplicationHelpers::createApplicationNumber($submission, TRUE));

    // Test with different serial numbers.
    $this->assertEquals('ENV-001-0000456', ApplicationHelpers::createApplicationNumber($submission, FALSE));
    $this->assertEquals('GRANTS-ENV-TYPE-00000456', ApplicationHelpers::createApplicationNumber($submission, TRUE));

    $this->assertEquals('ENV-002-0000456', ApplicationHelpers::createApplicationNumber($submission, FALSE));
  }

  /**
   * Test the getSerialFromApplicationNumber method.
   *
   * @covers ::getSerialFromApplicationNumber
   */
  public function testGetSerialFromApplicationNumber() {
    $applicationNumber = 'ENV-001-0000123';
    $result = ApplicationHelpers::getSerialFromApplicationNumber($applicationNumber);
    $this->assertEquals('123', $result);

    // Test with invalid application number.
    $applicationNumber = 'INVALID45633465';
    $result = ApplicationHelpers::getSerialFromApplicationNumber($applicationNumber);
    $this->assertEquals($result, $applicationNumber);
  }

  /**
   * @covers ::getApplicationNumberInEnvFormat
   */
  public function testGetApplicationNumberInEnvFormat() {
    $appParam = 'ENV';
    $typeId = '001';
    $serial = 123;

    $expected = 'ENV-001-0000123';
    $this->assertEquals($expected, ApplicationHelpersExposed::exposedGetApplicationNumberInEnvFormat($appParam, $typeId, $serial));

    $appParam = 'PROD';
    $expected = '001-0000123';
    $this->assertEquals($expected, ApplicationHelpersExposed::exposedGetApplicationNumberInEnvFormat($appParam, $typeId, $serial));
  }

  /**
   * @covers ::getApplicationNumberInEnvFormatOldFormat
   */
  public function testGetApplicationNumberInEnvFormatOldFormat() {
    $appParam = 'ENV';
    $typeId = 'TYPE';
    $serial = 123;

    $expected = 'GRANTS-ENV-TYPE-00000123';
    $this->assertEquals($expected, ApplicationHelpersExposed::exposedGetApplicationNumberInEnvFormatOldFormat($appParam, $typeId, $serial));

    $appParam = 'PROD';
    $expected = 'GRANTS-TYPE-00000123';
    $this->assertEquals($expected, ApplicationHelpersExposed::exposedGetApplicationNumberInEnvFormatOldFormat($appParam, $typeId, $serial));
  }

  /**
   * Test the hasBreakingChangesInNewerVersion method.
   *
   * @covers ::hasBreakingChangesInNewerVersion
   * @covers Drupal\grants_handler\ApplicationHelpers::getLatestApplicationForm
   */
  public function testHasBreakingChangesInNewerVersion() {
    // Mock the Webform entity.
    $webform = $this->createMock(Webform::class);
    $webform->method('uuid')->willReturn('parent-uuid');
    $webform->method('getThirdPartySettings')
      ->with('grants_metadata')
      ->will($this->onConsecutiveCalls(
        [
          'applicationType' => 'test-application-type',
        ],
        [
          'parent' => 'parent-uuid',
          'avus2BreakingChange' => TRUE,
        ],
        [
          'parent' => 'parent-uuid',
          'avus2BreakingChange' => TRUE,
        ],
        [
          'parent' => 'parent-uuid',
          'avus2BreakingChange' => TRUE,
        ],
        [
          'parent' => 'parent-uuid',
          'avus2BreakingChange' => TRUE,
        ]
      ));

    // Mock the entity type manager and storage.
    $entityTypeManager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $webformStorage = $this->createMock('Drupal\Core\Entity\EntityStorageInterface');
    $entityTypeManager->method('getStorage')
      ->with('webform')
      ->willReturn($webformStorage);

    // Mock the loading of webforms.
    $webformStorage->method('loadByProperties')
      ->willReturn([$webform]);

    // Set the entity type manager service.
    \Drupal::setContainer(new ContainerBuilder());
    \Drupal::getContainer()->set('entity_type.manager', $entityTypeManager);

    // Call the method and assert the result.
    $result = ApplicationHelpers::hasBreakingChangesInNewerVersion($webform);
    $this->assertTrue($result);

  }

  /**
   * Test the getAvailableApplicationNumber method.
   *
   * @covers \Drupal\grants_handler\ApplicationHelpers::getAvailableApplicationNumber
   */
  public function testGetAvailableApplicationNumber() {

    // Can't get this one to work...
  }

}
