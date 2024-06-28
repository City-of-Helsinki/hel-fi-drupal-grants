<?php

namespace Drupal\grants_club_section\Plugin\WebformElement;

use Drupal\grants_handler\Plugin\WebformElement\GrantsCompositeBase;

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
class ClubSectionComposite extends GrantsCompositeBase {

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

}
