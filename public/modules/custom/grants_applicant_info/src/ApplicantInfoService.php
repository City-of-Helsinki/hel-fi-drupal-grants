<?php

namespace Drupal\grants_applicant_info;

use Drupal\grants_applicant_info\TypedData\Definition\ApplicantInfoDefinition;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_profile\GrantsProfileService;

/**
 * HAndle applicant info service.
 */
class ApplicantInfoService {

  const PRIVATE_PERSON = '0';
  const REGISTERED_COMMUNITY = '2';
  const UNREGISTERED_COMMUNITY = '1';

  /**
   * Access to grants profile data.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Construct the service object.
   *
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Grants profile access.
   */
  public function __construct(GrantsProfileService $grantsProfileService) {
    $this->grantsProfileService = $grantsProfileService;
  }

  /**
   * Since this is full property provider, we need to return full json array.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $property
   *   Property to process.
   *
   * @return array
   *   PArsed values.
   */
  public function processApplicantInfo(ComplexDataInterface $property) {

    $retval = [];
    $dataDefinition = $property->getDataDefinition();
    $usedFields = $dataDefinition->getSetting('fieldsForApplication');

    $applicantType = '';

    foreach ($property as $itemIndex => $p) {
      $pDef = $p->getDataDefinition();
      $pJsonPath = $pDef->getSetting('jsonPath');
      $defaultValue = $pDef->getSetting('defaultValue');
      $valueCallback = $pDef->getSetting('valueCallback');
      $temp = $pJsonPath;
      $elementName = array_pop($temp);

      $itemTypes = AtvSchema::getJsonTypeForDataType($pDef);
      $itemValue = AtvSchema::getItemValue($itemTypes, $p->getValue(), $defaultValue, $valueCallback);

      if ($elementName == 'applicantType') {
        // If value is empty, make sure we get proper applicant type.
        if (empty($itemValue)) {
          $applicantType = $this->grantsProfileService->getApplicantType();
        }
        else {
          $applicantType = $itemValue;
        }

        if ($applicantType == 'private_person') {
          $itemValue = self::PRIVATE_PERSON;
        }
        elseif ($applicantType == 'unregistered_community') {
          $itemValue = self::UNREGISTERED_COMMUNITY;
        }
        else {
          $itemValue = self::REGISTERED_COMMUNITY;
        }
      }

      $pValue = [
        'ID' => $elementName,
        'value' => $itemValue,
        'valueType' => $itemTypes['jsonType'],
        'label' => $pDef->getLabel(),
      ];

      self::setNestedValue($retval, $temp, $pValue);
    }

    if ($applicantType == 'registered_community') {
      // Hack NOT to set address things here and set them via normal address UI.
      unset($retval["compensation"]["currentAddressInfoArray"]);
      self::removeItemById($retval, 'email');
      self::removeItemById($retval, 'firstname');
      self::removeItemById($retval, 'lastname');
      self::removeItemById($retval, 'socialSecurityNumber');

    }
    if ($applicantType == 'unregistered_community') {
      // Hack NOT to set address things here and set them via normal address UI.
      unset($retval["compensation"]["currentAddressInfoArray"]);
      self::removeItemById($retval, 'email');
      self::removeItemById($retval, 'firstname');
      self::removeItemById($retval, 'lastname');
      self::removeItemById($retval, 'socialSecurityNumber');
      self::removeItemById($retval, 'companyNumber');
      self::removeItemById($retval, 'registrationDate');
      self::removeItemById($retval, 'foundingYear');
      self::removeItemById($retval, 'home');
      self::removeItemById($retval, 'homePage');
      self::removeItemById($retval, 'communityOfficialNameShort');
    }
    if ($applicantType == 'private_person') {
      self::removeItemById($retval, 'companyNumber');
      self::removeItemById($retval, 'communityOfficialName');
      self::removeItemById($retval, 'communityOfficialNameShort');
      self::removeItemById($retval, 'registrationDate');
      self::removeItemById($retval, 'foundingYear');
      self::removeItemById($retval, 'home');
      self::removeItemById($retval, 'homePage');
    }

    if (is_array($retval["compensation"]["applicantInfoArray"])) {
      $retval["compensation"]["applicantInfoArray"] = array_values($retval["compensation"]["applicantInfoArray"]);
    }

    if (isset($retval["compensation"]["currentAddressInfoArray"]) && is_array($retval["compensation"]["currentAddressInfoArray"])) {
      $retval["compensation"]["currentAddressInfoArray"] = array_values($retval["compensation"]["currentAddressInfoArray"]);
    }

    return $retval;
  }

