<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests that the uiSchemas address fields that exist.
 *
 * @group grants_application
 */
final class UiSchemaPathsTest extends UnitTestCase {

  /**
   * Every uiSchema entry must address a field that the schema declares.
   *
   * A uiSchema entry is matched to the schema by path, so a field renamed on
   * one side and not the other leaves the entry inert rather than failing
   * loudly. The field then renders with default widgets and no tooltips.
   */
  public function testUiSchemaEntriesAddressExistingFields(): void {
    $dead = [];
    $checked = 0;

    foreach (glob(__DIR__ . '/../../../fixtures/*/uiSchema.json') ?: [] as $path) {
      $formName = basename(dirname($path));
      $uiSchema = $this->readJson($path);
      $schema = $this->readJson(dirname($path) . '/schema.json');

      foreach ($this->collectFieldPaths($uiSchema) as $fieldPath) {
        $checked++;
        if (!$this->resolve($schema, $schema, $fieldPath)) {
          $dead[] = sprintf('%s: %s', $formName, implode('.', $fieldPath));
        }
      }
    }

    $this->assertGreaterThan(1000, $checked, 'The uiSchemas declare field entries');

    sort($dead);
    $this->assertSame([], $dead, sprintf(
      "These uiSchema entries address a field the schema does not declare, so nothing renders from them:\n%s",
      implode("\n", $dead)
    ));
  }

  /**
   * Reads and decodes a fixture file.
   *
   * @param string $path
   *   The path to the file.
   *
   * @return array<string, mixed>
   *   The decoded contents.
   */
  private function readJson(string $path): array {
    $contents = file_get_contents($path);
    $this->assertIsString($contents, sprintf('%s is readable', $path));

    return json_decode($contents, TRUE, flags: JSON_THROW_ON_ERROR);
  }

  /**
   * Collects the paths of the uiSchema entries that configure a field.
   *
   * A node counts as a field entry when it carries at least one `ui:` or
   * `misc:` directive.
   *
   * @param mixed $node
   *   The uiSchema, or a part of it.
   * @param array<int, string> $path
   *   The path walked so far.
   *
   * @return array<int, array<int, string>>
   *   The field paths.
   */
  private function collectFieldPaths(mixed $node, array $path = []): array {
    if (!is_array($node)) {
      return [];
    }

    $found = [];
    $isFieldEntry = (bool) array_filter(
      array_keys($node),
      fn ($key) => str_starts_with((string) $key, 'ui:') || str_starts_with((string) $key, 'misc:'),
    );
    if ($isFieldEntry && $path) {
      $found[] = $path;
    }

    foreach ($node as $key => $child) {
      if (str_starts_with((string) $key, 'ui:') || str_starts_with((string) $key, 'misc:')) {
        continue;
      }
      $found = array_merge($found, $this->collectFieldPaths($child, [...$path, (string) $key]));
    }

    return $found;
  }

  /**
   * Resolves a uiSchema path to the schema nodes it addresses.
   *
   * A path can address more than one node, since a section may be declared both
   * directly and inside a conditional branch.
   *
   * @param array<string, mixed> $node
   *   The node to walk from.
   * @param array<string, mixed> $root
   *   The root schema, used to look up `$ref`s.
   * @param array<int, string> $path
   *   The remaining path to walk.
   *
   * @return array<int, array<string, mixed>>
   *   The nodes the path addresses.
   */
  private function resolve(array $node, array $root, array $path): array {
    $nodes = [$this->dereference($node, $root)];

    foreach ($path as $key) {
      $next = [];
      foreach ($nodes as $current) {
        $next = array_merge($next, $this->childrenAt($current, $root, $key));
      }
      $nodes = $next;
      if (!$nodes) {
        return [];
      }
    }

    return $nodes;
  }

  /**
   * Finds the children a key addresses, looking through conditional branches.
   *
   * @param array<string, mixed> $node
   *   The node to look in.
   * @param array<string, mixed> $root
   *   The root schema, used to look up `$ref`s.
   * @param string $key
   *   The key to look for.
   *
   * @return array<int, array<string, mixed>>
   *   The matching children.
   */
  private function childrenAt(array $node, array $root, string $key): array {
    $found = [];

    if (in_array($key, ['items', 'additionalItems'], TRUE)) {
      $child = $node[$key] ?? NULL;
      if (is_array($child) && array_is_list($child)) {
        $child = $child[0] ?? NULL;
      }
      if (is_array($child)) {
        $found[] = $this->dereference($child, $root);
      }
    }
    elseif (isset($node['properties'][$key]) && is_array($node['properties'][$key])) {
      $found[] = $this->dereference($node['properties'][$key], $root);
    }

    foreach ($node['allOf'] ?? [] as $branch) {
      if (!is_array($branch)) {
        continue;
      }
      $branch = $this->dereference($branch, $root);
      foreach ([$branch, $branch['then'] ?? NULL, $branch['else'] ?? NULL] as $subschema) {
        if (is_array($subschema)) {
          $found = array_merge($found, $this->childrenAt($this->dereference($subschema, $root), $root, $key));
        }
      }
    }

    return $found;
  }

  /**
   * Resolves a `$ref`, merging any keywords declared alongside it.
   *
   * @param array<string, mixed> $node
   *   The node to resolve.
   * @param array<string, mixed> $root
   *   The root schema.
   *
   * @return array<string, mixed>
   *   The resolved node.
   */
  private function dereference(array $node, array $root): array {
    $seen = [];

    while (isset($node['$ref']) && !in_array($node['$ref'], $seen, TRUE)) {
      $seen[] = $ref = $node['$ref'];
      $target = $root;
      foreach (explode('/', ltrim($ref, '#/')) as $part) {
        if (!is_array($target) || !isset($target[$part])) {
          $target = [];
          break;
        }
        $target = $target[$part];
      }
      unset($node['$ref']);
      $node = array_merge(is_array($target) ? $target : [], $node);
    }

    return $node;
  }

}
