<?php

namespace Drupal\grants_budget_components\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'grants_budget_cost_static'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("grants_budget_cost_static")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\webform_example_composite\Element\WebformExampleComposite
 */
class GrantsBudgetCostStatic extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_grants_budget_cost_static'];
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
    $dataForElement = $element['#value'];

    $storage = $form_state->getStorage();
    $errors = $storage['errors'][$element['#webform_key']] ?? [];

    $element_errors = $errors['errors'] ?? [];
    foreach ($element_errors as $errorKey => $erroValue) {
      $element[$errorKey]['#attributes']['class'][] = $erroValue['class'];
      $element[$errorKey]['#attributes']['error_label'] = $erroValue['label'];
    }

    $fieldKeys = array_keys(self::getFieldNames());

    $fieldsInUse = [];

    foreach ($fieldKeys as $fieldKey) {
      $keyToCheck = '#' . $fieldKey . '__access';
      if (isset($element[$keyToCheck]) && $element[$keyToCheck] === FALSE) {
        unset($element[$fieldKey]);
      } else {
        $fieldsInUse[] = $fieldKey;
      }
    }

    if (isset($dataForElement['costGroupName'])) {
      $element['costGroupName']['#value'] = $dataForElement['costGroupName'];
    }

    if (empty($element['costGroupName']['#value']) && isset($element['#incomeGroup'])) {
      $element['costGroupName']['#value'] = $element['#incomeGroup'];
    }

    if (getenv('PRINT_DEVELOPMENT_DEBUG_FIELDS') == '1') {
      $element['debugging'] = [
        '#type' => 'details',
        '#title' => 'Dev DEBUG:',
        '#open' => FALSE,
      ];

      $element['debugging']['fieldset'] = [
        '#type' => 'fieldset'
      ];

      $element['debugging']['fieldset']['fields_in_use'] = [
        '#type' => 'inline_template',
        '#template' => "->setSetting('fieldsForApplication', [
          {% for field in fields %}
            '{{ field }}',<br/>
          {% endfor %}
        ])",
        '#context' => ['fields' => $fieldsInUse]
      ];
    }

    return $element;
  }

  // @codingStandardsIgnoreEnd

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element) {
    $elements = [];

    $fieldNames = self::getFieldNames();

    foreach ($fieldNames as $key => $fieldName) {
      $elements[$key] = [
        '#title' => $fieldName,
        '#type' => 'textfield',
        '#input_mask' => "'alias': 'decimal', 'groupSeparator': ' ', 'digits': '2', 'radixPoint': ',', 'substituteRadixPoint': 'true'",
        '#maxlength' => 20,
        '#attributes' => [
          'class' => ['webform--small'],
        ],
      ];
    }

    $elements['costGroupName'] = [
      '#type' => 'hidden',
      '#title' => t('incomeGroupName'),
      // Add .js-form-wrapper to wrapper (ie td) to prevent #states API from
      // disabling the entire table row when this element is disabled.
      '#wrapper_attributes' => ['class' => 'js-form-wrapper'],
    ];
    return $elements;
  }

  /**
   * Get field names for this element.
   *
   * @return array
   *   Array of the field keys.
   */
  public static function getFieldNames(): array {
    $tOpts = ['context' => 'grants_budget_components'];
    return [
      "salaries" => t("Salaries (€)", [], $tOpts),
      "personnelSideCosts" => t("Personnel costs from salaries and fees (approx. 30%) (€)", [], $tOpts),
      "personnelSocialSecurityCosts" => t("personnelSocialSecurityCosts (€)", [], $tOpts),
      "rentSum" => t("Rents (€)", [], $tOpts),
      "materials" => t("Materials (€)", [], $tOpts),
      "transport" => t("transport (€)", [], $tOpts),
      "food" => t("food (€)", [], $tOpts),
      "pr" => t("pr (€)", [], $tOpts),
      "insurance" => t("insurance (€)", [], $tOpts),
      "snacks" => t("Snacks (€)", [], $tOpts),
      "cleaning" => t("Cleaning (€)", [], $tOpts),
      "premisesService" => t("Premises Service (€)", [], $tOpts),
      "travel" => t("travel (€)", [], $tOpts),
      "heating" => t("Heating (€)", [], $tOpts),
      "servicesTotal" => t("servicesTotal (€)", [], $tOpts),
      "water" => t("Water (€)", [], $tOpts),
      "electricity" => t("Electricity (€)", [], $tOpts),
      "suppliesTotal" => t("suppliesTotal (€)", [], $tOpts),
      "admin" => t("Admin (€)", [], $tOpts),
      "accounting" => t("Accounting (€)", [], $tOpts),
      "health" => t("Health (€)", [], $tOpts),
      "otherCostsTotal" => t("otherCostsTotal (€)", [], $tOpts),
      "services" => t("Services (€)", [], $tOpts),
      "supplies" => t("Supplies (€)", [], $tOpts),
      "useOfCustomerFeesTotal" => t("useOfCustomerFeesTotal (€)", [], $tOpts),
      "netCosts" => t("netCosts (€)", [], $tOpts),
      "performerFees" => t("Salaries and fees for performers and artists (€)", [], $tOpts),
      "otherFees" => t("Other salaries and fees (production, technology, etc.) (€)", [], $tOpts),
      "generalCosts" => t("generalCosts (€)", [], $tOpts),
      "permits" => t("permits (€)", [], $tOpts),
      "setsAndCostumes" => t("setsAndCostumes (€)", [], $tOpts),
      "security" => t("security (€)", [], $tOpts),
      "costsWithoutDeferredItems" => t("costsWithoutDeferredItems (€)", [], $tOpts),
      "generalCostsTotal" => t("generalCostsTotal (€)", [], $tOpts),
      "showCosts" => t("Performance fees (€)", [], $tOpts),
      "travelCosts" => t("Travel costs (€)", [], $tOpts),
      "transportCosts" => t("Transport costs (€)", [], $tOpts),
      "equipment" => t("Technology, equipment rentals and electricity (€)", [], $tOpts),
      "premises" => t("Premise operating costs and rents (€)", [], $tOpts),
      "marketing" => t("Information, marketing and printing (€)", [], $tOpts),
      "totalCosts" => t("Total costs (€)", [], $tOpts),
      "allCostsTotal" => t("allCostsTotal (€)", [], $tOpts),
      "plannedTotalCosts" => t("Planned total costs (€)", [], $tOpts),
    ];
  }

}
