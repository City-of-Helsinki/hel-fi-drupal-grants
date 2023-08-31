<?php

namespace Drupal\grants_budget_components\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\Processor\NumberProcessor;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'grants_budget_cost_total'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("grants_budget_cost_total")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\webform_example_composite\Element\WebformExampleComposite
 */
class GrantsBudgetCostTotal extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_grants_budget'];
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
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {

    $element['#tree'] = TRUE;
    $element = parent::processWebformComposite($element, $form_state, $complete_form);

    $element['cost'] = [
      '#title' => 'Menot',
      '#type' => 'number',
      '#min' => 0,
      '#step' => '.01',
      '#disabled' => TRUE,
      '#process' => [
        [self::class, 'getCostValue'],
      ],
    ];

    return $element;
  }

    /**
   * Get value for costss.
   */
  public static function getCostValue(&$element, FormStateInterface $form_state, &$complete_form) {

    $incomes = $form_state->getValue('budget_static_cost');

    $total = 0;
    foreach ($incomes as $key => $income) {
      if (!is_numeric($income)) {
        continue;
      }

      $totalVal = $income;
      $total += $totalVal;
    }

    $element['#value'] = $total;
    $form_state->setValueForElement($element, $total);

    return $element;
  }

  // @codingStandardsIgnoreEnd

}
