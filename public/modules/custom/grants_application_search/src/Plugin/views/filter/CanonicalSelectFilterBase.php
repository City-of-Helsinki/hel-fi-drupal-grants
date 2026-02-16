<?php

declare(strict_types=1);

namespace Drupal\grants_application_search\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Base class for exposed select filters that apply conditions to Search API.
 *
 * @todo UHF-12853: Review this class when removing the webform functionality.
 */
abstract class CanonicalSelectFilterBase extends FilterPluginBase {

  /**
   * Cached select options.
   *
   * @var array<string, string>|null
   */
  protected ?array $valueOptions = NULL;

  /**
   * Canonical Search API field name to filter by.
   */
  abstract protected function getCanonicalField(): string;

  /**
   * Builds select options as [value => label].
   *
   * @return array<string, string>
   *   Select options.
   */
  abstract protected function buildSelectOptions(): array;

  /**
   * Defines options.
   *
   * @return array
   *   Options.
   */
  protected function defineOptions(): array {
    $options = parent::defineOptions();
    $options['exposed']['default'] = TRUE;
    return $options;
  }

  /**
   * Builds exposed form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state): void {
    parent::buildExposedForm($form, $form_state);

    $identifier = $this->options['expose']['identifier'] ?? $this->options['id'];

    $form[$identifier]['#type'] = 'select';
    $form[$identifier]['#options'] = $this->getSelectOptions();
    $form[$identifier]['#empty_option'] = $this->t('- Any -');
  }

  /**
   * Returns cached select options.
   *
   * @return array<string, string>
   *   Select options.
   */
  final protected function getSelectOptions(): array {
    if ($this->valueOptions !== NULL) {
      return $this->valueOptions;
    }

    $this->valueOptions = $this->buildSelectOptions();
    return $this->valueOptions;
  }

  /**
   * Applies the filter to the query.
   */
  public function query(): void {
    if ($this->value === NULL || $this->value === '') {
      return;
    }

    if (!$this->query instanceof SearchApiQuery) {
      return;
    }

    $value = is_array($this->value) ? (string) reset($this->value) : (string) $this->value;
    if ($value === '') {
      return;
    }

    $this->query
      ->getSearchApiQuery()
      ->addCondition($this->getCanonicalField(), [$value], 'IN');
  }

}
