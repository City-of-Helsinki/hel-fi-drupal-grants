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

    $toimintaAvustusData = $this->processToimintaAvustus($arguments);
    if (!empty($toimintaAvustusData)) {
      $retval = array_merge($retval, $toimintaAvustusData);
    }

    $palkkausAvustusData = $this->processPalkkausAvustus($arguments);
    if (!empty($palkkausAvustusData)) {
      $retval = array_merge($retval, $palkkausAvustusData);
    }

    return $retval;

  }

  /**
   * Process palkkaus toimintaavustus data to TypedDataArray.
   *
   * @param array $arguments
   *   Any extra arguments, eg used webform for meta fields.
   */
  private function processToimintaAvustus($arguments) {
    $submittedFormData = $arguments['submittedData'];
    $toimintaAvustus = $submittedFormData["yhdistyksen_kuluvan_vuoden_toiminta_avustus"] ?? '';
    $usedToimintaAvustus = $submittedFormData["selvitys_kuluvan_vuoden_toiminta_avustuksen_kaytosta"] ?? '';
    $hasToimintaAvustus = !empty($toimintaAvustus) && !empty($usedToimintaAvustus);
    $webform = $arguments['webform'];
    $elements = $webform->getElementsDecodedAndFlattened();

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

      [$page, $section] = $this->getPageAndSectionMeta($webform, 'edellisen_avustuksen_kayttoselvitys');

      if (!empty($toimintaAvustus)) {
        $elementArray = [
          'ID' => 'amount',
          'label' => 'Euroa',
          'valueType' => 'double',
          'value' => (string) GrantsHandler::convertToFloat($toimintaAvustus),
        ];

        $this->processFieldMeta($elementArray, $elements['yhdistyksen_kuluvan_vuoden_toiminta_avustus'], $page, $section);
        $toimintaAvustusArray[] = $elementArray;
      }
      if (!empty($usedToimintaAvustus)) {
        $elementArray = [
          'ID' => 'usedAmount',
          'label' => 'Euroa',
          'valueType' => 'double',
          'value' => (string) GrantsHandler::convertToFloat($usedToimintaAvustus),
        ];

        $this->processFieldMeta($elementArray, $elements['yhdistyksen_kuluvan_vuoden_toiminta_avustus'], $page, $section);
        $toimintaAvustusArray[] = $elementArray;
      }
      // And add to return array.
      $retval[] = $toimintaAvustusArray;
    }

    return $retval;
  }

  /**
   * Process palkkaus avustus data to TypedDataArray.
   *
   * @param array $arguments
   *   Any extra arguments, eg used webform for meta fields.
   */
  private function processPalkkausAvustus($arguments) {
    $submittedFormData = $arguments['submittedData'];
    $webform = $arguments['webform'];
    $elements = $webform->getElementsDecodedAndFlattened();

    $palkkausAvustus = $submittedFormData["yhdistyksen_kuluvan_vuoden_palkkausavustus_"] ?? '';
    $usedPalkkausAvustus = $submittedFormData["selvitys_kuluvan_vuoden_palkkausavustuksen_kaytosta"] ?? '';

    $hasPalkkausAvustus = !empty($palkkausAvustus) && !empty($usedPalkkausAvustus);
    [$page, $section] = $this->getPageAndSectionMeta($webform, 'edellisen_avustuksen_kayttoselvitys');

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
        $elementArray = [
          'ID' => 'amount',
          'label' => 'Euroa',
          'valueType' => 'double',
          'value' => (string) GrantsHandler::convertToFloat($palkkausAvustus),
        ];

        $this->processFieldMeta($elementArray, $elements['yhdistyksen_kuluvan_vuoden_palkkausavustus_'], $page, $section);
        $palkkausAvustusArray[] = $elementArray;
      }
      if (!empty($usedPalkkausAvustus)) {
        $elementArray = [
          'ID' => 'usedAmount',
          'label' => 'Euroa',
          'valueType' => 'double',
          'value' => (string) GrantsHandler::convertToFloat($usedPalkkausAvustus),
        ];

        $this->processFieldMeta($elementArray, $elements['selvitys_kuluvan_vuoden_palkkausavustuksen_kaytosta'], $page, $section);
        $palkkausAvustusArray[] = $elementArray;
      }
      $retval[] = $palkkausAvustusArray;
    }

    return $retval;
  }

  /**
   * Filter array values by id attribute.
   *
   * @param array $items
   *   Array of items.
   * @param string $id
   *   Id to filter items by.
   */
  private function filterById($items, $id) {
    return array_filter($items, function ($item) use ($id) {
      if ($item['ID'] === $id) {
        return TRUE;
      }
      return FALSE;
    });
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
      $subType = $this->filterById($items, 'subventionType');
      if (!empty($subType)) {
        $subType = array_values($subType);
        $subType = $subType[0]['value'];
      }
      // Then get sub amount.
      $subAmount = $this->filterById($items, 'amount');
      if (!empty($subAmount)) {
        $subAmount = array_values($subAmount);
        $meta = json_decode($subAmount[0]['meta'] ?? '', TRUE);
        $subAmount = InputmaskHandler::convertPossibleInputmaskValue($subAmount[0]['value'], $meta);
      }

      // And finally used sub amount.
      $subUsedAmount = $this->filterById($items, 'usedAmount');
      if (!empty($subUsedAmount)) {
        $subUsedAmount = array_values($subUsedAmount);
        $meta = json_decode($subUsedAmount[0]['meta'] ?? '', TRUE);
        $subUsedAmount = InputmaskHandler::convertPossibleInputmaskValue($subUsedAmount[0]['value'], $meta);
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

  /**
   * Adds possible fieldmeta data to elementArray.
   *
   * @param array $elementArray
   *   The element array reference.
   * @param array $formElement
   *   Form element array of given element.
   * @param array $page
   *   Page meta data array.
   * @param array $section
   *   Section meta data array.
   */
  private function processFieldMeta(array &$elementArray, array $formElement, array $page, array $section) {
    $element = [
      'label' => $formElement['#title'],
    ];
    InputmaskHandler::addInputmaskToMetadata($element, $formElement);
    $elementArray['meta'] = json_encode(AtvSchema::getMetaData($page, $section, $element), JSON_UNESCAPED_UNICODE);
  }

  /**
   * Generates page and section metadata.
   */
  private function getPageAndSectionMeta($webform, $fieldKey) {
    $webformMainElement = $webform->getElement($fieldKey);

    $elements = $webform->getElementsDecodedAndFlattened();
    $elementKeys = array_keys($elements);

    $pages = $webform->getPages('edit');

    $pageId = $webformMainElement['#webform_parents'][0];
    $pageKeys = array_keys($pages);
    $pageLabel = $pages[$pageId]['#title'];
    $pageNumber = array_search($pageId, $pageKeys) + 1;

    $sectionId = $webformMainElement['#webform_parents'][1];
    $sectionLabel = $elements[$sectionId]['#title'];
    $sectionWeight = array_search($sectionId, $elementKeys);

    $page = [
      'id' => $pageId,
      'label' => $pageLabel,
      'number' => $pageNumber,
    ];

    $section = [
      'id' => $sectionId,
      'label' => $sectionLabel,
      'weight' => $sectionWeight,
    ];

    return [
      $page,
      $section,
    ];
  }

}
