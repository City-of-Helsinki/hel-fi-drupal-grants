<?php

namespace Drupal\grants_budget_components\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\GrantsErrorStorage;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Base class for Static budget components.
 */
class GrantsBudgetStaticBase extends WebformCompositeBase {

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
    $dataForElement = $element['#value'];

    $errorStorage = GrantsErrorStorage::getErrors();
    $errors = $errorStorage[$element['#webform_key']] ?? [];

    $element_errors = $errors['errors'] ?? [];
    foreach ($element_errors as $errorKey => $erroValue) {
      $element[$errorKey]['#attributes']['class'][] = $erroValue['class'];
      $element[$errorKey]['#attributes']['error_label'] = $erroValue['label'];
    }

    $fieldKeys = array_keys(static::getFieldNames());

    $fieldsInUse = [];

    foreach ($fieldKeys as $fieldKey) {
      $keyToCheck = '#' . $fieldKey . '__access';
      if (isset($element[$keyToCheck]) && $element[$keyToCheck] === FALSE) {
        unset($element[$fieldKey]);
      }
      else {
        $fieldsInUse[] = $fieldKey;
      }
    }

    if (isset($dataForElement['incomeGroupName'])) {
      $element['incomeGroupName']['#value'] = $dataForElement['incomeGroupName'];
    }

    if (empty($element['incomeGroupName']['#value']) && isset($element['#incomeGroup'])) {
      $element['incomeGroupName']['#value'] = $element['#incomeGroup'];
    }

    if (getenv('PRINT_DEVELOPMENT_DEBUG_FIELDS') == '1') {
      $element['debugging'] = [
        '#type' => 'details',
        '#title' => 'Dev DEBUG:',
        '#open' => FALSE,
      ];

      $element['debugging']['fieldset'] = [
        '#type' => 'fieldset',
      ];

      $element['debugging']['fieldset']['fields_in_use'] = [
        '#type' => 'inline_template',
        '#template' => "->setSetting('fieldsForApplication', [
          {% for field in fields %}
            '{{ field }}',<br/>
          {% endfor %}
        ])",
        '#context' => ['fields' => $fieldsInUse],
      ];
    }

    return $element;
  }

  /**
   * Get field names for this element.
   *
   * @return array
   *   Array of the field keys.
   */
  public static function getFieldNames(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $formState) {
    $values = parent::valueCallback($element, $input, $formState);
    // For some reason webform won't pass values later, if composite values
    // Are all falsy. Bit hacky but let's insert a value that is not used
    // any where, so we can track if user actually inserted 0 value or left
    // field empty.
    $values['_Temp'] = '1';
    return $values;
  }

}
