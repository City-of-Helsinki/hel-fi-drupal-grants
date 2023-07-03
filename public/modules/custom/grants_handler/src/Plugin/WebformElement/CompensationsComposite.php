<?php

namespace Drupal\grants_handler\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'webform_example_composite' element.
 *
 * @WebformElement(
 *   id = "grants_compensations",
 *   label = @Translation("Grants Compensations"),
 *   description = @Translation("Element for compensations element"),
 *   category = @Translation("Grants"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\webform_example_composite\Element\WebformExampleComposite
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class CompensationsComposite extends WebformCompositeBase {

  /**
   * Compensation types.
   *
   * @var string[]
   */
  protected static $optionsForTypes;

  /**
   * Return options for different compensation types.
   *
   * @return string[]
   *   Compensation types.
   */
  public static function getOptionsForTypes($langcode = NULL): array {
    if ($langcode === NULL) {
      $langcode = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    if (!isset(self::$optionsForTypes)) {
      self::$optionsForTypes = [];
    }
    if (!isset(self::$optionsForTypes[$langcode])) {
      $config = \Drupal::service('translated_config.helper')->getTranslatedConfig('grants_metadata.settings', $langcode);
      $thirdPartyOpts = $config->get('third_party_options');
      self::$optionsForTypes[$langcode] = (array) $thirdPartyOpts['subvention_types'];
    }

    return self::$optionsForTypes[$langcode];
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {
    // Here you define your webform element's default properties,
    // which can be inherited.
    //
    // @see \Drupal\webform\Plugin\WebformElementBase::defaultProperties
    // @see \Drupal\webform\Plugin\WebformElementBase::defaultBaseProperties
    $parent = parent::defineDefaultProperties();

    return [
      'amount' => '',
      'subventionType' => '',
      'requiredSubventionType' => '',
      'onlyOneSubventionPerApplication' => 0,
        // 'subventionTypeName' => '',
    ] + $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $retval = parent::getValue($element, $webform_submission, $options);
    return $retval;
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
    $form['element']['subventionType'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => $this->t('Subvention type'),
      '#options' => self::getOptionsForTypes(),
    ];

    $form['element']['requiredSubventionType'] = [
      '#type' => 'select',
      '#multiple' => FALSE,
      '#title' => $this->t('Required subvention type'),
      '#description' => $this->t('Applicant must always apply for this type in this application'),
      '#options' => ['' => t('- Select -')] + self::getOptionsForTypes(),
    ];

    $form['element']['onlyOneSubventionPerApplication'] = [
      '#type' => 'radios',
      '#title' => $this->t('Allow the applicant to apply for only one type of subvention.'),
      '#description' => $this->t('If you want to configure that applicant is only able to apply for one subvention type'),
      '#options' => [
        0 => t('No'),
        1 => t('Yes'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    $types = self::getOptionsForTypes();

    return [
      $types[$value['subventionType']] . ': ' . $value['amount'],

    ];
  }

}
