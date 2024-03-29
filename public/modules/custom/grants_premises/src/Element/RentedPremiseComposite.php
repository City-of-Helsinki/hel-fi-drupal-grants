<?php

namespace Drupal\grants_premises\Element;

use Drupal\Core\Form\FormStateInterface;
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

    _grants_handler_process_multivalue_errors($element, $form_state);

    return $element;
  }

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
      '#title' => t('Street address', [], $tOpts),
    ];

    $elements['premisePostalCode'] = [
      '#type' => 'textfield',
      '#title' => t('Postal code', [], $tOpts),
      '#size' => 10,
      '#maxlength' => 8,
      '#pattern' => '^(FI-)?[0-9]{5}$',
      '#pattern_error' => t('Use the format FI-XXXXX or enter a five-digit postcode.', [], $tOpts),
    ];

    $elements['premisePostOffice'] = [
      '#type' => 'textfield',
      '#title' => t('City', [], $tOpts),
    ];

    $elements['rentSum'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'decimal', 'groupSeparator': ' ', 'digits': '2', 'radixPoint': ',', 'substituteRadixPoint': 'true'",
      '#title' => t('Rent', [], $tOpts),
      '#help' => t('EUR per month', [], $tOpts),
      '#attributes' => [
        'class' => ['webform--small'],
      ],
    ];

    $elements['lessorName'] = [
      '#type' => 'textfield',
      '#title' => t("Lessor's name", [], $tOpts),
    ];

    $elements['lessorPhoneOrEmail'] = [
      '#type' => 'textfield',
      '#title' => t("Lessor's contact information", [], $tOpts),
      '#help' => t('Email and/or telephone number', [], $tOpts),
    ];

    $elements['usage'] = [
      '#type' => 'textfield',
      '#title' => t('Purpose of use', [], $tOpts),
      '#help' => t('For example, an office, storage, gathering or clubs', [], $tOpts),
    ];

    $elements['daysPerWeek'] = [
      '#type' => 'number',
      '#title' => t('How many days per week is the facility used?', [], $tOpts),
      '#min' => 0,
      '#max' => 7,
    ];

    $elements['hoursPerDay'] = [
      '#type' => 'number',
      '#title' => t('How many hours per day is the facility used?', [], $tOpts),
      '#min' => 0,
      '#max' => 24,
    ];
    return $elements;
  }

}
