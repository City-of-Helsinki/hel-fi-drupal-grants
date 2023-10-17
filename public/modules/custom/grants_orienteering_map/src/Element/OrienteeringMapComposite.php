<?php

namespace Drupal\grants_orienteering_map\Element;

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
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'decimal', 'groupSeparator': ' ', 'digits': '2', 'radixPoint': ',', 'substituteRadixPoint': 'true'",
      '#pattern' => '^[0-9 ,]*$',
      '#title' => t('Size in km2', [], $tOpts),
    ];

    $elements['voluntaryHours'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'numeric', 'groupSeparator': ' ', 'digits': '0'",
      '#pattern' => '^[0-9 ]*$',
      '#title' => t('Informal voluntary work in hours', [], $tOpts),
      '#step' => 1,
    ];

    $elements['cost'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'decimal', 'groupSeparator': ' ', 'digits': '2', 'radixPoint': ',', 'substituteRadixPoint': 'true'",
      '#pattern' => '^[0-9 ,]*$',
      '#title' => t('Costs in euros', [], $tOpts),
    ];

    $elements['otherCompensations'] = [
      '#type' => 'textfield',
      '#input_mask' => "'alias': 'decimal', 'groupSeparator': ' ', 'digits': '2', 'radixPoint': ',', 'substituteRadixPoint': 'true'",
      '#pattern' => '^[0-9 ,]*$',
      '#title' => t('Grants received from others in euros', [], $tOpts),
    ];

    return $elements;
  }

}
