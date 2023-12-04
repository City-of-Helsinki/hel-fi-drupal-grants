<?php

namespace Drupal\grants_metadata;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\ListInterface;
use Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler;

/**
 * Service for getting & setting data values from & to JSON structure.
 */
class CompensationService {

  /**
   * Parse previousyear compensations.
   *
   * Take in property definition & data from form and transform it to JSON
   * structure specified in the example.
   *
   * @param \Drupal\Core\TypedData\ListInterface $property
   *   Property that is handled.
   * @param array $arguments
   *   Any extra arguments, eg used webform for meta fields.
   *
   * @return array
   *   Processed items.
   */
  public function processPreviousYearCompensations(ListInterface $property, array $arguments): array {
    $retval = [];
    // Get data.
    $submittedFormData = $arguments['submittedData'];
    $toimintaAvustus = $submittedFormData["yhdistyksen_kuluvan_vuoden_toiminta_avustus"] ?? '';
    $usedToimintaAvustus = $submittedFormData["selvitys_kuluvan_vuoden_toiminta_avustuksen_kaytosta"] ?? '';
    $hasToimintaAvustus = !empty($toimintaAvustus) && !empty($usedToimintaAvustus);

    // If toiminta-avustus values are set.
    if ($hasToimintaAvustus) {
      // Parse them.
      $toimintaAvustusArray = [
        [
          'ID' => 'subventionType',
          'label' => 'Avustuslaji',
          'valueType' => 'string',
          'value' => '1',
        ],
      ];

      if (!empty($toimintaAvustus)) {
        $toimintaAvustusArray[] = [
          'ID' => 'amount',
          'label' => 'Euroa',
          'valueType' => 'double',
          'value' => (string) GrantsHandler::convertToFloat($toimintaAvustus),
        ];
      }
      if (!empty($usedToimintaAvustus)) {
        $toimintaAvustusArray[] = [
          'ID' => 'usedAmount',
          'label' => 'Euroa',
          'valueType' => 'double',
          'value' => (string) GrantsHandler::convertToFloat($usedToimintaAvustus),
        ];
      }
      // And add to return array.
      $retval[] = $toimintaAvustusArray;
    }

    $palkkausAvustus = $submittedFormData["yhdistyksen_kuluvan_vuoden_palkkausavustus_"] ?? '';
    $usedPalkkausAvustus = $submittedFormData["selvitys_kuluvan_vuoden_palkkausavustuksen_kaytosta"] ?? '';

    $hasPalkkausAvustus = !empty($palkkausAvustus) && !empty($usedPalkkausAvustus);

    if ($hasPalkkausAvustus) {
      $palkkausAvustusArray = [
        [
          'ID' => 'subventionType',
          'label' => 'Avustuslaji',
          'valueType' => 'string',
          'value' => '2',
        ],
      ];

      if (!empty($palkkausAvustus)) {
        $palkkausAvustusArray[] = [
          'ID' => 'amount',
          'label' => 'Euroa',
          'valueType' => 'double',
          'value' => (string) GrantsHandler::convertToFloat($palkkausAvustus),
        ];
      }
      if (!empty($usedPalkkausAvustus)) {
        $palkkausAvustusArray[] = [
          'ID' => 'usedAmount',
          'label' => 'Euroa',
          'valueType' => 'double',
          'value' => (string) GrantsHandler::convertToFloat($usedPalkkausAvustus),
        ];
      }
      $retval[] = $palkkausAvustusArray;
    }

    return $retval;

  }

  /**
   * Extact data from document structure.
   *
   * Return webformable data for given fields.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinition $property
   *   Property.
   * @param array $content
   *   Doc content.
   *
   * @return array
   *   Values
   */
  public function extractDataForWebformPreviousYear(ListDataDefinition $property, array $content): array {

    $values = [];
    // GEt data from document content.
    $previousYear = $content["compensation"]["compensationInfo"]["previousYearArray"] ?? [];

    // Loop data & generate webform structure with values.
    foreach ($previousYear as $items) {
      /* First filter out subvention type variable, cannot get by key since we
      cannot be sure what keys they are in, so the must be filtered from
      structure.*/
      $subType = array_filter($items, function ($item) {
        if ($item['ID'] === 'subventionType') {
          return TRUE;
        }
        return FALSE;
      });
      if (!empty($subType)) {
        $subType = array_values($subType);
        $subType = $subType[0]['value'];
      }
      // Then get sub amount.
      $subAmount = array_filter($items, function ($item) {
        if ($item['ID'] === 'amount') {
          return TRUE;
        }
        return FALSE;
      });
      if (!empty($subAmount)) {
        $subAmount = array_values($subAmount);
        $subAmount = str_replace('.', ',', $subAmount[0]['value']);
      }

      // And finally used sub amount.
      $subUsedAmount = array_filter($items, function ($item) {
        if ($item['ID'] === 'usedAmount') {
          return TRUE;
        }
        return FALSE;
      });
      if (!empty($subUsedAmount)) {
        $subUsedAmount = array_values($subUsedAmount);
        $subUsedAmount = str_replace('.', ',', $subUsedAmount[0]['value']);
      }
      // Set values to be given to form / preview / whatever.
      if ($subType === '1') {
        $values["yhdistyksen_kuluvan_vuoden_toiminta_avustus"] = $subAmount;
        $values["selvitys_kuluvan_vuoden_toiminta_avustuksen_kaytosta"] = $subUsedAmount;
      }
      if ($subType === '2') {
        $values["yhdistyksen_kuluvan_vuoden_palkkausavustus_"] = $subAmount;
        $values["selvitys_kuluvan_vuoden_palkkausavustuksen_kaytosta"] = $subUsedAmount;
      }
    }

    return $values;
  }

}
