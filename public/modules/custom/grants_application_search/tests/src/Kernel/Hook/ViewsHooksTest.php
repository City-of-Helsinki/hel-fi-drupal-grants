<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application_search\Kernel;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\RendererInterface;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_application_search\Hook\ViewsHooks;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\TermInterface;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Kernel tests for ViewsHooks.
 *
 * @group grants_application_search
 */
final class ViewsHooksTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'grants_application_search_test',
  ];

  /**
   * The system under test.
   *
   * @var \Drupal\Tests\grants_application_search\Kernel\TestableViewsHooks
   */
  private TestableViewsHooks $sut;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Mock renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private RendererInterface $renderer;

  /**
   * Mock form settings service.
   *
   * @var \Drupal\grants_application\Form\FormSettingsServiceInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private FormSettingsServiceInterface $formSettings;

  /**
   * Mock language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private LanguageManagerInterface $languageManager;

  /**
   * Mock entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  private EntityRepositoryInterface $entityRepository;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Set up mock services.
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->renderer = $this->createMock(RendererInterface::class);
    $this->formSettings = $this->createMock(FormSettingsServiceInterface::class);
    $this->languageManager = $this->createMock(LanguageManagerInterface::class);
    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);

    // Mock language interface to return English.
    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn('en');

    $this->languageManager
      ->method('getCurrentLanguage')
      ->with(LanguageInterface::TYPE_CONTENT)
      ->willReturn($language);

    // Mock form configuration data.
    $this->formSettings
      ->method('getFormConfig')
      ->with('form_configuration')
      ->willReturn([
        'subvention_types' => ['2'],
        'applicant_types' => ['1'],
      ]);

    // Mock label translation callback.
    $this->formSettings
      ->method('getLabels')
      ->willReturnCallback(static function (array $values, string $langcode): array {
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

    // Initialize the testable ViewsHooks instance.
    $this->sut = new TestableViewsHooks(
      $this->entityTypeManager,
      $this->renderer,
      $this->formSettings,
      $this->languageManager,
      $this->entityRepository,
    );
  }

  /**
   * Tests hook_views_data() output structure.
   */
  public function testViewsData(): void {
    $data = $this->sut->viewsData();

    // Verify the main table key exists.
    $this->assertArrayHasKey('search_api_index_search_index', $data);
    $table = $data['search_api_index_search_index'];

    // Verify filter IDs are correctly set.
    $this->assertSame('canonical_subvention_type_select', $table['canonical_subvention_type_select']['filter']['id']);
    $this->assertSame('canonical_applicant_type_select', $table['canonical_applicant_type_select']['filter']['id']);
    $this->assertSame('canonical_target_group_select', $table['canonical_target_group_select']['filter']['id']);
  }

  /**
   * Tests views_pre_render() hook.
   */
  public function testViewsPreRenderMutatesFieldsAndRows(): void {
    // Mock view with required fields.
    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn('application_search_search_api');

    // Add required field handlers as placeholders.
    $view->field = [];
    foreach ([
      'application_close',
      'application_open',
      'field_application_continuous',
      'field_application_period',
      'field_avustuslaji',
      'field_target_group',
      'application_target_group',
      'field_react_form',
      'application_subvention_type',
    ] as $id) {
      $view->field[$id] = new \stdClass();
    }

    // Create test rows: one without react form, one with.
    $row_webform = new ResultRow(['_test_has_react_form' => FALSE]);
    $row_react = new ResultRow(['_test_has_react_form' => TRUE]);
    $view->result = [$row_webform, $row_react];

    // Execute the hook.
    $this->sut->viewsPreRender($view);

    // First row: react form empty => those handlers should be removed.
    $this->assertArrayNotHasKey('application_target_group', $view->field);
    $this->assertArrayNotHasKey('application_open', $view->field);
    $this->assertArrayNotHasKey('application_close', $view->field);
    $this->assertArrayNotHasKey('field_react_form', $view->field);

    // Second row: react form present => overrides should be populated.
    // @phpstan-ignore property.notFound
    $this->assertInstanceOf(MarkupInterface::class, $row_react->_target_group_override);
    // @phpstan-ignore property.notFound
    $this->assertInstanceOf(MarkupInterface::class, $row_react->_subvention_type_override);
    // @phpstan-ignore property.notFound
    $this->assertInstanceOf(MarkupInterface::class, $row_react->_application_period_override);
  }

  /**
   * Tests preprocess_views_view_fields() hook applies overrides and unsets fields.
   */
  public function testPreprocessViewsViewFieldsAppliesOverridesAndUnsetsFields(): void {
    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn('application_search_search_api');

    // Create test row with override data.
    $row = new ResultRow([
      '_target_group_override' => Markup::create('<div>target</div>'),
      '_subvention_type_override' => Markup::create('<div>subvention</div>'),
      '_application_period_override' => Markup::create('<div>period</div>'),
    ]);

    // Set up template variables with fields.
    $variables = [
      'view' => $view,
      'row' => $row,
      'fields' => [
        'field_target_group' => (object) ['content' => 'OLD'],
        'application_subvention_type' => (object) ['content' => 'OLD'],
        'field_avustuslaji' => (object) ['content' => 'OLD'],
        'field_application_period' => (object) ['content' => 'OLD'],
        'application_open' => (object) ['content' => 'OLD'],
        'application_close' => (object) ['content' => 'OLD'],
        'field_application_continuous' => (object) ['content' => 'OLD'],
      ],
    ];

    // Execute the hook.
    $this->sut->preprocessViewsViewFields($variables);

    // Verify overrides were applied.
    // @phpstan-ignore property.notFound
    $this->assertSame((string) $row->_target_group_override, (string) $variables['fields']['field_target_group']->content);
    // @phpstan-ignore property.notFound
    $this->assertSame((string) $row->_subvention_type_override, (string) $variables['fields']['application_subvention_type']->content);
    // @phpstan-ignore property.notFound
    $this->assertSame((string) $row->_application_period_override, (string) $variables['fields']['field_application_period']->content);

    // Verify webform subvention field was removed when react override exists.
    $this->assertArrayNotHasKey('field_avustuslaji', $variables['fields']);

    // Verify period fields were removed when period override exists.
    $this->assertArrayNotHasKey('application_open', $variables['fields']);
    $this->assertArrayNotHasKey('application_close', $variables['fields']);

    // Verify continuous field is always removed.
    $this->assertArrayNotHasKey('field_application_continuous', $variables['fields']);
  }

  /**
   * Tests preprocess_views_view() hook.
   */
  public function testPreprocessViewsViewBuildsNewExposedFilterLabels(): void {
    // Prepare mocks for target_type label resolution.
    $term = $this->createMock(TermInterface::class);
    $term->method('label')->willReturn('Target label');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->with(123)->willReturn($term);

    $this->entityTypeManager
      ->method('getStorage')
      ->with('taxonomy_term')
      ->willReturn($storage);

    $this->entityRepository
      ->method('getTranslationFromContext')
      ->with($term)
      ->willReturn($term);

    // Mock view with exposed input.
    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn('application_search_search_api');
    $view->method('getExposedInput')->willReturn([
      'applicant' => '1',
      'subvention_type' => '2',
      'target_type' => '123',
      // Should be ignored - not in allowed list.
      'foo' => 'bar',
      'applicant_other' => '1',
    ]);

    $variables = [
      'view' => $view,
    ];

    // Execute the hook.
    $this->sut->preprocessViewsView($variables);

    // Verify application search link was added.
    $this->assertNotEmpty($variables['applicationSearchLink']);

    // Verify exposed filter labels were correctly resolved.
    $this->assertSame([
      'applicant' => 'Applicant label',
      'subvention_type' => 'Subvention label',
      'target_type' => 'Target label',
    ], $variables['newExposedFilter']);
  }

}

/**
 * Testable wrapper for ViewsHooks.
 *
 * Overrides internal helpers so hooks can be tested without building actual
 * Views field handlers / Search API items.
 */
final class TestableViewsHooks extends ViewsHooks {

  /**
   * {@inheritdoc}
   */
  protected function getFieldValue(ViewExecutable $view, ResultRow $row, string $field_id, bool $multivalue = FALSE): mixed {
    // Only used by viewsPreRender() for deciding react vs webform.
    if ($field_id === 'field_react_form') {
      return !empty($row->_test_has_react_form) ? 'has_value' : NULL;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildOverride(ViewExecutable $view, ResultRow $row, string $override): ?MarkupInterface {
    return Markup::create('<div>override:' . $override . '</div>');
  }

  /**
   * {@inheritdoc}
   */
  protected function buildApplicationSubventionMarkup(ViewExecutable $view, ResultRow $row): ?MarkupInterface {
    return Markup::create('<div>subvention</div>');
  }

  /**
   * {@inheritdoc}
   */
  protected function buildApplicationPeriodMarkup(ViewExecutable $view, ResultRow $row): MarkupInterface {
    return Markup::create('<div>period</div>');
  }

}
