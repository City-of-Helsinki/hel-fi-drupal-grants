<?php

namespace Drupal\grants_premises\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_premises\Plugin\GrantsCompositesBase;

/**
 * Provides a 'premises_composite' element.
 *
 * @WebformElement(
 *   id = "premises_composite",
 *   label = @Translation("Grants premises"),
 *   description = @Translation("Provides a premises element."),
 *   category = @Translation("Hel.fi elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drupal\grants_premises\Plugin\GrantsCompositesBase
 * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class CompositesComposite extends GrantsCompositesBase {

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
    $form['element']['premiseFields'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#multiple' => TRUE,
      '#title' => $this->t('Premise fields collected'),
      '#options' => self::buildPremiseFieldsOptions(),
    ];

    return $form;
  }

  /**
   * Build fieldlist for UI.
   *
   * @return array
   *   Updated element
   *
   * @see grants_handler.module
   */
  public static function buildPremiseFieldsOptions(): array {

    return [
      'premiseType' => t('Premise Type'),
      'premiseName' => t('Premise Name'),
      'premiseAddress' => t('Premise Address'),
      'location' => t('Premise location'),
      'streetAddress' => t('Street Address'),
      'address' => t('Address'),
      'postCode' => t('Postal code'),
      'studentCount' => t('Student Count'),
      'specialStudents' => t('Special Students'),
      'groupCount' => t('Group Count'),
      'specialGroups' => t('Special Groups'),
      'personnelCount' => t('Personnel Count'),
      'totalRent' => t('Total Rent'),
      'rentTimeBegin' => t('Rent time begin'),
      'rentTimeEnd' => t('Rent time end'),
      'free' => t('Free'),
    ];

  }

}
