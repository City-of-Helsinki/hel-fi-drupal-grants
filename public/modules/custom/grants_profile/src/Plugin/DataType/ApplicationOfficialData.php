<?php

declare(strict_types=1);

namespace Drupal\grants_profile\Plugin\DataType;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\Attribute\DataType;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\grants_profile\TypedData\Definition\ApplicationOfficialDefinition;

/**
 * Address DataType.
 */
#[DataType(
  id: 'grants_profile_application_official',
  label: new TranslatableMarkup('Application Official'),
  definition_class: ApplicationOfficialDefinition::class
)]
class ApplicationOfficialData extends Map {

  /**
   * Set field value.
   *
   * @param array $values
   *   Values for the field.
   */
  public function setValues(array $values): void {
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {

    /* With unregistered communities, officials do no have roles, so we need to
    force role to 0, it HAS to be an integer because of the data
    type in json. */
    if (!isset($values['role']) || $values['role'] == "") {
      $values['role'] = 0;
    }

    parent::setValue($values, $notify);
  }

}
