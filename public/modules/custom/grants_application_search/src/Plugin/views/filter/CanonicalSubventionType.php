<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\grants_application_search\Plugin\search_api\processor\CanonicalFields;
use Drupal\views\Attribute\ViewsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Canonical subvention type filter.
 *
 * @todo UHF-12853: Review this class when removing the webform functionality.
 */
#[ViewsFilter('canonical_subvention_type_select')]
final class CanonicalSubventionType extends CanonicalSelectFilterBase {

  private const string VOCABULARY = 'avustuslaji';

  /**
   * Cached reverse map: canonical ID -> term ID.
   *
   * @var array|null
   */
  private static ?array $reverseMap = NULL;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LanguageManagerInterface $languageManager,
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
  ): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('language_manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getCanonicalField(): string {
    return 'canonical_subvention_type';
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSelectOptions(): array {
    $langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)
      ->getId();

    $reverse = $this->getReverseMap();

    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadTree(self::VOCABULARY, 0, NULL, TRUE);

    $options = [];
    foreach ($terms as $term) {
      if (!$term->isPublished()) {
        continue;
      }
      $term_id = (string) $term->id();

      // Only terms that exist in the mapping are exposed.
      $canonical = $reverse[$term_id] ?? NULL;
      if ($canonical === NULL) {
        continue;
      }

      $translated = $term->hasTranslation($langcode)
        ? $term->getTranslation($langcode)
        : $term;

      $options[$canonical] = $translated->label();
    }

    return $options;
  }

  /**
   * Builds a reverse map: term ID -> canonical ID.
   *
   * @return array
   *   Returns the reversed map.
   */
  private function getReverseMap(): array {
    if (self::$reverseMap !== NULL) {
      return self::$reverseMap;
    }

    $reverse = [];
    foreach (CanonicalFields::SUBVENTION_TYPE_MAP as $term_id => $canonical_id) {
      $reverse[(string) $term_id] = (string) $canonical_id;
    }

    return self::$reverseMap = $reverse;
  }

}
