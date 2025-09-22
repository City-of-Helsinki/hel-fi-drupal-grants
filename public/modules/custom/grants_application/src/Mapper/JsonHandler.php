<?php

declare(strict_types=1);

namespace Drupal\grants_application\Mapper;

/**
 * Simple json handler implementation.
 */
class JsonHandler {


  public const array definitionHandlerMap = [
    'path_to_source' => 'functionName',
    'compensation.budgetInfo.incomeGroupsArrayStatic.0.otherIncomeRowsArrayStatic.0.muut_avustukset_field_0' => 'setLabelAndValue',
    'compensation.budgetInfo.costGroupsArrayStatic.1.otherCostRowsArrayStatic.0.muut_menot_4_0' => 'setLabelAndValue',
    'compensation.budgetInfo.costGroupsArrayStatic.2.otherCostRowsArrayStatic.0.muut_palveluiden_ostot_2_0' => 'setLabelAndValue',
    'compensation.budgetInfo.costGroupsArrayStatic.3.otherCostRowsArrayStatic.0.muut_menot_tarvikkeet_0' => 'setLabelAndValue',
    'compensation.budgetInfo.costGroupsArrayStatic.4.otherCostRowsArrayStatic.0.muut_menot_2_0' => 'setLabelAndValue',
    'compensation.budgetInfo.costGroupsArrayStatic.5.otherCostRowsArrayStatic.0.muut_menot_3_0' => 'setLabelAndValue',
  ];

  /**
   * Run custom handler function which returns updated definition.
   *
   * For example Form ID 52 required that one data-definition is
   * created by adding one field value as 'label' and another field value as
   * the 'value'. This is too complex operation for the mapper itself.
   *
   * @param $path
   *   Path to source data.
   * @param $data
   *   The actual data to handle.
   * @param $definition
   *   The mapping definition.
   *
   * @return mixed
   *   Anything that is needed.
   */
  public function handleDefinitionUpdate($path, $data, $definition): mixed {
    if (!isset(self::definitionHandlerMap[$path])) {
      throw new \Exception('Handler function not set.');
    }

    return call_user_func([self::class, self::definitionHandlerMap[$path]], $data, $definition);
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

    $handledData['label'] = $data[0];
    $handledData['value'] = $data[1];

    return $handledData;
  }

}
