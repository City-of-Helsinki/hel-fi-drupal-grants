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

    $elements['premiseName'] = [
      '#type' => 'textfield',
      '#title' => t('Premise name', [], $tOpts),
      '#access' => TRUE,
    ];

    $elements['premiseAddress'] = [
      '#type' => 'textfield',
      '#title' => t('Premise address', [], $tOpts),
      '#access' => TRUE,
    ];

    $elements['location'] = [
      '#type' => 'textfield',
      '#title' => t('Location', [], $tOpts),
    ];

    $elements['streetAddress'] = [
      '#type' => 'textfield',
      '#title' => t('Street Address', [], $tOpts),
    ];

    $elements['address'] = [
      '#type' => 'textfield',
      '#title' => t('Address', [], $tOpts),
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

    /* Remove all elements from elements that are not explicitly selected
    for this form. Hopefully this fixes issues with data fields. */
    foreach ($element as $fieldName => $value) {
      if (str_contains($fieldName, '__access')) {
        $fName = str_replace('__access', '', $fieldName);
        $fName = str_replace('#', '', $fName);
        if ($value === FALSE && isset($elements[$fName])) {
          unset($elements[$fName]);
        }
      }
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form): array {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);
    $elementValue = $element['#value'];

    if (isset($element["free"]) && $elementValue["free"] === "false") {
      $element["free"]["#default_value"] = 0;
    }
    if (isset($element["free"]) && $elementValue["free"] === "true") {
      $element["free"]["#default_value"] = 1;
    }

    return $element;
  }

}
