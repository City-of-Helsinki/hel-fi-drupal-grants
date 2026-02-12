<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application_search\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\grants_application_search\Plugin\views\filter\CanonicalTaxonomySelectFilterBase;
use Drupal\taxonomy\TermStorageInterface;

/**
 * Tests the taxonomy selection filters.
 *
 * @group grants_application_search
 */
final class CanonicalTaxonomySelectFilterBaseTest extends CanonicalUnitTestBase {

  /**
   * Tests buildSelectOptions() filters and translates taxonomy terms.
   */
  public function testBuildSelectOptionsFiltersAndTranslates(): void {
    $published_translated_term = $this->termStub(
      published: TRUE,
      id: '10',
      label: 'Default 10',
      has_translation: TRUE,
      translated_label: 'Translated 10',
    );

    $unpublished_term = $this->termStub(
      published: FALSE,
      id: '11',
      label: 'Default 11',
      has_translation: FALSE,
      translated_label: NULL,
    );

    $published_no_translation_term = $this->termStub(
      published: TRUE,
      id: '12',
      label: 'Default 12',
      has_translation: FALSE,
      translated_label: NULL,
    );

    $term_storage = $this->createMock(TermStorageInterface::class);
    $term_storage->method('loadTree')->willReturn([
      $published_translated_term,
      $unpublished_term,
      $published_no_translation_term,
    ]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager
      ->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($term_storage);

    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('fi');

    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_INTERFACE)
      ->willReturn($language);

    $filter = new class([], 'id', [], $entity_type_manager, $language_manager) extends CanonicalTaxonomySelectFilterBase {
      // phpcs:disable
      protected function getCanonicalField(): string {
        return 'canonical_target_group';
      }

      protected function getVocabulary(): string {
        return 'target_group';
      }
      // phpcs:enable
    };

    $options = $this->invokeProtected($filter, 'buildSelectOptions');
    $this->assertSame([
      '10' => 'Translated 10',
      '12' => 'Default 12',
    ], $options);
  }

  /**
   * Tests buildSelectOptions() skips terms with null option values.
   */
  public function testBuildSelectOptionsSkipsNullOptionValue(): void {
    $published_term = $this->termStub(
      published: TRUE,
      id: '10',
      label: 'Default 10',
      has_translation: FALSE,
      translated_label: NULL,
    );

    $term_storage = $this->createMock(TermStorageInterface::class);
    $term_storage->method('loadTree')->willReturn([$published_term]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->with('taxonomy_term')->willReturn($term_storage);

    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('fi');

    $language_manager = $this->createMock(LanguageManagerInterface::class);
    $language_manager
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_INTERFACE)
      ->willReturn($language);

    $filter = new class([], 'id', [], $entity_type_manager, $language_manager) extends CanonicalTaxonomySelectFilterBase {
      // phpcs:disable
      protected function getCanonicalField(): string {
        return 'canonical_subvention_type';
      }
      protected function getVocabulary(): string {
        return 'avustuslaji';
      }
      protected function getOptionValue(string $term_id): ?string {
        return NULL;
      }
      // phpcs:enable
    };

    $options = $this->invokeProtected($filter, 'buildSelectOptions');

    $this->assertSame([], $options);
  }

  /**
   * Creates a mock taxonomy term stub for testing.
   *
   * @param bool $published
   *   Whether the term should appear as published.
   * @param string $id
   *   The term ID to return.
   * @param string $label
   *   The default label for the term.
   * @param bool $has_translation
   *   Whether the term has a translation for the current language.
   * @param string|null $translated_label
   *   The translated label, or NULL if no translation.
   *
   * @return object
   *   A mock term object with the specified properties.
   */
  private function termStub(
    bool $published,
    string $id,
    string $label,
    bool $has_translation,
    ?string $translated_label,
  ): object {
    return new class($published, $id, $label, $has_translation, $translated_label) {
      // phpcs:disable
      public function __construct(
        private readonly bool $published,
        private readonly string $id,
        private readonly string $label,
        private readonly bool $has_translation,
        private readonly ?string $translated_label,
      ) {}

      public function isPublished(): bool {
        return $this->published;
      }

      public function id(): string {
        return $this->id;
      }

      public function hasTranslation(string $langcode): bool {
        return $this->has_translation;
      }

      public function getTranslation(string $langcode): object {
        $label = $this->translated_label ?? $this->label;
        return new class($label) {

          public function __construct(private readonly string $label) {}

          public function label(): string {
            return $this->label;
          }

        };
      }

      public function label(): string {
        return $this->label;
      }
      // phpcs:enable
    };
  }

}
