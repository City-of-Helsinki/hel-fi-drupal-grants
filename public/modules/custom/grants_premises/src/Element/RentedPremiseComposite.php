<?php

namespace Drupal\grants_premises\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'rented_premise_composite'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. rented_premise_composite)
 *
 * @FormElement("rented_premise_composite")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 */
class RentedPremiseComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'rented_premise_composite'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];
    $tOpts = ['context' => 'grants_premises'];

    $elements['premiseAddress'] = [
      '#type' => 'textfield',
      '#title' => t('Premise Address', [], $tOpts),
    ];

    $elements['premisePostalCode'] = [
      '#type' => 'textfield',
      '#title' => t('Post Code', [], $tOpts),
      '#size' => 10,
      '#maxlength' => 8,
      '#pattern' => '^(FI-)?[0-9]{5}$',
      '#pattern_error' => t('Use the format FI-XXXXX or enter a five-digit postcode.', [], $tOpts),
    ];

    $elements['premisePostOffice'] = [
      '#type' => 'textfield',
      '#title' => t('Post office', [], $tOpts),
    ];

    $elements['rentSum'] = [
      '#type' => 'number',
      '#title' => t('Rent sum', [], $tOpts),
    ];

    $elements['usage'] = [
      '#type' => 'textfield',
      '#title' => t('Usage', [], $tOpts),
    ];

    $elements['daysPerWeek'] = [
      '#type' => 'number',
      '#title' => t('Days per week', [], $tOpts),
    ];

    $elements['hoursPerDay'] = [
      '#type' => 'number',
      '#title' => t('Hours per day', [], $tOpts),
    ];

    $elements['lessorName'] = [
      '#type' => 'textfield',
      '#title' => t('Lessor name', [], $tOpts),
    ];

    $elements['lessorPhoneOrEmail'] = [
      '#type' => 'textfield',
      '#title' => t('Lessor phone or email', [], $tOpts),
    ];

    $elements['lessorAddress'] = [
      '#type' => 'textfield',
      '#title' => t('Lessor address', [], $tOpts),
    ];

    $elements['lessorPostalCode'] = [
      '#type' => 'textfield',
      '#title' => t('Lessor postal code', [], $tOpts),
      '#size' => 10,
      '#maxlength' => 8,
      '#pattern' => '^(FI-)?[0-9]{5}$',
      '#pattern_error' => t('Use the format FI-XXXXX or enter a five-digit postcode.', [], $tOpts),
    ];

    $elements['lessorPostOffice'] = [
      '#type' => 'textfield',
      '#title' => t('Lessor post office', [], $tOpts),
    ];

    return $elements;
  }

}
