<?php

namespace Drupal\grants_budget_components\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'grants_budget_income_static'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. webform_address)
 *
 * @FormElement("grants_budget_income_static")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\webform_example_composite\Element\WebformExampleComposite
 */
class GrantsBudgetIncomeStatic extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'grants_budget_income_static'];
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

    if (isset($dataForElement['incomeGroupName'])) {
      $element['incomeGroupName']['#value'] = $dataForElement['incomeGroupName'];
    }

    if (empty($element['incomeGroupName']['#value']) && isset($element['#incomeGroup'])) {
      $element['incomeGroupName']['#value'] = $element['#incomeGroup'];
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
        '#type' => 'textfield',
        '#title' => $fieldName,
        '#attributes' => [
          ' type' => 'number',
        ],
      ];
    }

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

  /**
   * Get field names for this element.
   *
   * @return array
   *   Array of the field keys.
   */
  public static function getFieldNames(): array {
    $tOpts = ['context' => 'grants_budget_components'];
    return [
      "compensation" => t("compensation", [], $tOpts),
      "customerFees" => t("customerFees", [], $tOpts),
      "donations" => t("donations", [], $tOpts),
      "entryFees" => t("entryFees", [], $tOpts),
      "otherCompensations" => t("otherCompensations", [], $tOpts),
      "sponsorships" => t("sponsorships", [], $tOpts),
      "sales" => t("sales", [], $tOpts),
      "compensationFromCulturalAffairs" => t("compensationFromCulturalAffairs", [], $tOpts),
      "otherCompensationFromCity" => t("otherCompensationFromCity", [], $tOpts),
      "otherCompensationType" => t("otherCompensationType", [], $tOpts),
      "totalIncome" => t("totalIncome", [], $tOpts),
      "incomeWithoutCompensations" => t("incomeWithoutCompensations", [], $tOpts),
      "plannedStateOperativeSubvention" => t("plannedStateOperativeSubvention", [], $tOpts),
      "plannedOtherCompensations" => t("plannedOtherCompensations", [], $tOpts),
      "ownFunding" => t("ownFunding", [], $tOpts),
      "financialFundingAndInterests" => t("financialFundingAndInterests", [], $tOpts),
      "plannedTotalIncome" => t("plannedTotalIncome", [], $tOpts),
      "plannedTotalIncomeWithoutSubventions" => t("plannedTotalIncomeWithoutSubventions", [], $tOpts),
      "plannedShareOfIncomeWithoutSubventions" => t("plannedShareOfIncomeWithoutSubventions", [], $tOpts),
      "stateOperativeSubvention" => t("stateOperativeSubvention", [], $tOpts),
      "totalIncomeWithoutSubventions" => t("totalIncomeWithoutSubventions", [], $tOpts),
      "shareOfIncomeWithoutSubventions" => t("shareOfIncomeWithoutSubventions", [], $tOpts),
    ];
  }

}
