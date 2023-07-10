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
    ];

    $elements['premiseAddress'] = [
      '#type' => 'textfield',
      '#title' => t('Premise address', [], $tOpts),
    ];

    $elements['location'] = [
      '#type' => 'textfield',
      '#title' => t('Location', [], $tOpts),
      '#maxlength' => 100,
    ];

    $elements['streetAddress'] = [
      '#type' => 'textfield',
      '#title' => t('Street Address', [], $tOpts),
      '#maxlength' => 100,
      '#prefix' => '<div class="place-of-operation-group__location">',
      '#wrapper_attributes' => [
        'class' => ['place-of-operation-group__location--address'],
      ],
    ];

    $elements['address'] = [
      '#type' => 'textfield',
      '#title' => t('Address', [], $tOpts),
    ];

    $elements['postCode'] = [
      '#type' => 'textfield',
      '#title' => t('Post Code', [], $tOpts),
      '#maxlength' => 8,
      '#pattern' => '^(FI-)?[0-9]{5}$',
      '#pattern_error' => t('Enter a valid post code.', [], $tOpts),
      '#suffix' => '</div>',
      '#wrapper_attributes' => [
        'class' => ['place-of-operation-group__location--post-code'],
      ],
    ];

    $elements['studentCount'] = [
      '#type' => 'textfield',
      '#title' => t('Student Count', [], $tOpts),
      '#maxlength' => 10,
      '#pattern' => '^[0-9]*$',
      '#pattern_error' => t('Only numbers.', [], $tOpts),
      '#prefix' => '<div class="place-of-operation-group__students">',
      '#wrapper_attributes' => [
        'class' => ['place-of-operation-group__students--student-count'],
      ],
    ];

    $elements['specialStudents'] = [
      '#type' => 'textfield',
      '#title' => t('Special Students', [], $tOpts),
      '#maxlength' => 10,
      '#pattern' => '^[0-9]*$',
      '#pattern_error' => t('Only numbers.', [], $tOpts),
      '#suffix' => '</div>',
      '#wrapper_attributes' => [
        'class' => ['place-of-operation-group__students--special-student-count'],
      ],
    ];

    $elements['groupCount'] = [
      '#type' => 'textfield',
      '#title' => t('Group Count', [], $tOpts),
      '#maxlength' => 10,
      '#pattern' => '^[0-9]*$',
      '#pattern_error' => t('Only numbers.', [], $tOpts),
      '#prefix' => '<div class="place-of-operation-group__groups">',
      '#wrapper_attributes' => [
        'class' => ['place-of-operation-group__groups--group-count'],
      ],
    ];

    $elements['specialGroups'] = [
      '#type' => 'textfield',
      '#title' => t('Special Groups', [], $tOpts),
      '#maxlength' => 10,
      '#pattern' => '^[0-9]*$',
      '#pattern_error' => t('Only numbers.', [], $tOpts),
      '#suffix' => '</div>',
      '#wrapper_attributes' => [
        'class' => ['place-of-operation-group__groups--special-group-count'],
      ],
    ];

    $elements['personnelCount'] = [
      '#type' => 'textfield',
      '#title' => t('Personnel Count', [], $tOpts),
      '#maxlength' => 10,
      '#pattern' => '^[0-9]*$',
      '#pattern_error' => t('Only numbers.', [], $tOpts),
      '#prefix' => '<div class="place-of-operation-group__personnel">',
      '#suffix' => '</div>',
      '#wrapper_attributes' => [
        'class' => ['place-of-operation-group__personnel--personnel-count'],
      ],
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
      '#title' => t('Total Rent (€)', [], $tOpts),
      '#after_build' => [[get_called_class(), 'alterState']],
      '#size' => 20,
      '#maxlength' => 20,
      '#min' => 0,
      '#input_mask' => "'alias': 'currency', 'prefix': '', 'suffix': '€','groupSeparator': ' ','radixPoint':','",
      '#prefix' => '<div class="place-of-operation-group__rent">',
      '#suffix' => '</div>',
      '#wrapper_attributes' => [
        'class' => ['place-of-operation-group__rent--rent-amount'],
      ],
    ];

    $elements['rentTimeBegin'] = [
      '#type' => 'date',
      '#title' => t('Rent time begin', [], $tOpts),
      '#after_build' => [[get_called_class(), 'alterState']],
      '#prefix' => '<div class="place-of-operation-group__time">',
      '#wrapper_attributes' => [
        'class' => [
          'hds-text-input',
          'place-of-operation-group__time--time-start',
        ],
      ],
    ];

    $elements['rentTimeEnd'] = [
      '#type' => 'date',
      '#title' => t('Rent time end', [], $tOpts),
      '#after_build' => [[get_called_class(), 'alterState']],
      '#suffix' => '</div>',
      '#wrapper_attributes' => [
        'class' => [
          'hds-text-input',
          'place-of-operation-group__time--time-end',
        ],
      ],
    ];

    // Remove all elements that are not explicitly selected for this form.
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

  /**
   * The alterState method.
   *
   * This method alters the "required" and "visible" states
   * of elements based on the value of the "free" element.
   *
   * @param array $element
   *   A form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array
   *   The altered form element.
   */
  public static function alterState(array $element, FormStateInterface $form_state): array {
    preg_match('/^(.+)\[[^]]+]$/', $element['#name'], $match);
    $compositeName = $match[1];

    if ($compositeName) {
      $element['#states']['visible'] = [
        [':input[name="' . $compositeName . '[free]"]' => ['value' => '0']],
      ];
      $element['#states']['required'] = [
        [':input[name="' . $compositeName . '[free]"]' => ['value' => '0']],
      ];
    }

    return $element;
  }

}
