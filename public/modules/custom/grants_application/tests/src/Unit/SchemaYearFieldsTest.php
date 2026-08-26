<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the year fields of the form schemas.
 *
 * @group grants_application
 */
final class SchemaYearFieldsTest extends UnitTestCase {

  /**
   * Tests that year fields follow convention.
   */
  public function testYearFieldsAreConstrained(): void {
    $offenders = [];
    $checked = 0;

    foreach (glob(__DIR__ . '/../../../fixtures/*/schema.json') ?: [] as $path) {
      $formName = basename(dirname($path));
      $contents = file_get_contents($path);
      $this->assertIsString($contents, sprintf('%s is readable', $path));
      $schema = json_decode($contents, TRUE, flags: JSON_THROW_ON_ERROR);

      foreach ($this->collectYearFields($schema) as $name => $field) {
        $checked++;
        $missing = array_diff(['format', 'maxLength', 'pattern'], array_keys($field));

        if ($missing) {
          $offenders[] = sprintf('%s: %s is missing %s', $formName, $name, implode(', ', $missing));
          continue;
        }
        if ($field['format'] !== 'year' || $field['maxLength'] !== 4) {
          $offenders[] = sprintf('%s: %s declares format %s and maxLength %s', $formName, $name, $field['format'], $field['maxLength']);
          continue;
        }
        if (!$this->acceptsOnlyYears($field['pattern'])) {
          $offenders[] = sprintf('%s: %s has a pattern that accepts something other than a four-digit year: %s', $formName, $name, $field['pattern']);
        }
      }
    }

    $this->assertNotSame(0, $checked, 'The fixtures declare year fields');
    $this->assertSame([], $offenders, "Year fields that are not fully constrained:\n" . implode("\n", $offenders));
  }

  /**
   * Collects the year fields of a schema.
   *
   * @param array<string, mixed> $schema
   *   The decoded schema.
   *
   * @return array<string, array<string, mixed>>
   *   The year fields, keyed by definition and field name.
   */
  private function collectYearFields(array $schema): array {
    $fields = [];

    foreach ($schema['definitions'] ?? [] as $definitionName => $definition) {
      if (!is_array($definition)) {
        continue;
      }
      foreach ($definition['properties'] ?? [] as $name => $field) {
        $isYear = str_ends_with((string) $name, '_issuer_year') || $name === 'year';
        if ($isYear && is_array($field) && ($field['type'] ?? NULL) === 'string') {
          $fields[$definitionName . '.' . $name] = $field;
        }
      }
    }

    return $fields;
  }

  /**
   * Checks that a pattern accepts four-digit years and nothing else.
   *
   * @param string $pattern
   *   The pattern from the field's schema.
   *
   * @return bool
   *   TRUE when every accepted value is a four-digit year.
   */
  private function acceptsOnlyYears(string $pattern): bool {
    $delimited = '/' . str_replace('/', '\/', $pattern) . '/';
    $accepted = 0;

    foreach (['0', '99', '999', '10000', 'abcd', '20a4', '', ' 2024', '2024 '] as $rejected) {
      if (@preg_match($delimited, $rejected) !== 0) {
        return FALSE;
      }
    }

    for ($year = 1000; $year <= 9999; $year++) {
      if (preg_match($delimited, (string) $year) === 1) {
        $accepted++;
      }
    }

    return $accepted > 0;
  }

}
