<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel;

use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\JsonSchemaValidator;

/**
 * @coversDefaultClass \Drupal\grants_application\JsonSchemaValidator
 *
 * @group grants_application
 */
final class JsonSchemaValidatorTest extends KernelTestBase {

  protected static $modules = [
    'grants_application',
  ];

  /**
   * Test validator.
   */
  public function testValidator(): void {
    /** @var JsonSchemaValidator $validator */
    $validator = $this->container->get(JsonSchemaValidator::class);
    $schema = '{"$id": "https://example.com/address.schema.json","$schema": "https://json-schema.org/draft/2020-12/schema","description": "description","type": "object","properties": {"test": {"type": "string"}}}';

    $value1 = '{}';
    $result = $validator->validate(json_decode($value1), json_decode($schema));
    $this->assertTrue($result);

    $value2 = '{"test": "value"}';
    $result2 = $validator->validate(json_decode($value2), json_decode($schema));
    $this->assertIsNotArray($result2);
    $this->assertTrue($result2);

    $value3= '{"test": 12345}';
    $result3 = $validator->validate(json_decode($value3), json_decode($schema));
    $this->assertIsArray($result3);
    $this->assertTrue(isset($result3[0]['message']));
  }

  /**
   * Test ID58 with real schema and real test data.
   */
  public function testID58FormValidation(): void {
    $settingsService = $this->container->get(FormSettingsService::class);
    $schema = $settingsService->getFormSettings(58)->getSchema();
    $data = file_get_contents(__DIR__ . '/../../fixtures/reactForm/form58.json');

    $validator = $this->container->get(JsonSchemaValidator::class);
    $result = $validator->validate(json_decode($data), json_decode(json_encode($schema)));

    $this->assertTrue($result);
  }

}
