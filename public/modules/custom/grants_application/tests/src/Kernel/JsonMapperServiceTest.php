<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel;

use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\JsonSchemaValidator;
use Drupal\grants_application\Mapper\JsonMapper;
use Drupal\grants_application\Mapper\JsonMapperService;
use Drupal\grants_application\User\GrantsProfile;
use Drupal\grants_application\User\UserInformationService;

/**
 * @coversDefaultClass \Drupal\grants_application\JsonSchemaValidator
 *
 * @group grants_application
 */
final class JsonMapperServiceTest extends KernelTestBase {

  protected static $modules = [
    'grants_application',
  ];

  /**
   * A simple validator test.
   */
  public function testMapperService(): void {
    $testData = $this->getAllDatasources('form58.json');

    $mockUserInfoService = $this->createMock(UserInformationService::class);
    $mockUserInfoService->method('getGrantsProfileContent')->willReturn(new GrantsProfile($testData['grants_profile_array']));
    $mockUserInfoService->method('getGrantsProfileAttachments')->willReturn($this->getBankFile());
    $mockUserInfoService->method('getApplicantTypeId')->willReturn(2);
    $mockUserInfoService->method('getUserData')->willReturn($testData['user']);
    $mockUserInfoService->method('getSelectedCompany')->willReturn($testData['company']);
    $mockUserInfoService->method('getUserProfileData')->willReturn($testData['user_profile']);
    $settingsService = $this->container->get(FormSettingsService::class);
    $mapperService = new JsonMapperService($mockUserInfoService, $settingsService);


    // $schema = $settingsService->getFormSettings(58)->getSchema();
    $formData = json_decode(file_get_contents(__DIR__ . '/../../fixtures/reactForm/form58.json'), TRUE);
    $bankFile = $mapperService->getSelectedBankFile($formData);

    $mappedData = $mapperService->handleMapping(
      "58",
      "KERNELTEST-058-0000001",
      $formData,
      $bankFile,
      TRUE,
      "registered_community",
    );

    $this->assertTrue(isset($mappedData['events']));
    $this->assertTrue(isset($mappedData['messages']));
    $this->assertTrue(isset($mappedData['statusUpdates']));
    $this->assertEquals(FALSE, $mappedData['formUpdate']);

    $this->assertEquals('DRAFT', $mappedData['compensation']['applicationInfoArray'][6]['value']);

    // Modify the document as it had been updated by Avus2/integration.
    $oldDocument = [
      'content' => $mappedData,
    ];

    $oldDocument['content']['messages'] = [['data' => 'test-message1']];
    $oldDocument['content']['statusUpdates'] = [['data' => 'test-status1']];
    $oldDocument['content']['events'] = [['data' => 'test-event1']];
    $oldDocument['content']['compensation']['applicationInfoArray'][6]['value'] = 'RECEIVED';

    $mappedData = $mapperService->handleMappingForPatchRequest(
      "58",
      "KERNELTEST-058-0000001",
      $formData,
      'registered_community',
      $oldDocument
    );

    $this->assertEquals('RECEIVED', $mappedData['compensation']['applicationInfoArray'][6]['value']);
    $this->assertTrue(!empty($mappedData['events']));
    $this->assertTrue(!empty($mappedData['messages']));
    $this->assertTrue(!empty($mappedData['statusUpdates']));
    $this->assertEquals(TRUE, $mappedData['formUpdate']);
  }

  /**
   * Test ID58 with real schema and real test data.
   */
  // public function testID58FormValidation(): void {
    /*
    $settingsService = $this->container->get(FormSettingsService::class);
    $schema = $settingsService->getFormSettings(58)->getSchema();
    $data = file_get_contents(__DIR__ . '/../../fixtures/reactForm/form58.json');

    $validator = $this->container->get(JsonSchemaValidator::class);
    $result = $validator->validate(json_decode($data), json_decode(json_encode($schema)));

    $this->assertTrue($result);
    */
  // }

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
   * Return a file in format used by ATV.
   *
   * @return array[]
   *   Array of bank files
   */
  private function getBankFile() {
    return [
      [
        'id' => '123456',
        'created_at' => '',
        'updated_at' => '',
        'filename' => 'dummy.pdf',
        'media_type' => 'application/octet-stream',
        'size' => '12345',
        'href' => 'https://example.com/dummy.pdf',
      ],
    ];
  }
}
