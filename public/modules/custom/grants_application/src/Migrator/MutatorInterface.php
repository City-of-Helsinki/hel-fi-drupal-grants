<?php

declare(strict_types=1);

namespace Drupal\grants_application\Migrator;

/**
 * Interface for schema version mutators.
 */
interface MutatorInterface {

  /**
   * Apply the mutation to the document content.
   *
   * @param array $content
   *   The document content.
   *
   * @return array
   *   The mutated document content.
   */
  public function mutate(array $content): array;

  /**
   * The schema version this mutator applies from.
   *
   * @return string
   *   The source version.
   */
  public function appliesFrom(): string;

  /**
   * The schema version this mutator produces.
   *
   * @return string
   *   The target version.
   */
  public function appliesTo(): string;

}
