<?php

namespace Drupal\grants_handler\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Compensations webform component.
 *
 * Support for following must be added:
 *
 * "uniqueID",
 * "subventionType",
 * "amount",
 * "amountInLetters",
 * "eventBegin",
 * "eventEnd",
 * "primaryArt",
 * "purpose",
 * "isFestival",
 * "letterNumber",
 * "letterDate",
 * "supportTimeBegin",
 * "supportTimeEnd",
 * "studentName",
 * "caretakerName",
 * "caretakerAddress",
 * "totalCosts"
 *
 * @FormElement("grants_compensations")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\grants_handler\Element\CompensationsComposite
 */
class CompensationsComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'compensation_composite'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];

    $elements['subventionTypeTitle'] = [
      '#type' => 'textfield',
      '#title' => t('Subvention name'),
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $elements['subventionType'] = [
      '#type' => 'hidden',
      '#title' => t('Subvention type'),
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $elements['amount'] = [
      '#type' => 'textfield',
      '#title' => t('Subvention amount'),
      '#input_mask' => "'alias': 'currency', 'prefix': '', 'suffix': '€','groupSeparator': ' ','radixPoint':','",
      '#attributes' => ['class' => ['input--borderless']],
      '#element_validate' => ['\Drupal\grants_handler\Element\CompensationsComposite::validateAmount'],
    ];

    return $elements;
  }

  /**
   * Validate subvention amount.
   *
   * The rule here is that in SOME field must have amount inserted.
   * So this errors if no values are given.
   *
   * @param array $element
   *   Element tobe validated.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $form
   *   The form.
   */
  public static function validateAmount(array &$element, FormStateInterface $formState, array &$form) {

    $values = $formState->getValues();

    $subventionPath = array_slice(
      $element['#array_parents'],
      0,
      array_search('subventions', $element['#array_parents']) + 1
    );

    $subventionElement = NestedArray::getValue($form, $subventionPath);
    $requiredSubvention = $subventionElement['#requiredSubventionType'] ?? '';
    $singleSubventionType = $subventionElement['#onlyOneSubventionPerApplication'] ?? FALSE;

    $subventionNumber = count($values['subventions']['items']);
    $zeroes = 0;
    $nonZeroes = 0;

    unset($values['subventions']['items']);
    foreach ($values['subventions'] as $item) {
      if (isset($item['amount'])) {
        if ($item['amount'] == '0,00€' || empty($item['amount'])) {
          $zeroes++;
          if ($requiredSubvention === $item['subventionType']) {
            $formState->setErrorByName('subventions', t('You must apply for the "@subventionType"', ['@subventionType' => $item['subventionTypeTitle']]));
          }
        }
        else {
          $nonZeroes++;
        }
      }
    }

    if ($singleSubventionType && $nonZeroes > 1) {
      $formState->setErrorByName('subventions', t('You can only select one subvention type.'));
    }

    if ($zeroes === $subventionNumber) {
      $formState->setErrorByName('subventions', t('You must insert at least one subvention amount'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $formState) {

    $parent = parent::valueCallback($element, $input, $formState);

    if (!empty($parent)) {
      return $parent;
    }

    $retval = [
      'subventionType' => '',
      'amount' => '',
    ];

    if (isset($parent['subventionType']) && $parent['subventionType'] != "") {
      $retval['subventionType'] = $parent['subventionType'];
      $retval['amount'] = $parent['amount'];
    }
    return $retval;
  }

}
