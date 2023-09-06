<?php

namespace Drupal\grants_members\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'members_composite'.
 *
 * Webform composites contain a group of sub-elements.
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. members_composite)
 *
 * @FormElement("members_composite")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 */
class MembersComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'members_composite'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];
    $tOpts = ['context' => 'grants_members'];

    $elements['organizationName'] = [
      '#type' => 'textfield',
      '#title' => t('Organization name', [], $tOpts),
    ];

    $elements['fee'] = [
      '#type' => 'number',
      '#title' => t('Fee, euros', [], $tOpts),
    ];

    return $elements;
  }

}
