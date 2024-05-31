<?php

namespace Drupal\grants_premises\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_premises\Plugin\GrantsPremisesBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'rent_income_composite' element.
 *
 * @WebformElement(
 *   id = "rent_income_composite",
 *   label = @Translation("Grants rental income"),
 *   description = @Translation("Provides a rental income element."),
 *   category = @Translation("Hel.fi elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\grants_premises\Plugin\GrantsPremisesBase
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class RentIncomeComposite extends GrantsPremisesBase {

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
    $submissionValue = $this->getValue($element, $webform_submission, $options);
    $lines = [];
    $lines[] = '<dl>';

    foreach ($submissionValue as $fieldName => $fieldValue) {
      if (isset($element["#webform_composite_elements"][$fieldName])) {
        $webformElement = $element["#webform_composite_elements"][$fieldName];
        $value = $webformElement['#options'][$fieldValue] ?? NULL;

        // Convert date strings.
        if ($fieldName === 'dateBegin' || $fieldName === 'dateEnd') {
          if ($fieldValue) {
            $fieldValue = date("d.m.Y", strtotime(date($fieldValue)));
          }
        }
        if (!isset($webformElement['#access']) || ($webformElement['#access'] !== FALSE)) {
          if (isset($value)) {
            $lines[] = '<dt>' . $webformElement['#title'] . '</dt>';
            $lines[] = '<dd>' . $value . '</dd>';
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
