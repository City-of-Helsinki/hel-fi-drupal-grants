<?php

namespace Drupal\grants_premises\Plugin;

use Drupal\grants_composites\Plugin\GrantsCompositesBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * The base class for all Premises components.
 */
class GrantsPremisesBase extends GrantsCompositesBase {

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
