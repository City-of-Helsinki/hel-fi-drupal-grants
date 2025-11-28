<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel;

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

  public function testValidator(): void {
    /** @var JsonSchemaValidator $validator */
    $validator = $this->container->get(JsonSchemaValidator::class);
    $schema = '{"$id": "https://example.com/address.schema.json","$schema": "https://json-schema.org/draft/2020-12/schema","description": "An address similar to http://microformats.org/wiki/h-card","type": "object","properties": {"test": {"type": "string"}}}';
    $result = $validator->validate(json_decode('{"test": "value"}'), json_decode($schema));
  }
}
