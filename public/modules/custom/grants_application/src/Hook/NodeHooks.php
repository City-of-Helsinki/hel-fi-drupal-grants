<?php

declare(strict_types=1);

namespace Drupal\grants_application\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Entity\EntityInterface;
use Drupal\grants_handler\Entity\Node\ServicePage;

/**
 * Views hook implementations for grants_application.
 */
class NodeHooks {

  /**
   * When react form is selected, empty the webform related fields.
   */
  #[Hook('node_presave')]
  public function nodePresave(EntityInterface $entity): void {
    if (!$entity instanceof ServicePage) {
      return;
    }

    // If the React form is set, empty the webform related fields.
    $doClearFields = !$entity->get('field_react_form')->isEmpty();
    if (!$doClearFields) {
      return;
    }

    foreach ($this->getFieldsToClear() as $fieldName) {
      if (!$entity->hasField($fieldName) || $entity->get($fieldName)->isEmpty()) {
        continue;
      }

      $entity->set($fieldName, []);
    }
  }

  /**
   * List of fields to clear.
   *
   * @return string[]
   *   List of fields to clear.
   */
  private function getFieldsToClear(): array {
    return [
      'field_webform',
      'field_avustuslaji',
      'field_industry',
      'field_target_group',
      'field_hakijatyyppi',
      'field_application_continuous',
      'field_application_period',
      'field_acting_years_type',
      'field_application_acting_years',
      'field_acting_years_next_count',
    ];
  }

}
