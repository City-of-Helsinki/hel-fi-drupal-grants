<?php

namespace Drupal\grants_orienteering_map\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler;
use Drupal\grants_handler\Processor\NumberProcessor;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'orienteering_map_composite'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. toimipaikka_composite)
 *
 * @FormElement("orienteering_map_composite")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 */
class OrienteeringMapComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'orienteering_map_composite'];
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);
    _grants_handler_process_multivalue_errors($element, $form_state);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];
    $tOpts = ['context' => 'grants_orienteering_map'];

    $elements['mapName'] = [
      '#type' => 'textarea',
      '#title' => t('Map name, location and map type', [], $tOpts),
      '#required' => TRUE,
      '#counter_type' => 'character',
      '#maxlength' => 5000,
      '#counter_maximum' => 5000,
      '#counter_maximum_message' => t('%d/5000 characters left', [], $tOpts),
    ];

    $elements['size'] = [
      '#required' => TRUE,
      '#type' => 'number',
      '#title' => t('Size in km2', [], $tOpts),
      '#process' => [
        [NumberProcessor::class, 'process'],
      ],
    ];

    $elements['voluntaryHours'] = [
      '#type' => 'number',
      '#title' => t('Informal voluntary work in hours', [], $tOpts),
      '#process' => [
        [NumberProcessor::class, 'process'],
      ],
    ];

    $elements['cost'] = [
      '#type' => 'number',
      '#title' => t('Costs in euros', [], $tOpts),
      '#process' => [
        [NumberProcessor::class, 'process'],
      ],
    ];

    $elements['otherCompensations'] = [
      '#type' => 'number',
      '#title' => t('Grants received from others in euros', [], $tOpts),
      '#process' => [
        [NumberProcessor::class, 'process'],
      ],
      '#element_validate' => [
        [self::class, 'validateOtherCompensation'],
      ],
    ];

    return $elements;
  }

  /**
   * Validate orienteering map other compensation value.
   *
   * The field cannot be higher than the sum of voluntaryHours + cost fields.
   *
   * @param array $element
   *   Element tobe validated.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $form
   *   The form.
   */
  public static function validateOtherCompensation(array &$element, FormStateInterface $formState, array &$form) {
    $tOpts = ['context' => 'grants_orienteering_map'];

    $arrayPath = $element['#parents'];
    array_pop($arrayPath);

    $elementValues = $formState->getValue($arrayPath);

    // Get current item values.
    $voluntaryHours = $elementValues['voluntaryHours'] ?? 0;
    $cost = $elementValues['cost'] ?? 0;
    $otherCompensations = $elementValues['otherCompensations'] ?? 0;

    // Float conversion, just to be sure.
    $voluntaryHours = GrantsHandler::convertToFloat($voluntaryHours);
    $cost = GrantsHandler::convertToFloat($cost);
    $otherCompensation = GrantsHandler::convertToFloat($otherCompensations);

    // Hours + Cost cannot be lower than otherCompensation.
    $hoursAndCostSum = $voluntaryHours + $cost;
    if ($otherCompensation > $hoursAndCostSum) {
      $formState->setError(
        $element,
        t(
          'This value cannot be higher than the sum of voluntary hours and cost fields',
          [],
          $tOpts
        )
      );
    }
  }

}
