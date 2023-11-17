<?php

namespace Drupal\grants_premises\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

class GrantsPremisesBase extends WebformCompositeBase
{
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
