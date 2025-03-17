<?php

declare(strict_types=1);

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_profile\TypedData\Definition\GrantsProfileRegisteredCommunityDefinition;

/**
 * Address DataType.
 */
#[DataType(
  id: 'grants_profile_registered_community',
  label: new TranslatableMarkup('Grants Profile RC'),
  definition_class: GrantsProfileRegisteredCommunityDefinition::class
)]
class GrantsProfileRegisteredCommunityData extends Map {

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {

    if ($values['companyStatusSpecial'] == NULL) {
      $values['companyStatusSpecial'] = '';
    }
    parent::setValue($values, $notify);
  }

}
