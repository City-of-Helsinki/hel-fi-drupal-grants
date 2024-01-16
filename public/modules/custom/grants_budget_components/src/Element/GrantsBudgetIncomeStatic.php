<?php

namespace Drupal\grants_budget_components\Element;

use Drupal\Core\Form\FormStateInterface;

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
class GrantsBudgetIncomeStatic extends GrantsBudgetStaticBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return parent::getInfo() + ['#theme' => 'grants_budget_income_static'];
  }

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

      if ($key === 'compensation') {
        $elements[$key]['#disabled'] = TRUE;
        $elements[$key]['#process'][] = [
          self::class, 'getCompensationValue',
        ];
      }
    }

    $elements['incomeGroupName'] = [
      '#type' => 'hidden',
      '#title' => t('incomeGroupName'),
      // Add .js-form-wrapper to wrapper (ie td) to prevent #states API from
      // disabling the entire table row when this element is disabled.
      '#wrapper_attributes' => ['class' => 'js-form-wrapper'],
    ];
    return $elements;
  }

  /**
   * Get value for compensation field from subventions.
   */
  public static function getCompensationValue(&$element, FormStateInterface $form_state, &$complete_form) {

    $subventions = $form_state->getValue('subventions');

    $total = 0;
    foreach ($subventions as $key => $subvention) {
      if ($key === 'items' || !isset($subvention['amount'])) {
        continue;
      }

      $trimmedString = str_replace([' ', '€'], '', $subvention['amount']);
      $trimmedString = str_replace(',', '.', $trimmedString);
      $floatVal = floatval($trimmedString);
      $total += $floatVal;
    }

    $form_state->setValueForElement($element, $total);

    // Convert value back to #inputMask format for the element.
    $element['#value'] = str_replace('.', ',', $total);

    return $element;
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
      "compensation" => t("Requested grants (€)", [], $tOpts),
      "plannedStateOperativeSubvention" => t("Planned state operative subvention (€)", [], $tOpts),
      "otherCompensationFromCity" => t("Operational assistance of the cultural services of the City of Helsinki (€)", [], $tOpts),
      "stateOperativeSubvention" => t("State operating subvention (€)", [], $tOpts),
      "plannedOtherCompensations" => t("Other grants (€)", [], $tOpts),
      "sponsorships" => t("Private financier (e.g. sponsorship, severance pay, donation) (€)", [], $tOpts),
      "entryFees" => t("Access and cancellation fees (€)", [], $tOpts),
      "sales" => t("Other income from own operations (€)", [], $tOpts),
      "financialFundingAndInterests" => t("Financial funding and interests (€)", [], $tOpts),
      "customerFees" => t("Customer fees (€)", [], $tOpts),
      "donations" => t("Donations (€)", [], $tOpts),
      "compensationFromCulturalAffairs" => t("compensationFromCulturalAffairs (€)", [], $tOpts),
      "otherCompensationType" => t("otherCompensationType (€)", [], $tOpts),
      "incomeWithoutCompensations" => t("incomeWithoutCompensations (€)", [], $tOpts),
      "ownFunding" => t("The community's own funding (€)", [], $tOpts),
      "plannedTotalIncome" => t("Proposed total income in Euros (€)", [], $tOpts),
      "otherCompensations" => t("Other compensations (€)", [], $tOpts),
      "plannedTotalIncomeWithoutSubventions" => t("plannedTotalIncomeWithoutSubventions (€)", [], $tOpts),
      "plannedShareOfIncomeWithoutSubventions" => t("plannedShareOfIncomeWithoutSubventions (€)", [], $tOpts),
      "shareOfIncomeWithoutSubventions" => t("Share of income other than subsidies (€)", [], $tOpts),
      "totalIncomeWithoutSubventions" => t("Income without subsidies (€)", [], $tOpts),
      "totalIncome" => t("Total income (€)", [], $tOpts),
    ];
  }

}
