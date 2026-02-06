<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Hook;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ResultRow;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Views hook implementations for grants_application_search.
 */
final class ViewsHooks implements ContainerInjectionInterface {

  use AutoWireTrait;
  use StringTranslationTrait;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected RendererInterface $renderer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer'),
    );
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {

    if ($view->id() !== 'application_search_search_api') {
      return;
    }

    $required = [
      'application_close',
      'application_open',
      'field_application_continuous',
      'field_application_period',
      'field_avustuslaji',
      'field_target_group',
    ];

    foreach ($required as $id) {
      if (empty($view->field[$id])) {
        return;
      }
    }

    // Override webform fields if react form fields are not empty.
    foreach ($view->result as $row) {
      // @phpstan-ignore property.notFound
      $row->_application_period_override = $this->buildMarkup($view, $row);
      // @phpstan-ignore property.notFound
      $row->_target_group_override = $this->buildOverride($view, $row, 'application_target_group');
      // @phpstan-ignore property.notFound
      $row->_avustuslaji_override = $this->buildOverride($view, $row, 'application_subvention_type');
    }
  }

  /**
   * Implements hook_preprocess_views_view_fields().
   */
  #[Hook('preprocess_views_view_fields')]
  public function preprocessViewsViewFields(array &$variables): void {
    $view = $variables['view'];

    if ($view->id() !== 'application_search_search_api') {
      return;
    }

    $row = $variables['row'] ?? NULL;
    if (!$row instanceof ResultRow) {
      return;
    }

    // Apply the overrides if the fields exists.
    if (!empty($row->_target_group_override) && !empty($variables['fields']['field_target_group'])) {
      $variables['fields']['field_target_group']->content = $row->_target_group_override;
    }

    if (!empty($row->_avustuslaji_override) && !empty($variables['fields']['field_avustuslaji'])) {
      $variables['fields']['field_avustuslaji']->content = $row->_avustuslaji_override;
    }

    if (!empty($row->_application_period_override) && !empty($variables['fields']['field_application_period'])) {
      $variables['fields']['field_application_period']->content = $row->_application_period_override;
      unset($variables['fields']['field_application_continuous']);
    }
  }

  /**
   * Build the final application period markup for one row.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view executable.
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   Returns the markup.
   */
  protected function buildMarkup(ViewExecutable $view, ResultRow $row): MarkupInterface {
    $date_icon = '<span aria-hidden="true" class="hel-icon hel-icon--calendar-clock hel-icon--size-s"></span>';
    $continuous_raw = $this->getFieldValue($view, $row, 'field_application_continuous');
    $continuous = ($continuous_raw === '1' || $continuous_raw === 'true');

    if ($continuous) {
      return Markup::create($date_icon . '<span>' . $this->t('Continuous application') . '</span>');
    }

    $open_raw = $this->getFieldValue($view, $row, 'application_open');
    $close_raw = $this->getFieldValue($view, $row, 'application_close');
    $open = $this->parseData($open_raw);
    $close = $this->parseData($close_raw);

    if ($open && $close) {
      $s = $open->format('d.m.Y') . ' - ' . $close->format('d.m.Y');
      return Markup::create($date_icon . '<span>' . $this->t('Application period') . ' ' . $s . '</span>');
    }

    $period_rendered = trim((string) $view->field['field_application_period']->advancedRender($row));
    if ($period_rendered !== '') {
      return Markup::create(
        $date_icon . '<span>' . $this->t('Application period') . ' ' . $period_rendered . '</span>'
      );
    }

    return Markup::create(
      $date_icon . '<span>' . $this->t('The application period will be announced later') . '</span>'
    );
  }

  /**
   * Get a raw Views/Search API field value.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view executable.
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   * @param string $field_id
   *   The field id or the Search API field identifier.
   *
   * @return string|null
   *   Returns the field value.
   */
  protected function getFieldValue(ViewExecutable $view, ResultRow $row, string $field_id): ?string {
    // Try to get the indexed values via views field handler.
    if (!empty($view->field[$field_id])) {
      $value = $view->field[$field_id]->getValue($row);

      if (is_array($value)) {
        $value = reset($value);
      }

      if ($value !== NULL && $value !== '') {
        return (string) $value;
      }
    }

    // If not found, try to get the indexed values via search api.
    if (isset($row->_item) && is_object($row->_item)) {
      try {
        $field = $row->_item->getField($field_id);
      }
      catch (\Throwable $e) {
        $field = NULL;
      }

      if ($field && method_exists($field, 'getValues')) {
        $values = $field->getValues();
        $first = $values[0] ?? NULL;

        if ($first !== NULL && $first !== '') {
          return (string) $first;
        }
      }
    }

    return NULL;
  }

  /**
   * Parse the date string into DateTimeImmutable or return NULL.
   *
   * @param string|null $value
   *   The raw string value.
   *
   * @return \DateTimeImmutable|null
   *   Returns the DateTimeImmutable object or NULL.
   */
  protected function parseData(?string $value): ?\DateTimeImmutable {
    if ($value === NULL) {
      return NULL;
    }

    try {
      $date = new \DateTimeImmutable();
      return $date->setTimestamp((int) $value);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * Build a rendered override for an entity reference Search API field.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view executable.
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   * @param string $override
   *   The Search API field id that contains the reference(s).
   *
   * @return \Drupal\Component\Render\MarkupInterface|null
   *   Rendered markup or NULL if no values.
   */
  protected function buildOverride(ViewExecutable $view, ResultRow $row, string $override): ?MarkupInterface {
    $ids = $this->getReferencedEntityIds($view, $row, $override);

    if ($ids === []) {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $entities = $storage->loadMultiple($ids);

    if ($entities === []) {
      return NULL;
    }

    $builds = [];
    $parts = [];
    $view_builder = $this->entityTypeManager->getViewBuilder('taxonomy_term');

    // Build render arrays for each referenced taxonomy term
    // using the default view mode.
    foreach ($entities as $entity) {
      $builds[] = $view_builder->view($entity, 'default');
    }

    // Render each entity build, trim whitespace, remove empty results,
    // and reindex the array. Result should be a clean list of rendered strings.
    foreach ($builds as $build) {
      $rendered = trim((string) $this->renderer->renderInIsolation($build));
      if ($rendered !== '') {
        $parts[] = $rendered;
      }
    }

    if ($parts === []) {
      return NULL;
    }

    return Markup::create(implode('', $parts));
  }

  /**
   * Extract referenced entity ids from a Search API field in a Views row.
   *
   * Supports common shapes:
   * - scalar: "123"
   * - array: [123, 456]
   * - array of arrays: [['target_id' => 123], ...]
   *
   * @return int[]
   *   Unique ids in original order.
   */
  protected function getReferencedEntityIds(ViewExecutable $view, ResultRow $row, string $field_id): array {
    $raw = NULL;

    // Get the raw views field handler value.
    if (!empty($view->field[$field_id])) {
      $raw = $view->field[$field_id]->getValue($row);
    }

    // Fallback to Search API item field values.
    if (
      ($raw === NULL || $raw === '') &&
      isset($row->_item) &&
      is_object($row->_item) &&
      method_exists($row->_item, 'getField')
    ) {
      try {
        $field = $row->_item->getField($field_id);
        if ($field && method_exists($field, 'getValues')) {
          $raw = $field->getValues();
        }
      }
      catch (\Throwable $e) {
        $raw = NULL;
      }
    }

    $values = is_array($raw) ? $raw : [$raw];
    $ids = [];

    foreach ($values as $value) {
      // If the value is an array, try to get the target_id.
      if (is_array($value)) {
        $value = $value['target_id'] ?? reset($value);
      }

      // Skip nullable values.
      if ($value === NULL || $value === '') {
        continue;
      }

      // Skip non numeric values.
      if (!is_numeric($value)) {
        continue;
      }
      $ids[] = (int) $value;
    }

    return array_values(array_unique($ids));
  }

}
