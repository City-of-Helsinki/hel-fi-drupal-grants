<?php

namespace Drupal\grants_premises\Plugin;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * The base class for all Premises components.
 */
class GrantsPremisesBase extends WebformCompositeBase {

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
