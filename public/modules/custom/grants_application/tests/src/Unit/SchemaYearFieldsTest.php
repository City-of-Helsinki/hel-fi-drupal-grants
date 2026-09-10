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
   * A year field must also declare its type, length and accepted range.
   */
  public function testYearFieldsAreConstrained(): void {
    $offenders = [];
    $checked = 0;

    foreach (glob(__DIR__ . '/../../../fixtures/*/schema.json') ?: [] as $path) {
      $formName = basename(dirname($path));
      $contents = file_get_contents($path);
      $this->assertIsString($contents, sprintf('%s is readable', $path));
      $schema = json_decode($contents, TRUE, flags: JSON_THROW_ON_ERROR);

      foreach ($this->collectFieldsWithFormat($schema, 'year') as $name => $field) {
        $checked++;

        if (($field['type'] ?? NULL) !== 'string' || ($field['maxLength'] ?? NULL) !== 4) {
          $offenders[] = sprintf(
            '%s: %s declares type %s and maxLength %s',
            $formName,
            $name,
            var_export($field['type'] ?? NULL, TRUE),
            var_export($field['maxLength'] ?? NULL, TRUE)
          );
          continue;
        }
        if (!isset($field['pattern'])) {
          $offenders[] = sprintf('%s: %s declares no accepted range', $formName, $name);
          continue;
        }
        if (!$this->acceptsOnlyYears($field['pattern'])) {
          $offenders[] = sprintf(
            '%s: %s has a pattern accepting something other than a four-digit year: %s',
            $formName,
            $name,
            $field['pattern']
          );
        }
      }
    }

    $this->assertNotSame(0, $checked, 'The fixtures declare year fields');
    $this->assertSame([], $offenders, "Year fields that are not fully constrained:\n" . implode("\n", $offenders));
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
