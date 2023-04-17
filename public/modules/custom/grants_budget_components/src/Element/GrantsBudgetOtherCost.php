<?php

namespace Drupal\grants_budget_components\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'grants_budget_other_cost'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("grants_budget_other_cost")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\webform_example_composite\Element\WebformExampleComposite
 */
class GrantsBudgetOtherCost extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_grants_budget_other_cost'];
  }

  /**
   * Build webform element based on data in ATV document.
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
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {

    $element['#tree'] = TRUE;
    $element = parent::processWebformComposite($element, $form_state, $complete_form);
    $dataForElement = $element['#value'];

    if (isset($dataForElement['incomeGroupName'])) {
      $element['incomeGroupName']['#value'] = $dataForElement['incomeGroupName'];
    }

    if (empty($element['incomeGroupName']['#value']) && isset($element['#incomeGroup'])) {
      $element['incomeGroupName']['#value'] = $element['#incomeGroup'];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];

    $elements['label'] = [
      '#type' => 'textfield',
      '#title' => t('Label'),
    ];
    $elements['value'] = [
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#attributes' => [
        ' type' =>  'number'
      ]
    ];

    $default_value = $element['#incomeGroupName__placeholder'] ?? NULL;
    $elements['incomeGroupName'] = [
      '#type' => 'hidden',
      '#title' => t('incomeGroupName'),
      // Add .js-form-wrapper to wrapper (ie td) to prevent #states API from
      // disabling the entire table row when this element is disabled.
      '#wrapper_attributes' => ['class' => 'js-form-wrapper'],
      '#value' => $default_value,
    ];
    return $elements;
  }

}
