<?php

declare(strict_types=1);

namespace Drupal\grants_application\Migrator;

/**
 * Migrates ATV document content between schema versions.
 */
class SchemaVersionMigrator {

  /**
   * Migrate document content from a stored version to the current version.
   *
   * If the versions match, the content is returned unchanged. Otherwise,
   * mutators registered for each version step are applied in sequence.
   *
   * @param array $content
   *   The document content.
   * @param string $storedVersion
   *   The schema version the content was last written with.
   * @param string $currentVersion
   *   The schema version from the current schema.json.
   *
   * @return array
   *   The migrated content.
   *
   * @throws \RuntimeException
   *   Thrown when migration cannot be completed.
   */
  public function migrate(array $content, string $storedVersion, string $currentVersion): array {
    if (version_compare($storedVersion, $currentVersion, '=')) {
      return $content;
    }

    $mutators = $this->getMutators($storedVersion, $currentVersion);

    foreach ($mutators as $mutator) {
      $content = $mutator->mutate($content);
    }

    return $content;
  }

  /**
   * Get the ordered list of mutators to apply between two versions.
   *
   * @param string $fromVersion
   *   The source schema version.
   * @param string $toVersion
   *   The target schema version.
   *
   * @return \Drupal\grants_application\Migrator\MutatorInterface[]
   *   Ordered list of mutators. Currently empty — add mutators in
   *   src/Migrator/Mutators/ as schemas evolve.
   */
  private function getMutators(string $fromVersion, string $toVersion): array {
    // Future mutators will be collected and ordered here.
    // Each mutator lives in src/Migrator/Mutators/ and implements
    // MutatorInterface. They are selected when appliesFrom() <= $fromVersion
    // and appliesTo() <= $toVersion, then sorted by version.
    return [];
  }

}
