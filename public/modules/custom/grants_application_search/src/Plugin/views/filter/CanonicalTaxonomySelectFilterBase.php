<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for canonical select filters.
 *
 * @todo UHF-12853: Review this class when removing the webform functionality.
 *
 * @phpstan-consistent-constructor
 */
abstract class CanonicalTaxonomySelectFilterBase extends CanonicalSelectFilterBase {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly LanguageManagerInterface $languageManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
    );
  }

  /**
   * Vocabulary machine name to load options from.
   */
  abstract protected function getVocabulary(): string;

  /**
   * Returns select option value for a term.
   */
  protected function getOptionValue(string $term_id): ?string {
    return $term_id;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSelectOptions(): array {
    $langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)
      ->getId();

    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadTree($this->getVocabulary(), 0, NULL, TRUE);

    $options = [];
    foreach ($terms as $term) {
      if (!$term->isPublished()) {
        continue;
      }

      $term_id = (string) $term->id();
      $value = $this->getOptionValue($term_id);
      if ($value === NULL) {
        continue;
      }

      $translated = $term->hasTranslation($langcode)
        ? $term->getTranslation($langcode)
        : $term;

      $options[$value] = $translated->label();
    }

    return $options;
  }

}
