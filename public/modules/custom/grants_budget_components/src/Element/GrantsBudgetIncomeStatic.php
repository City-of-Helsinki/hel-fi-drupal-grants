<?php

namespace Drupal\grants_budget_components\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_handler\Processor\NumberProcessor;
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

    _grants_handler_process_multivalue_errors($element, $form_state);

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

    if (isset($dataForElement['incomeGroupName'])) {
      $element['incomeGroupName']['#value'] = $dataForElement['incomeGroupName'];
    }

    if (empty($element['incomeGroupName']['#value']) && isset($element['#incomeGroup'])) {
      $element['incomeGroupName']['#value'] = $element['#incomeGroup'];
    }

    $appEnv = ApplicationHandler::getAppEnv();
    if ($appEnv === 'DEV' || strpos($appEnv, 'LOCALA') !== FALSE) {
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
        '#type' => 'number',
        '#min' => 0,
        '#step' => '.01',
        '#process' => [
          [NumberProcessor::class, 'process'],
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

    $element['#value'] = $total;
    $form_state->setValueForElement($element, $total);

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
      "customerFees" => t("customerFees (€)", [], $tOpts),
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
