<?php

namespace Drupal\grants_club_section\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'club_section_composite' element.
 *
 * @WebformElement(
 *   id = "club_section_composite",
 *   label = @Translation("Grants club section"),
 *   description = @Translation("Provides a club section element."),
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
class ClubSectionComposite extends WebformCompositeBase {

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
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array|string {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $value = $this->getValue($element, $webform_submission, $options);
    $lines = [];
    foreach ($value as $fieldName => $fieldValue) {
      if (isset($element["#webform_composite_elements"][$fieldName])) {
        $webformElement = $element["#webform_composite_elements"][$fieldName];

        $value2 = $webformElement['#options'][$fieldValue] ?? NULL;

        if (!isset($webformElement['#access']) || ($webformElement['#access'] !== FALSE)) {
          if (isset($value2)) {
            $lines[] = '<strong>' . $webformElement['#title'] . '</strong>';
            $lines[] = $value2 . '<br>';
          }
          elseif (!is_string($webformElement['#title'])) {
            $lines[] = '<strong>' . $webformElement['#title']->render() . '</strong>';
            $lines[] = $fieldValue . '<br>';
          }
          elseif (is_string($webformElement['#title'])) {
            $lines[] = '<strong>' . $webformElement['#title'] . '</strong>';
            $lines[] = $fieldValue . '<br>';
          }
        }
      }
    }

    return $lines;
  }

}
