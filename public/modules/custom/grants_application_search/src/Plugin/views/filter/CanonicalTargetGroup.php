<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Plugin\views\filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Attribute\ViewsFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Canonical target group filter.
 *
 * @todo UHF-12853: Review this class when removing the webform functionality.
 */
#[ViewsFilter('canonical_target_group_select')]
final class CanonicalTargetGroup extends CanonicalSelectFilterBase {

  private const string VOCABULARY = 'target_group';

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
    return 'canonical_target_group';
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
      ->loadTree(self::VOCABULARY, 0, NULL, TRUE);

    $options = [];
    foreach ($terms as $term) {
      if (!$term->isPublished()) {
        continue;
      }
      $translated = $term->hasTranslation($langcode)
        ? $term->getTranslation($langcode)
        : $term;

      $options[(string) $term->id()] = $translated->label();
    }

    return $options;
  }

}
