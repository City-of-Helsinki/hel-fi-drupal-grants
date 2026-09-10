<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the conditional branches of the form schemas.
 *
 * @group grants_application
 */
final class SchemaConditionalsTest extends UnitTestCase {

  /**
   * Every `if` must require the fields it constrains.
   */
  public function testConditionalsRequireTheirGatingFields(): void {
    $offenders = [];

    foreach (glob(__DIR__ . '/../../../fixtures/*/schema.json') ?: [] as $path) {
      $contents = file_get_contents($path);
      $this->assertIsString($contents, sprintf('%s is readable', $path));
      $schema = json_decode($contents, TRUE, flags: JSON_THROW_ON_ERROR);
      $formName = basename(dirname($path));

      foreach ($this->collectConditions($schema) as $condition) {
        foreach ($this->collectUnrequiredProperties($condition) as $property) {
          $offenders[] = sprintf('%s: %s', $formName, $property);
        }
      }
    }

    $this->assertSame([], $offenders, sprintf(
      "The following `if` subschemas constrain properties without requiring them:\n%s",
      implode("\n", $offenders)
    ));
  }

  /**
   * Collects every `if` subschema of a schema.
   *
   * @param mixed $node
   *   The schema, or a part of it.
   *
   * @return array<int, array<string, mixed>>
   *   The `if` subschemas.
   */
  private function collectConditions(mixed $node): array {
    if (!is_array($node)) {
      return [];
    }

    $conditions = isset($node['if']) && is_array($node['if']) ? [$node['if']] : [];

    foreach ($node as $key => $child) {
      if ($key !== 'if') {
        $conditions = array_merge($conditions, $this->collectConditions($child));
      }
    }

    return $conditions;
  }

  /**
   * Collects the constrained but unrequired properties of a condition.
   *
   * @param array<string, mixed> $condition
   *   An `if` subschema, or a nested part of one.
   * @param string $path
   *   The path to the condition, used for reporting.
   *
   * @return array<int, string>
   *   The paths of the offending properties.
   */
  private function collectUnrequiredProperties(array $condition, string $path = ''): array {
    if (!isset($condition['properties']) || !is_array($condition['properties'])) {
      return [];
    }

    $required = $condition['required'] ?? [];
    $offenders = [];

    foreach ($condition['properties'] as $name => $subschema) {
      $childPath = $path === '' ? $name : $path . '.' . $name;

      if (!in_array($name, $required, TRUE)) {
        $offenders[] = $childPath;
      }

      if (is_array($subschema)) {
        $offenders = array_merge($offenders, $this->collectUnrequiredProperties($subschema, $childPath));
      }
    }

    return $offenders;
  }

}
