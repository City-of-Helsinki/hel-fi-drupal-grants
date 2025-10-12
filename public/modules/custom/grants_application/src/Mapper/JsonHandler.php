<?php

declare(strict_types=1);

namespace Drupal\grants_application\Mapper;

/**
 * Allow more complex mappings.
 */
class JsonHandler {

  /**
   * Run custom handler function which returns updated definition.
   *
   * For example Form ID 52 required that one data-definition is
   * created by adding one field value as 'label' and another field value as
   * the 'value'. This is too complex operation for the mapper itself.
   *
   * @param $handler
   *   The name of the handler function.
   * @param $data
   *   The actual data to handle.
   * @param $definition
   *   The mapping definition.
   *
   * @return mixed
   *   Anything that is needed.
   */
  public function handleDefinitionUpdate(string $handler, array $data, array $definition): mixed {
    return call_user_func([self::class, $handler], $data, $definition);
  }

  /**
   * Form ID 58: Two field values are combined into one data-definition.
   *
   * @param array $data
   *   The actual data from source.
   * @param array $definition
   *   The mapping definition.
   *
   * @return array
   *   A data-definition which is accepted by Avus2.
   */
  static function setLabelAndValue(array $data, array $definition): array {
    $handledData = $definition['data'];

    if (!is_array($data) || empty($data) || count($data) !== 2) {
      return [
        'label' => '',
        'value' => '',
      ];
    }

    $handledData['label'] = array_values($data)[0];
    $handledData['value'] = array_values($data)[1];

    return $handledData;
  }

  /**
   * Map income component.
   *
   * @param array $data
   * @param array $definition
   * @return array
   */
  static function income(array $data, array $definition): array {
    $result = [];

    foreach($data as $key => $item) {
      $mappedItem = self::setLabelAndValue($item, $definition);
      $mappedItem['ID'] .= "_$key";
      $result[] = $mappedItem;
    }

    return $result;
  }

}
