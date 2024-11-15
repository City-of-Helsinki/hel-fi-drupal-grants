<?php

namespace Drupal\grants_react_form;

/**
 * Class to map react data fields to.
 */
class ReactDataMapper {

  /**
   * Add a value to given multidimensional array.
   *
   * @param string $id
   *   The field id to map to avustus2 document.
   * @param array $value
   *   The value to add to the array.
   * @param array $data_array
   *   The avus2 document.
   */
  public function mapReactFieldToAvusDocument(string $id, array $value, array &$data_array): void {
    $path = $this->getJsonPath($id);
    $this->arraySet($data_array, $path, $value);
  }

  /**
   * Return hardcoded path or path to questions array.
   *
   * Every field everywhere are hardcoded except customQuestionsArray items,
   * and that's why we should just hardcode everything.
   * If field is not mapped, it is highly likely custom question.
   *
   * @param string $id
   *   The id of the field.
   *
   * @return string[]
   *   Correct place to write the data.
   */
  private function getJsonPath(string $id): array {
    $fields = [
      'hardcoded_field_id' => ['hardcoded', 'path', 'goes', 'here'],
      'orienteering_maps' => ['compensation', 'orienteeringMapInfo'],
    ];

    return $fields[$id] ?? ['compensation', 'customQuestionsInfo', 'customQuestionsArray'];
  }

  /**
   * Set the value in correct place in multidimensional data array.
   *
   * @param array $data_arr
   *   The array to alter.
   * @param array $path
   *   The path you want to set the value to.
   * @param array $data
   *   The actual value.
   */
  private function arraySet(array &$data_arr, array $path, array $data): void {
    $current = &$data_arr;
    foreach ($path as $key) {
      $current = &$current[$key];
    }

    $current[] = $data;
  }

  /**
   * Return an array that should be acceepted by avus2.
   *
   * In theory, this could contain all the data arrays of all the forms.
   * This could also be filled dynamically based on the fields sent from react.
   *
   * @return array
   *   The data array to be filled.
   */
  public function getEmptyDataArray(): array {
    return [
      'compensation' => [
        'activitiesInfoArray' => [],
        'additionalInformation' => '',
        'applicantInfoArray' => [],
        'applicantOfficialsArray' => [],
        'applicationInfoArray' => [],
        'bankAccountArray' => [],
        'benefitsInfoArray' => [],
        'budgetInfo' => [
          'costGroupsArrayStatic' => [],
          'incomeGroupsArrayStatic' => [],
        ],
        'compensationInfo' => [
          'compensationArray' => [],
          'generalInfoArray' => [],
        ],
        'currentAddressInfoArray' => [],
        'customQuestionsInfo' => [
          'customQuestionsArray' => [],
        ],
        'otherCompensationsInfo' => [],
        'senderInfoArray' => [],
      ],
      'attachmentsInfo' => [],
    ];

   // CustomQuestion pitää sisällään dynaamista dataa -
   // ei toimi jollain yhdellä hakemuksella tjs.
  }

}
