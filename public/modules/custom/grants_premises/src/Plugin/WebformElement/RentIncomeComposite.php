<?php

namespace Drupal\grants_premises\Plugin\WebformElement;

use Drupal\grants_premises\Plugin\GrantsPremisesBase;

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

}
