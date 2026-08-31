<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the postal code fields of the form schemas.
 *
 * @group grants_application
 */
final class SchemaPostalCodeFieldsTest extends UnitTestCase {

  /**
   * A postal code field must also declare its type and length.
   */
  public function testPostalCodeFieldsAreConstrained(): void {
    $offenders = [];
    $checked = 0;

    foreach (glob(__DIR__ . '/../../../fixtures/*/schema.json') ?: [] as $path) {
      $formName = basename(dirname($path));
      $contents = file_get_contents($path);
      $this->assertIsString($contents, sprintf('%s is readable', $path));
      $schema = json_decode($contents, TRUE, flags: JSON_THROW_ON_ERROR);

      foreach ($this->collectFieldsWithFormat($schema, 'postal-code') as $name => $field) {
        $checked++;
        if (($field['type'] ?? NULL) !== 'string' || ($field['maxLength'] ?? NULL) !== 5) {
          $offenders[] = sprintf(
            '%s: %s declares type %s and maxLength %s',
            $formName,
            $name,
            var_export($field['type'] ?? NULL, TRUE),
            var_export($field['maxLength'] ?? NULL, TRUE)
          );
        }
      }
    }

    $this->assertNotSame(0, $checked, 'The fixtures declare postal code fields');
    $this->assertSame([], $offenders, "Postal code fields that are not fully constrained:\n" . implode("\n", $offenders));
  }

  /**
   * Collects the fields declaring a given format.
   *
   * @param mixed $node
   *   The schema, or a part of it.
   * @param string $format
   *   The format to look for.
   * @param string $path
   *   The path walked so far.
   *
   * @return array<string, array<string, mixed>>
   *   The matching fields, keyed by path.
   */
  private function collectFieldsWithFormat(mixed $node, string $format, string $path = ''): array {
    if (!is_array($node)) {
      return [];
    }

    $found = [];
    if (($node['format'] ?? NULL) === $format) {
      $found[$path] = $node;
    }

    foreach ($node as $key => $value) {
      if (is_array($value)) {
        $childPath = $path === '' ? (string) $key : $path . '.' . $key;
        $found = array_merge($found, $this->collectFieldsWithFormat($value, $format, $childPath));
      }
    }

    return $found;
  }

}
