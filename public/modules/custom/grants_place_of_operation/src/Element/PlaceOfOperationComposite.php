<?php

namespace Drupal\grants_place_of_operation\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'place_of_operation_composite'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. toimipaikka_composite)
 *
 * @FormElement("place_of_operation_composite")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 */
class PlaceOfOperationComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'place_of_operation_composite'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];
    $tOpts = ['context' => 'grants_place_of_operation'];

    $elements['location'] = [
      '#type' => 'textfield',
      '#title' => t('Location', [], $tOpts),
    ];

    $elements['streetAddress'] = [
      '#type' => 'textfield',
      '#title' => t('Street Address', [], $tOpts),
    ];

    $elements['postCode'] = [
      '#type' => 'textfield',
      '#title' => t('Post Code', [], $tOpts),
      '#size' => 10,
    ];

    $elements['studentCount'] = [
      '#type' => 'textfield',
      '#title' => t('Student Count', [], $tOpts),
    ];

    $elements['specialStudents'] = [
      '#type' => 'textfield',
      '#title' => t('Special Students', [], $tOpts),
    ];

    $elements['groupCount'] = [
      '#type' => 'textfield',
      '#title' => t('Group Count', [], $tOpts),
    ];

    $elements['specialGroups'] = [
      '#type' => 'textfield',
      '#title' => t('Special Groups', [], $tOpts),
    ];

    $elements['personnelCount'] = [
      '#type' => 'textfield',
      '#title' => t('Personnel Count', [], $tOpts),
    ];

    $elements['free'] = [
      '#type' => 'radios',
      '#options' => [
        1 => t('Yes', [], $tOpts),
        0 => t('No', [], $tOpts),
      ],
      '#title' => t('Is premise free', [], $tOpts),
    ];

    $elements['totalRent'] = [
      '#type' => 'textfield',
      '#title' => t('Total Rent', [], $tOpts),
    ];

    $elements['rentTimeBegin'] = [
      '#type' => 'datetime',
      '#title' => t('Rent time begin', [], $tOpts),
      '#wrapper_attributes' => [
        'class' => ['hds-text-input'],
      ],
    ];

    $elements['rentTimeEnd'] = [
      '#type' => 'datetime',
      '#title' => t('Rent time end', [], $tOpts),
      '#wrapper_attributes' => [
        'class' => ['hds-text-input'],
      ],
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);
    return $element;
  }

}