  /**
   * Remove item.
   *
   * @param array $data
   *   DAta.
   * @param string $itemID
   *   Item id.
   */
  public static function removeItemById(array &$data, $itemID): void {
    $path = [];
    foreach ($data as $key => $value) {
      $numerickeys = array_filter(array_keys($value), 'is_int');
      if (empty($numerickeys)) {
        foreach ($value as $key2 => $value2) {
          $numerickeys2 = array_filter(array_keys($value2), 'is_int');
          if (!empty($numerickeys2)) {
            foreach ($value2 as $key3 => $item) {
              if ($item['ID'] == $itemID) {
                $path[] = $key;
                $path[] = $key2;
                $path[] = $key3;
              }
            }
          }
        }
      }
    }
    if (!empty($path)) {
      NestedArray::unsetValue($data, $path);
    }
  }

  /**
   * Extact data.
   *
   * @param \Drupal\grants_applicant_info\TypedData\Definition\ApplicantInfoDefinition $property
   *   Property.
   * @param array $content
   *   Doc content.
   *
   * @return array
   *   VAlues
   */
  public function extractDataForWebform(ApplicantInfoDefinition $property, array $content): array {
    $keys = [
      'applicantType',
      'companyNumber',
      'communityOfficialName',
      'communityOfficialNameShort',
      'registrationDate',
      'foundingYear',
      'home',
      'homePage',
      'registrationDate',
      'socialSecurityNumber',
      'firstname',
      'lastname',
      'registrationDate',
    ];
    $values = [];
    foreach ($content['compensation'] as $key => $item) {
      if (is_numeric($key)) {
        if (in_array($item['ID'], $keys) && !in_array($item['ID'], $values)) {
          $values[$item['ID']] = $item['value'];
        }
      }
      else {
        if (!is_array($item)) {
          $values[$key] = $item;
          continue;
        }
        foreach ($item as $key2 => $item2) {
          if (!is_array($item2)) {
            $values[$key2] = $item2;
          }
          elseif (AtvSchema::numericKeys($item2)) {
            foreach ($item2 as $item3) {
              if (AtvSchema::numericKeys($item3)) {
                foreach ($item3 as $item4) {
                  if (in_array($item4['ID'], $keys) && !array_key_exists($item4['ID'], $values)) {
                    $values[$item4['ID']] = $item4['value'];
                  }
                }
              }
              else {
                if (in_array($item3['ID'], $keys) && !array_key_exists($item3['ID'], $values)) {
                  $values[$item3['ID']] = $item3['value'];
                }
              }
            }
          }
          else {
            if (is_numeric($key2)) {
              if (in_array($item2['ID'], $keys) && !in_array($item2['ID'], $values)) {
                $values[$item2['ID']] = $item2['value'];
              }
            }
          }
        }
      }
    }
    if ($values['applicantType'] == self::REGISTERED_COMMUNITY) {
      $values['applicantType'] = 'registered_community';
      $values['applicant_type'] = 'registered_community';
    }
    if ($values['applicantType'] == self::UNREGISTERED_COMMUNITY) {
      $values['applicantType'] = 'unregistered_community';
      $values['applicant_type'] = 'unregistered_community';
    }
    if ($values['applicantType'] == self::PRIVATE_PERSON) {
      $values['applicantType'] = 'private_person';
      $values['applicant_type'] = 'private_person';
    }
    return $values;

  }

  /**
   * Sets a value in a nested array with variable depth.
   *
   * This helper function should be used when the depth of the array element you
   * are changing may vary (that is, the number of parent keys is variable). It
   * is primarily used for form structures and renderable arrays.
   *
   * @param array $array
   *   A reference to the array to modify.
   * @param array $parents
   *   An array of parent keys, starting with the outermost key.
   * @param mixed $value
   *   The value to set.
   * @param bool $force
   *   (optional) If TRUE, the value is forced into the structure even if it
   *   requires the deletion of an already existing non-array parent value. If
   *   FALSE, PHP throws an error if trying to add into a value that is not an
   *   array. Defaults to FALSE.
   *
   * @see NestedArray::unsetValue()
   * @see NestedArray::getValue()
   */
  public static function setNestedValue(array &$array, array $parents, $value, $force = FALSE) {
    $ref = &$array;
    foreach ($parents as $parent) {
      // PHP auto-creates container arrays and NULL entries without error if
      // is NULL, but throws an error if $ref is set, but not an array.
      if ($force && isset($ref) && !is_array($ref)) {
        $ref = [];
      }
      $ref = &$ref[$parent];
    }
    $ref[] = $value;
  }

}
