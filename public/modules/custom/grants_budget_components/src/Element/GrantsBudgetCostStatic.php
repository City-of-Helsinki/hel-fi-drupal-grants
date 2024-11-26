<?php

namespace Drupal\grants_budget_components\Element;

use Drupal\Core\Form\FormStateInterface;

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
class GrantsBudgetCostStatic extends GrantsBudgetStaticBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'webform_grants_budget_cost_static'];
  }

  // @codingStandardsIgnoreStart

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);
    $dataForElement = $element['#value'];

    unset($element['incomeGroupName']);

    if (isset($dataForElement['costGroupName'])) {
      $element['costGroupName']['#value'] = $dataForElement['costGroupName'];
    }

    if (empty($element['costGroupName']['#value']) && isset($element['#incomeGroup'])) {
      $element['costGroupName']['#value'] = $element['#incomeGroup'];
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
      "personnelSideCosts" => t("Personnel costs from salaries and fees (approx. 30%) (€)", [], $tOpts),
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
