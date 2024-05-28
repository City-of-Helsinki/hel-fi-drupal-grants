<?php

namespace Drupal\grants_applicant_info\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'premises_composite' element.
 *
 * @WebformElement(
 *   id = "applicant_info",
 *   label = @Translation("Grants Applicant info"),
 *   description = @Translation("Provides a premises elemebnt."),
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
class ApplicantInfoComposite extends WebformCompositeBase {

  /**
   * Routes which should use submission data.
   */
  const SUBMISSION_DATA_ROUTES = [
    'grants_handler.view_application',
  ];

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->fileSystem = $container->get('file_system');
    $instance->renderer = $container->get('renderer');
    $instance->generate = $container->get('webform_submission.generate');
    $instance->currentRouteMatch = $container->get('current_route_match');
    return $instance;
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
    return [] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItemValue(
    array $element,
    WebformSubmissionInterface $webform_submission,
    array $options = []): array|string {
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
        if ($webformElement && isset($webformElement['#title'])) {
          $lines[] = '<dt>' . $webformElement['#title']->render() . '</dt>';
          $lines[] = '<dd>' . $fieldValue . '</dd>';
        }
      }
    }
    $lines[] = '</dl>';

    return $lines;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $retval = [];

    foreach ($element["#webform_composite_elements"] as $elName => $elVal) {
      $retval[$elName] = $elVal['#value'];
    }

    $routeName = $this->currentRouteMatch->getRouteName();

    // Override data with submission data, if we are in
    // sent / draft application view.
    if (in_array($routeName, self::SUBMISSION_DATA_ROUTES)) {
      $submissionData = $webform_submission->getData();
      $retval = array_merge($retval, $submissionData['hakijan_tiedot']);
    }

    return $retval;

  }

}
