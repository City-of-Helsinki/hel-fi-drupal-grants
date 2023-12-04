<?php

namespace Drupal\grants_premises\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'rent_cost_composite'.
 *
 * Webform composites contain a group of sub-elements.
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. rent_cost_composite)
 *
 * @FormElement("rent_cost_composite")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 */
class RentCostComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'rent_cost_composite'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];
    $tOpts = ['context' => 'rent_cost_composite'];

    $elements['rentCostsHours'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Total hours', [], $tOpts),
    ];

    $elements['rentCostsCost'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'decimal', 'groupSeparator': ' ', 'digits': '2', 'radixPoint': ',', 'substituteRadixPoint': 'true'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Total / EUR', [], $tOpts),
    ];

    $elements['rentCostsDifferenceToNextYear'] = [
      '#type' => 'textfield',
      '#title' => t('Change in the use of facilities planned for the next year, +/- hours and reason', [], $tOpts),
    ];

    return $elements;
  }

}
