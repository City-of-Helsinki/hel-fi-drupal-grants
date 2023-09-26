<?php

namespace Drupal\grants_budget_components\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'grants_budget_income_total'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("grants_budget_income_total")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\webform_example_composite\Element\WebformExampleComposite
 */
class GrantsBudgetIncomeTotal extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);

    return [
      '#input' => FALSE,
      '#size' => 60,
      '#default_value' => 0,
      '#pre_render' => [
        [$class, 'preRenderGrantsBudgetIncomeTotalFieldElement'],
      ],
      '#theme' => 'webform_grants_budget_total',
    ];
  }

  // @codingStandardsIgnoreStart

  /**
   * Process default values and values from submitted data.
   *
   * @param array $element
   *   Element that is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Full form.
   *
   * @return array[]
   *   Form API element for webform element.
   */
  /**
   * Process default values and values from submitted data.
   *
   * @param array $element
   *   Element that is being processed.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $complete_form
   *   Full form.
   *
   * @return array[]
   *   Form API element for webform element.
   */
  public static function preRenderGrantsBudgetIncomeTotalFieldElement(array $element): mixed {
    $field = '';
    $column = '';
    $fieldarray = [];
    foreach ($element['#collect_field'] as $key => $value) {
      if ($value !== 0) {
        if (strstr($element['#collect_field'][$key], '%%')) {
          [$field, $column] = explode('%%', $element['#collect_field'][$key]);
          $fieldarray[] = ['fieldName' => $field, 'columnName' => $column];
        }
      }
    }

    $element['#theme_wrappers'][] = 'form_element';
    $element['#wrapper_attributes']['id'] = $element['#id'] . '--wrapper';
    $element['#attributes']['id'] = $element['#id'];
    $element['#attributes']['name'] = $element['#name'];
    $element['#attributes']['value'] = $element['#value'];
    $formItem = 'text_field';
    if (isset($element['#form_item'])) {
      $formItem = $element['#form_item'];
    }
    $element['#type'] = 'number';

    $element['#attached']['drupalSettings']['totalFields'][$element['#id']] = [
      'totalFieldId' => $element['#id'],
      'fieldName' => $field,
      'columnName' => $column,
      'fields' => $fieldarray,
    ];

        // Add class name to wrapper attributes.
        $class_name = str_replace('_', '-', $element['#type']);
        static::setAttributes($element, ['js-' . $class_name, $class_name]);

    return $element;
  }


  // @codingStandardsIgnoreEnd

}
