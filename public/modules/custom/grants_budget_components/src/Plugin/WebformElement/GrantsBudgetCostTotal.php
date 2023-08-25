<?php

namespace Drupal\grants_budget_components\Plugin\WebformElement;

/**
 * Provides a 'grants_budget_cost_total' element.
 *
 * @WebformElement(
 *   id = "grants_budget_cost_total",
 *   label = @Translation("GrantsBudgetCostTotal"),
 *   description = @Translation("Provides a GrantsBudgetCostTotal."),
 *   category = @Translation("GrantsBudgetCostTotal"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\webform_example_composite\Element\WebformExampleComposite
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class GrantsBudgetCostTotal extends GrantsBudgetBase {

}
