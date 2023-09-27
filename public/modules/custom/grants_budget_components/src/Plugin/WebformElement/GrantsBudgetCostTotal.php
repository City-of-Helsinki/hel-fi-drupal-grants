<?php

namespace Drupal\grants_budget_components\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;

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
class GrantsBudgetCostTotal extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {

    return parent::getDefaultProperties() + [
      'collect_field' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Get webform object.
    $webform_obj = $form_state->getFormObject()->getWebform();
    $webform_field = $webform_obj->getElementsInitializedFlattenedAndHasValue();
    $collect_column = [];

    // Collect Field.
    foreach ($webform_field as $field_key => $field_detail) {
      if ($field_detail['#type'] == 'grants_budget_cost_static') {
        foreach ($field_detail['#webform_composite_elements'] as $column_key => $value) {
          $collect_column[$field_key . '%%' . $column_key] = $field_key . ': ' . $column_key;
        }
        continue;
      }
      else {
        continue;
      }
    }

    $form['grants_webform_budget_cost_total_field'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('webform_budget_cost_total field settings'),
    ];

    $form['grants_webform_budget_cost_total_field']['collect_field'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Collect Fields'),
      '#options' => $collect_column,
      '#description' => $this->t('Which fields should be collected.'),
    ];

    return $form;
  }

}
