<?php

namespace Drupal\grants_premises\Plugin;

use Drupal\grants_handler\Plugin\WebformElement\GrantsCompositeBase;

/**
 * The base class for all Premises components.
 */
class GrantsPremisesBase extends GrantsCompositeBase {

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
