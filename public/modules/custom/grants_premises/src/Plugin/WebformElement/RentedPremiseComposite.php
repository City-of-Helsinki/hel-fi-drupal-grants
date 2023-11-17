<?php

namespace Drupal\grants_premises\Plugin\WebformElement;

use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\grants_premises\Plugin\GrantsPremisesBase;

/**
 * Provides a 'rented_premise_composite' element.
 *
 * @WebformElement(
 *   id = "rented_premise_composite",
 *   label = @Translation("Grants rented premise"),
 *   description = @Translation("Provides a rented premise element."),
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
class RentedPremiseComposite extends GrantsPremisesBase {

}
