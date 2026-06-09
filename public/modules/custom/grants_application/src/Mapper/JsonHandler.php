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
   * @param string $handler
   *   The name of the handler function.
   * @param mixed $data
   *   The actual data to handle.
   * @param array $definition
   *   The mapping definition.
   *
   * @return mixed
   *   Anything that is needed.
   */
  public function handleDefinitionUpdate(string $handler, mixed $data, array $definition): mixed {
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
  public static function setLabelAndValue(array $data, array $definition): array {
    $handledData = $definition['data'];

    if (empty($data) || count($data) !== 2) {
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
   *   The data.
   * @param array $definition
   *   Mapping definition.
   *
   * @return array|false
   *   The income field data.
   */
  public static function income(array $data, array $definition): array|FALSE {
    $result = [];

    if (!$data) {
      return FALSE;
    }

    foreach ($data as $key => $item) {
      $mappedItem = self::setLabelAndValue($item, $definition);
      $mappedItem['ID'] .= "_$key";
      $mappedItem['value'] = str_replace(',', '.', rtrim($mappedItem['value'], ','));
      $result[] = $mappedItem;
    }

    return $result;
  }

  /**
   * Map a scalar enum value to a human-readable label using a value_map.
   *
   * @param string $data
   *   The raw enum value from form data (e.g. "1", "2", "3").
   * @param array<string, mixed> $definition
   *   Mapping definition; must contain data.value_map keyed by enum values.
   *
   * @return string
   *   The mapped label, or the original value if no mapping exists.
   */
  public static function enumToLabel(string $data, array $definition): string {
    $valueMap = $definition['value_map'] ?? [];
    return $valueMap[$data] ?? $data;
  }

}
