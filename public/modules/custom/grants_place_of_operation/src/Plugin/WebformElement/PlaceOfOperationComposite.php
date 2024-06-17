<?php

namespace Drupal\grants_place_of_operation\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\Plugin\WebformElement\GrantsCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'place_of_operation_composite' element.
 *
 * @WebformElement(
 *   id = "place_of_operation_composite",
 *   label = @Translation("Grants Place of Operation"),
 *   description = @Translation("Provides a Place of Operation element."),
 *   category = @Translation("Hel.fi elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class PlaceOfOperationComposite extends GrantsCompositeBase {

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    // Here you define your webform element's default properties,
    // which can be inherited.
    //
    // @see \Drupal\webform\Plugin\WebformElementBase::defaultProperties
    // @see \Drupal\webform\Plugin\WebformElementBase::defaultBaseProperties
    return [] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    // Here you can define and alter a webform element's properties UI.
    // Form element property visibility and default values are defined via
    // ::defaultProperties.
    //
    // @see \Drupal\webform\Plugin\WebformElementBase::form
    // @see \Drupal\webform\Plugin\WebformElement\TextBase::form
    $form['element']['placeOfOperationFields'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#title' => $this->t('Place of operation fields collected'),
      '#options' => self::buildPlaceOfOperationFieldsOptions(),
    ];

    return $form;
  }

  /**
   * Build field-list for UI.
   *
   * @return array
   *   Updated element
   *
   * @see grants_handler.module
   */
  public static function buildPlaceOfOperationFieldsOptions(): array {
    return [
      'premiseName' => t('Premise Name'),
      'premiseAddress' => t('Premise address'),
      'location' => t('Premise location'),
      'streetAddress' => t('Street Address'),
      'address' => t('Address'),
      'postCode' => t('Postal code'),
      'studentCount' => t('Student Count'),
      'specialStudents' => t('Special Students'),
      'groupCount' => t('Group Count'),
      'specialGroups' => t('Special Groups'),
      'personnelCount' => t('Personnel Count'),
      'free' => t('Free'),
      'totalRent' => t('Total Rent'),
      'rentTimeBegin' => t('Rent time begin'),
      'rentTimeEnd' => t('Rent time end'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element,
                                         WebformSubmissionInterface $webform_submission,
                                         array $options = []): array|string {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element,
                                         WebformSubmissionInterface $webform_submission,
                                         array $options = []): array {
    $value = $this->getValue($element, $webform_submission, $options);
    $lines = ['<dl>'];

    foreach ($value as $fieldName => $fieldValue) {
      if (!isset($element["#webform_composite_elements"][$fieldName])) {
        continue;
      }
      $webformElement = $element["#webform_composite_elements"][$fieldName];

      // Convert boolean value.
      if ($fieldName === 'free' && $fieldValue === 'false' || $fieldValue === FALSE) {
        $fieldValue = 0;
      }
      if ($fieldName === 'free' && $fieldValue === 'true' || $fieldValue === TRUE) {
        $fieldValue = 1;
      }

      $fieldValue = parent::formatFieldValue($webformElement, $fieldName, $fieldValue, ['rentTimeBegin', 'rentTimeEnd']);

      if (parent::isCompositeAccessible($webformElement)) {
        $lines[] = '<dt>' . parent::renderCompositeTitle($webformElement['#title']) . '</dt>';
        $lines[] = '<dd>' . $fieldValue . '</dd>';
      }

    }
    $lines[] = '</dl>';
    return $lines;
  }

}
