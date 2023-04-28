<?php

namespace Drupal\grants_webform_summation_field\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElementBase;

/**
 * Provides a 'grants_webform_summation_field' element.
 *
 * @WebformElement(
 *   id = "grants_webform_summation_field",
 *   label = @Translation("Grants Webform Summation Field"),
 *   description = @Translation("Provide a webform summation field field for grants applications."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class GrantsWebformSummationField extends WebformElementBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {

    return parent::getDefaultProperties() + [
      'collect_field' => '',
      'data_type' => 'integer',
      'display_type' => 'integer',
      'form_item' => 'integer',
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
      if ($field_detail['#type'] == 'grants_webform_summation_field') {
      }
      elseif ($field_detail['#type'] == 'grants_compensations') {
        foreach ($field_detail['#webform_composite_elements'] as $column_key => $value) {
          $collect_column[$field_key . '%%' . $column_key] = $field_detail['#title'] . ': ' . $column_key;
        }
        continue;
      }
      else {
        $collect_column[$field_key] = $field_detail['#title'];
      }
    }

    $form['grants_webform_summation_field'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('webform summation field settings'),
    ];

    $form['grants_webform_summation_field']['collect_field'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Collect Fields'),
      '#options' => $collect_column,
      '#description' => $this->t('Which fields should be collected.'),
    ];

    $form['grants_webform_summation_field']['data_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Data type'),
      '#options' => [
        'euro' => $this->t('Euro'),
        'integer' => $this->t('Integer'),
      ],
      '#description' => $this->t('What type of data is collected.'),
    ];

    $form['grants_webform_summation_field']['display_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Display value as...'),
      '#options' => [
        'euro' => $this->t('Euro'),
        'integer' => $this->t('Integer'),
      ],
      '#description' => $this->t('What type of data is collected.'),
    ];

    $form['grants_webform_summation_field']['form_item'] = [
      '#type' => 'select',
      '#title' => $this->t('Form item type'),
      '#options' => [
        'text_field' => $this->t('Text Field'),
        'hidden' => $this->t('Hidden'),
      ],
      '#description' => $this->t('What type of data is collected.'),
    ];
    return $form;
  }

}
