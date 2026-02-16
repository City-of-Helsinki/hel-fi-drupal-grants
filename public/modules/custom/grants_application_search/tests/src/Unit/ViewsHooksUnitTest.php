<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application_search\Unit;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\Markup;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_application_search\Hook\ViewsHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\taxonomy\TermInterface;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * @coversDefaultClass \Drupal\grants_application_search\Hook\ViewsHooks
 */
final class ViewsHooksUnitTest extends UnitTestCase {

  // phpcs:disable
  private EntityTypeManagerInterface $entityTypeManager;
  private RendererInterface $renderer;
  private FormSettingsServiceInterface $formSettings;
  private LanguageManagerInterface $languageManager;
  private EntityRepositoryInterface $entityRepository;
  private ViewsHooksProxy $sut;
  // phpcs:enable

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->renderer = $this->createMock(RendererInterface::class);
    $this->formSettings = $this->createMock(FormSettingsServiceInterface::class);
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);

    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('en');

    $this->languageManager
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_CONTENT)
      ->willReturn($language);

    $this->formSettings
      ->method('getFormConfig')
      ->with('form_configuration')
      ->willReturn([
        'subvention_types' => ['2'],
        'applicant_types' => ['1'],
      ]);

    $this->formSettings
      ->method('getLabels')
      ->willReturnCallback(static function (array $values): array {
        $map = [
          '1' => 'Applicant label',
          '2' => 'Subvention label',
        ];
        $out = [];
        foreach ($values as $v) {
          $out[(string) $v] = $map[(string) $v] ?? ('Label ' . $v);
        }
        return $out;
      });

    $this->sut = new ViewsHooksProxy(
      $this->entityTypeManager,
      $this->renderer,
      $this->formSettings,
      $this->languageManager,
      $this->entityRepository,
    );
    $this->sut->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Tests parseData method with various inputs.
   *
   * @covers ::parseData
   */
  public function testParseData(): void {
    $this->assertNull($this->sut->callParseData(NULL));

    $dt = $this->sut->callParseData('1700000000');
    $this->assertInstanceOf(\DateTimeImmutable::class, $dt);
    $this->assertSame(1700000000, $dt->getTimestamp());
  }

  /**
   * Tests getFieldValue method with single and multiple values.
   *
   * @covers ::getFieldValue
   */
  public function testGetFieldValue(): void {
    $row = new ResultRow();
    // @phpstan-ignore property.notFound
    $row->_item = new class {
      // phpcs:disable
      public function getField(string $id): object {
        return new class($id) {
          public function __construct(private string $id) {}
          public function getValues(): array {
            return $this->id === 'foo' ? ['a', 'b'] : [];
          }
        };
      }
      // phpcs:enable
    };

    $view = $this->createMock(ViewExecutable::class);

    $this->assertSame('a', $this->sut->callGetFieldValue($view, $row, 'foo', FALSE));
    $this->assertSame(['a', 'b'], $this->sut->callGetFieldValue($view, $row, 'foo', TRUE));
    $this->assertNull($this->sut->callGetFieldValue($view, $row, 'missing', FALSE));
  }

  /**
   * Tests continuous application period markup generation.
   *
   * @covers ::buildApplicationPeriodMarkup
   * @covers ::getFieldValue
   * @covers ::parseData
   */
  public function testBuildApplicationPeriodMarkupContinuous(): void {
    $row = new ResultRow();
    // @phpstan-ignore property.notFound
    $row->_item = $this->buildSearchApiItem([
      'field_application_continuous' => ['1'],
    ]);

    $view = $this->createMock(ViewExecutable::class);
    $view->field = [
      'field_application_period' => $this->mockViewsFieldAdvancedRender(''),
    ];

    $markup = $this->sut->callBuildApplicationPeriodMarkup($view, $row);
    $this->assertInstanceOf(MarkupInterface::class, $markup);
    $this->assertStringContainsString('Continuous application', (string) $markup);
  }

  /**
   * Tests open/close date range markup generation.
   *
   * @covers ::buildApplicationPeriodMarkup
   * @covers ::getFieldValue
   * @covers ::parseData
   */
  public function testBuildApplicationPeriodMarkupOpenCloseRange(): void {
    $open = 1700000000;
    $close = 1700500000;

    $row = new ResultRow();
    // @phpstan-ignore property.notFound
    $row->_item = $this->buildSearchApiItem([
      'field_application_continuous' => ['0'],
      'application_open' => [(string) $open],
      'application_close' => [(string) $close],
    ]);

    $view = $this->createMock(ViewExecutable::class);
    $view->field = [
      'field_application_period' => $this->mockViewsFieldAdvancedRender(''),
    ];

    $expected = (new \DateTimeImmutable())->setTimestamp($open)->format('d.m.Y')
      . ' - ' .
      (new \DateTimeImmutable())->setTimestamp($close)->format('d.m.Y');

    $markup = $this->sut->callBuildApplicationPeriodMarkup($view, $row);
    $this->assertStringContainsString('Application period', (string) $markup);
    $this->assertStringContainsString($expected, (string) $markup);
  }

  /**
   * Tests advanced render fallback handling.
   *
   * @covers ::buildApplicationPeriodMarkup
   * @covers ::getFieldValue
   * @covers ::parseData
   */
  public function testBuildApplicationPeriodMarkupUsesAdvancedRenderFallback(): void {
    $row = new ResultRow();
    // @phpstan-ignore property.notFound
    $row->_item = $this->buildSearchApiItem([
      'field_application_continuous' => ['0'],
      'application_open' => [NULL],
      'application_close' => [NULL],
    ]);

    $view = $this->createMock(ViewExecutable::class);
    $view->field = [
      'field_application_period' => $this->mockViewsFieldAdvancedRender('01.01.2026 - 31.01.2026'),
    ];

    $markup = $this->sut->callBuildApplicationPeriodMarkup($view, $row);
    $this->assertStringContainsString('01.01.2026 - 31.01.2026', (string) $markup);
  }

  /**
   * Tests subvention type markup generation.
   *
   * @covers ::buildApplicationSubventionMarkup
   * @covers ::getFieldValue
   */
  public function testBuildApplicationSubventionMarkup(): void {
    $row = new ResultRow();
    // @phpstan-ignore property.notFound
    $row->_item = $this->buildSearchApiItem([
      'application_subvention_type' => ['2'],
    ]);

    $view = $this->createMock(ViewExecutable::class);

    $markup = $this->sut->callBuildApplicationSubventionMarkup($view, $row);
    $this->assertInstanceOf(MarkupInterface::class, $markup);
    $this->assertStringContainsString('Subvention label', (string) $markup);
  }

  /**
   * Tests entity ID extraction from field values.
   *
   * @covers ::getReferencedEntityIds
   */
  public function testGetReferencedEntityIds(): void {
    $row = new ResultRow();
    // @phpstan-ignore property.notFound
    $row->_item = $this->buildSearchApiItem([
      'field_x' => [
        ['target_id' => 10],
        ['target_id' => '10'],
        ['target_id' => 20],
        ['target_id' => 'foo'],
        ['target_id' => NULL],
      ],
    ]);

    $viewsField = new class {
      // phpcs:disable
      public function getValue(ResultRow $row): array {
        return [
          ['target_id' => 1],
          ['target_id' => '2'],
          ['target_id' => '2'],
          ['target_id' => 'x'],
        ];
      }
      // phpcs:enable
    };

    $view = $this->createMock(ViewExecutable::class);
    /** @phpstan-ignore-next-line */
    $view->field = [
      'field_x' => $viewsField,
    ];

    $this->assertSame([1, 2], $this->sut->callGetReferencedEntityIds($view, $row, 'field_x'));
  }

  /**
   * Tests entity override rendering with multiple entities.
   *
   * @covers ::buildOverride
   * @covers ::getReferencedEntityIds
   */
  public function testBuildOverrideRendersEntities(): void {
    $row = new ResultRow();

    $viewsField = new class {
      // phpcs:disable
      public function getValue(ResultRow $row): array {
        return [
          ['target_id' => 1],
          ['target_id' => 2],
        ];
      }
      // phpcs:enable
    };

    $view = $this->createMock(ViewExecutable::class);
    /** @phpstan-ignore-next-line */
    $view->field = [
      'field_target_group' => $viewsField,
    ];

    $term1 = $this->createMock(TermInterface::class);
    $term2 = $this->createMock(TermInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->with([1, 2])->willReturn([1 => $term1, 2 => $term2]);

    $view_builder = $this->createMock(EntityViewBuilderInterface::class);
    $view_builder->method('view')->willReturnOnConsecutiveCalls(
      ['#markup' => 'A'],
      ['#markup' => 'B'],
    );

    /** @phpstan-ignore-next-line */
    $this->entityTypeManager->method('getStorage')->with('taxonomy_term')->willReturn($storage);
    /** @phpstan-ignore-next-line */
    $this->entityTypeManager->method('getViewBuilder')->with('taxonomy_term')->willReturn($view_builder);

    /** @phpstan-ignore-next-line */
    $this->renderer->method('renderInIsolation')->willReturnOnConsecutiveCalls(
      Markup::create(' A '),
      Markup::create('B'),
    );

    $markup = $this->sut->callBuildOverride($view, $row, 'field_target_group');
    $this->assertInstanceOf(MarkupInterface::class, $markup);
    $this->assertSame('AB', (string) $markup);
  }

  /**
   * Build a Search API item.
   *
   * @param array $field_map
   *   Field mapping for building search API item.
   *
   * @return object
   *   Mock search API item with field values.
   */
  private function buildSearchApiItem(array $field_map): object {
    return new class($field_map) {
      // phpcs:disable
      public function __construct(private array $field_map) {}

      public function getField(string $id): ?object {
        if (!array_key_exists($id, $this->field_map)) {
          return NULL;
        }
        $values = $this->field_map[$id];

        return new class($values) {
          public function __construct(private array $values) {}
          public function getValues(): array {
            return $this->values;
          }
        };
      }
      // phpcs:enable
    };
  }

  /**
   * Mock Views field render.
   *
   * @param string $value
   *   Value to return from advanced render.
   *
   * @return object
   *   Mock views field with advanced render capability.
   */
  private function mockViewsFieldAdvancedRender(string $value): object {
    return new class($value) {
      // phpcs:disable
      public function __construct(private string $value) {}
      public function advancedRender(ResultRow $row): string {
        return $this->value;
      }
      // phpcs:enable
    };
  }

}

/**
 * Proxy to expose protected methods for unit testing.
 */
final class ViewsHooksProxy extends ViewsHooks {
  // phpcs:disable
  public function callBuildApplicationPeriodMarkup(ViewExecutable $view, ResultRow $row): MarkupInterface {
    return parent::buildApplicationPeriodMarkup($view, $row);
  }

  public function callBuildApplicationSubventionMarkup(ViewExecutable $view, ResultRow $row): ?MarkupInterface {
    return parent::buildApplicationSubventionMarkup($view, $row);
  }

  public function callGetFieldValue(ViewExecutable $view, ResultRow $row, string $field_id, bool $multivalue = FALSE): mixed {
    return parent::getFieldValue($view, $row, $field_id, $multivalue);
  }

  public function callParseData(?string $value): ?\DateTimeImmutable {
    return parent::parseData($value);
  }

  public function callBuildOverride(ViewExecutable $view, ResultRow $row, string $override): ?MarkupInterface {
    return parent::buildOverride($view, $row, $override);
  }

  public function callGetReferencedEntityIds(ViewExecutable $view, ResultRow $row, string $field_id): array {
    return parent::getReferencedEntityIds($view, $row, $field_id);
  }
  // phpcs:enable
}
