<?php

namespace Drupal\grants_handler\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * The base class for all Premises components.
 */
class GrantsCompositeBase extends WebformCompositeBase {

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
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (!$this->hasValue($element, $webform_submission, $options)) {
      return '';
    }

    $format = $this->getItemFormat($element);

    // Handle custom composite html items.
    if ($format === 'custom' && !empty($element['#format_html'])) {
      return $this->formatCustomItem('html', $element, $webform_submission, $options);
    }

    switch ($format) {
      case 'list':
      case 'raw':
        $items = $this->formatCompositeHtmlItems($element, $webform_submission, $options);
        return [
          '#theme' => 'item_list',
          '#items' => $items,
        ];

      default:
        $lines = $this->formatHtmlItemValue($element, $webform_submission, $options);
        if (empty($lines)) {
          return '';
        }
        foreach ($lines as $key => $line) {
          if (is_string($line)) {
            $lines[$key] = ['#markup' => $line];
          }
        }
        return $lines;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(
    array $element,
    WebformSubmissionInterface $webform_submission,
    array $options = [],
  ): array|string {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(
    array $element,
    WebformSubmissionInterface $webform_submission,
    array $options = [],
  ): array {
    $value = $this->getValue($element, $webform_submission, $options);
    $lines = ['<dl>'];

    foreach ($value as $fieldName => $fieldValue) {
      if (!isset($element["#webform_composite_elements"][$fieldName])) {
        continue;
      }

      $webformElement = $element["#webform_composite_elements"][$fieldName];
      $formattedValue = $this->formatFieldValue($webformElement, $fieldName, $fieldValue);

      if ($this->isCompositeAccessible($webformElement)) {
        $title = $this->renderCompositeTitle($webformElement['#title']);
        $lines[] = "<dt>" . $title . "</dt>";
        $lines[] = "<dd>" . $formattedValue . "</dd>";
      }
    }

    $lines[] = '</dl>';
    return $lines;
  }

  /**
   * Format Field value for a webform element.
   *
   * @param array $webformElement
   *   Webform Element.
   * @param string $fieldName
   *   Field name in question.
   * @param mixed $fieldValue
   *   Value of the Field.
   * @param array $dateFieldNamesArray
   *   Array of Date Fields names.
   *
   * @return mixed
   *   The formatted Field Value.
   */
  public function formatFieldValue(
    array $webformElement,
    string $fieldName,
    mixed $fieldValue,
    array $dateFieldNamesArray = ['dateBegin', 'dateEnd'],
  ): mixed {
    if ($fieldValue === NULL) {
      return NULL;
    }
    if (in_array($fieldName, $dateFieldNamesArray) && $fieldValue) {
      return date("d.m.Y", strtotime($fieldValue));
    }
    return $webformElement['#options'][$fieldValue] ?? $fieldValue;
  }

  /**
   * Check if access to the composite is not prohibited.
   *
   * @param array $webformElement
   *   The webformelement whose access is needed to be checked.
   *
   * @return bool
   *   Is the composite accessible.
   */
  public function isCompositeAccessible($webformElement) {
    return !isset($webformElement['#access']) || $webformElement['#access'] !== FALSE;
  }

  /**
   * Since our titles might need rendering, this makes sure we have a string.
   *
   * @param mixed $title
   *   The title.
   *
   * @return string
   *   The correctly rendered title.
   */
  public function renderCompositeTitle($title) {
    return is_string($title) ? $title : $title->render();
  }

}
