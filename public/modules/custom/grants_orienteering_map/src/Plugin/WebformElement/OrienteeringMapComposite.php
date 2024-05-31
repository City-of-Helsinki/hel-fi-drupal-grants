<?php

namespace Drupal\grants_orienteering_map\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'orienteering_map_composite' element.
 *
 * @WebformElement(
 *   id = "orienteering_map_composite",
 *   label = @Translation("Grants Orienteering map"),
 *   description = @Translation("Provides a Orienteering map element."),
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
class OrienteeringMapComposite extends WebformCompositeBase {

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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array|string {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $value = $this->getValue($element, $webform_submission, $options);
    $lines = [];
    $lines[] = '<dl>';

    foreach ($value as $fieldName => $fieldValue) {
      if (isset($element["#webform_composite_elements"][$fieldName])) {
        $webformElement = $element["#webform_composite_elements"][$fieldName];

        $value2 = $webformElement['#options'][$fieldValue] ?? NULL;

        if (!isset($webformElement['#access']) || ($webformElement['#access'] !== FALSE)) {
          if (isset($value2)) {
            $lines[] = '<dt>' . $webformElement['#title'] . '</dt>';
            $lines[] = '<dd>' . $value2 . '</dd>';
          }
          elseif (!is_string($webformElement['#title'])) {
            $lines[] = '<dt>' . $webformElement['#title']->render() . '</dt>';
            $lines[] = '<dd>' . $fieldValue . '</dd>';
          }
          elseif (is_string($webformElement['#title'])) {
            $lines[] = '<dt>' . $webformElement['#title'] . '</dt>';
            $lines[] = '<dd>' . $fieldValue . '</dd>';
          }
        }
      }
    }
    $lines[] = '</dl>';
    return $lines;
  }

}
