<?php

namespace Drupal\grants_metadata\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\Map;

/**
 * Address DataType.
 *
 * @DataType(
 * id = "grants_metadata_kuvaerillis",
 * label = @Translation("Kulttuuri ja vapaa-aika: erillisavustus"),
 * definition_class =
 *   "\Drupal\grants_metadata\TypedData\Definition\KuvaErillisDefinition"
 * )
 */
class KuvaErillisData extends Map {

  use DataFormatTrait;

  /**
   * {@inheritdoc}
   */
  public function getPropertyPath(): ?string {
    if (isset($this->parent)) {
      // The property path of this data object is the parent's path appended
      // by this object's name.
      $prefix = $this->parent->getPropertyPath();
      return ((is_string($prefix) && strlen($prefix)) ? $prefix . '.' : '') . $this->name;
    }
    // If no parent is set, this is the root of the data tree. Thus the property
    // path equals the name of this data object.
    elseif (isset($this->name)) {
      return $this->name;
    }
    return '';
  }

}
