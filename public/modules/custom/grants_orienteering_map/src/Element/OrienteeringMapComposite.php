<?php

namespace Drupal\grants_orienteering_map\Element;

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
      '#type' => 'number',
      '#title' => t('Size in km2', [], $tOpts),
      '#process' => [
        [NumberProcessor::class, 'process'],
      ],
    ];

    $elements['voluntaryHours'] = [
      '#type' => 'number',
      '#title' => t('Informal voluntary work in hours', [], $tOpts),
      '#step' => 1,
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
    ];

    return $elements;
  }

}
