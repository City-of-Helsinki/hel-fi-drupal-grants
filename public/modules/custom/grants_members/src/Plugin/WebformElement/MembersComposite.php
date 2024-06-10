<?php

namespace Drupal\grants_members\Plugin\WebformElement;

use Drupal\grants_handler\Plugin\WebformElement\GrantsCompositeBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a 'members_composite' element.
 *
 * @WebformElement(
 *   id = "members_composite",
 *   label = @Translation("Grants Members"),
 *   description = @Translation("Provides a Members element."),
 *   category = @Translation("Hel.fi elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 *
 * @see \Drrupal\webform\Plugin\WebformElement\GrantsCompositeBase
 * @see \Drupal\webform\Plugin\WebformElementBase
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Annotation\WebformElement
 */
class MembersComposite extends GrantsCompositeBase {

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
