<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Hook;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ResultRow;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\ViewExecutable;

/**
 * Views hook implementations for grants_application_search.
 */
class ViewsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {

    if ($view->id() !== 'application_search_search_api') {
      return;
    }

    $required = [
      'field_application_period',
      'field_application_continuous',
      'application_open',
      'application_close',
    ];

    foreach ($required as $id) {
      if (empty($view->field[$id])) {
        return;
      }
    }

    foreach ($view->result as $row) {
      $row->_application_period_override = $this->buildMarkup($view, $row);
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

    if (empty($row->_application_period_override)) {
      return;
    }

    if (empty($variables['fields']['field_application_period'])) {
      return;
    }

    $variables['fields']['field_application_period']->content = $row->_application_period_override;
    unset($variables['fields']['field_application_continuous']);
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
   * Get a raw Views field value.
   *
   * @param \Drupal\views\ViewExecutable $view
   *   The view executable.
   * @param \Drupal\views\ResultRow $row
   *   The result row.
   * @param string $field_id
   *   The field id.
   *
   * @return string|null
   *   Returns the field value.
   */
  protected function getFieldValue(ViewExecutable $view, ResultRow $row, string $field_id): ?string {
    if (empty($view->field[$field_id])) {
      return NULL;
    }

    $value = $view->field[$field_id]->getValue($row);

    if (is_array($value)) {
      $value = reset($value);
    }

    if ($value === NULL || $value === '') {
      return NULL;
    }

    return (string) $value;

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
      return new \DateTimeImmutable($value);
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

}
