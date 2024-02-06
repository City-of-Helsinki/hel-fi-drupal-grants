<?php

namespace Drupal\grants_applicant_info;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\grants_applicant_info\TypedData\Definition\ApplicantInfoDefinition;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;

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
   * The helfi_helsinki_profiili.userdata service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData;

  /**
   * Construct the service object.
   *
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Grants profile access.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData
   *   The helfi_helsinki_profiili.userdata service.
   */
  public function __construct(
    GrantsProfileService $grantsProfileService,
    HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
  ) {
    $this->grantsProfileService = $grantsProfileService;
    $this->helsinkiProfiiliUserData = $helsinkiProfiiliUserData;
  }

  /**
   * Changes to the Unregistered community profile.
   *
   * @param array $profile
   *   Profile array.
   * @param array $retval
   *   Profile's return array.
   *
   * @return void
   *   Return void.
   */
  private static function processUnregisteredProfile(array $profile, array &$retval) {
    if ($profile) {
      $addressPath = [
        'compensation',
        'currentAddressInfoArray',
      ];

      $responsibles = array_filter($profile["officials"], fn($item) => $item['role'] == '11');
      $responsible = reset($responsibles);

      $addressElement = [
        [
          'ID' => 'street',
          'value' => $profile["addresses"][0]["street"],
          'valueType' => 'string',
          'label' => 'Katuosoite',
        ],
        [
          'ID' => 'city',
          'value' => $profile["addresses"][0]["city"],
          'valueType' => 'string',
          'label' => 'Postitoimipaikka',
        ],
        [
          'ID' => 'postCode',
          'value' => $profile["addresses"][0]["postCode"],
          'valueType' => 'string',
          'label' => 'Postinumero',
        ],
        [
          'ID' => 'country',
          'value' => $profile["addresses"][0]["country"],
          'valueType' => 'string',
          'label' => 'Maa',
        ],
        // Add contact person from user data.
        [
          'ID' => 'contactPerson',
          'value' => $responsible["name"] ?? '',
          'valueType' => 'string',
          'label' => 'Yhteyshenkilö',
        ],
        // Add phone from user data.
        [
          'ID' => 'phoneNumber',
          'value' => $responsible["phone"] ?? '',
          'valueType' => 'string',
          'label' => 'Puhelinnumero',
        ],
      ];

      foreach ($addressElement as $ae) {
        self::setNestedValue($retval, $addressPath, $ae);
      }

      /*
       * Set email from user details. This must be set,
       * or applications do not work
       */
      self::setNestedValue(
        $retval,
        [
          'compensation',
          'applicantInfoArray',
        ],
        [
          'ID' => 'email',
          'value' => $responsible["email"],
          'valueType' => 'string',
          'label' => 'Sähköpostiosoite',
        ]);
    }
  }

  /**
   * Method to use when we need full applicant info data parsed to existing.
   *
   * Since this is full property provider, we need to return full json array.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $property
   *   Property to process.
   * @param array $arguments
   *   Arguments from schema handler.
   *
   * @return array
   *   Parsed values.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function processApplicantInfo(ComplexDataInterface $property, array $arguments): array {

    $retval = [];

    $applicantType = '';
    foreach ($property as $p) {
      $pDef = $p->getDataDefinition();
      $pJsonPath = $pDef->getSetting('jsonPath');
      $temp = $pJsonPath;
      $elementName = array_pop($temp);

      $defaultValue = $pDef->getSetting('defaultValue');
      $valueCallback = $pDef->getSetting('valueCallback');

      $itemTypes = AtvSchema::getJsonTypeForDataType($pDef);
      $itemValue = AtvSchema::getItemValue($itemTypes, $p->getValue(), $defaultValue, $valueCallback);
      if ($elementName == 'applicantType') {

        // If value is empty, make sure we get proper applicant type.
        $applicantType = $itemValue ?? $this->grantsProfileService->getApplicantType();

        switch ($applicantType) {
          case 'private_person':
            $itemValue = self::PRIVATE_PERSON;
            break;

          case 'unregistered_community':
            $itemValue = self::UNREGISTERED_COMMUNITY;
            break;

          case 'registered_community':
            $itemValue = self::REGISTERED_COMMUNITY;
            break;

          default:
            break;
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
      $this->adjustRegisteredCommunityApplicantType($retval);
    }
    if ($applicantType == 'unregistered_community') {
      $this->adjustUnregisteredCommunityApplicantType($retval);
    }
    if ($applicantType == 'private_person') {
      $this->adjustPrivatePersonApplicantType($retval);
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
   * Adjust Applicant Info based on Registered community type.
   *
   * @param array $retval
   *   Array of items for the form.
   *
   * @return void
   *   Returns void.
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   */
  private function adjustRegisteredCommunityApplicantType(array &$retval) {
    // Hack NOT to set address things here and set them via normal address UI.
    unset($retval["compensation"]["currentAddressInfoArray"]);
    self::removeItemById($retval, 'email');
    self::removeItemById($retval, 'firstname');
    self::removeItemById($retval, 'lastname');
    self::removeItemById($retval, 'socialSecurityNumber');
  }

  /**
   * Adjust Applicant Info based on Unregistered community type.
   *
   * @param array $retval
   *   Array of items for the form.
   *
   * @return void
   *   Returns void.
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   */
  private function adjustUnregisteredCommunityApplicantType(array &$retval) {
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
    /*
     * We need to bring address details from applicant info details, since
     * address information needs to be automatically filled.
     *
     * These also do not need to be parsed the other way, since these details
     * are inside the applicant info component
     */

    $roleId = $this->grantsProfileService->getSelectedRoleData();
    $profile = $this->grantsProfileService->getGrantsProfileContent($roleId);

    self::processUnregisteredProfile($profile, $retval);
  }

  /**
   * Adjust Applicant Info based on Private person type.
   *
   * @param array $retval
   *   Array of items for the form.
   *
   * @return void
   *   Returns void.
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   */
  private function adjustPrivatePersonApplicantType(array &$retval) {
    unset($retval["compensation"]["currentAddressInfoArray"]);
    self::removeItemById($retval, 'email');
    self::removeItemById($retval, 'companyNumber');
    self::removeItemById($retval, 'communityOfficialName');
    self::removeItemById($retval, 'communityOfficialNameShort');
    self::removeItemById($retval, 'registrationDate');
    self::removeItemById($retval, 'foundingYear');
    self::removeItemById($retval, 'home');
    self::removeItemById($retval, 'homePage');

    /*
     * We need to bring address details from applicant info details, since
     * address information needs to be automatically filled.
     *
     * These also do not need to be parsed the other way, since these details
     * are inside the applicant info component
     */

    $roleId = $this->grantsProfileService->getSelectedRoleData();
    $profile = $this->grantsProfileService->getGrantsProfileContent($roleId);
    $helsinkiProfile = $this->helsinkiProfiiliUserData->getUserData();

    if ($profile) {
      $addressPath = [
        'compensation',
        'currentAddressInfoArray',
      ];

      $addressElement = [
        [
          'ID' => 'street',
          'value' => $profile["addresses"][0]["street"],
          'valueType' => 'string',
          'label' => 'Katuosoite',
        ],
        [
          'ID' => 'city',
          'value' => $profile["addresses"][0]["city"],
          'valueType' => 'string',
          'label' => 'Postitoimipaikka',
        ],
        [
          'ID' => 'postCode',
          'value' => $profile["addresses"][0]["postCode"],
          'valueType' => 'string',
          'label' => 'Postinumero',
        ],
        [
          'ID' => 'country',
          'value' => $profile["addresses"][0]["country"],
          'valueType' => 'string',
          'label' => 'Postinumero',
        ],
        // Add contact person from user data.
        [
          'ID' => 'contactPerson',
          'value' => $helsinkiProfile["name"] ?? '',
          'valueType' => 'string',
          'label' => 'Yhteyshenkilö',
        ],
        // Add phone from user data.
        [
          'ID' => 'phoneNumber',
          'value' => $profile["phone_number"] ?? '',
          'valueType' => 'string',
          'label' => 'Puhelinnumero',
        ],
      ];

      foreach ($addressElement as $ae) {
        self::setNestedValue($retval, $addressPath, $ae);
      }

      /*
       * Set email from user details. This must be set,
       * or applications do not work
       */
      self::setNestedValue(
        $retval,
        [
          'compensation',
          'applicantInfoArray',
        ],
        [
          'ID' => 'email',
          'value' => $helsinkiProfile["email"],
          'valueType' => 'string',
          'label' => 'Sähköpostiosoite',
        ]);
    }

  }

  /**
   * Parse the data array and remove an item with a certain ID in it.
   *
   * @param array $data
   *   Data.
   * @param string $itemID
   *   Item id.
   */
  public static function removeItemById(array &$data, $itemID): void {
    $path = [];
    foreach ($data as $key => $value) {
      $numerickeys = array_filter(array_keys($value), 'is_int');
      if (empty($numerickeys)) {
        foreach ($value as $key2 => $value2) {
          $path = self::getPathFromInnerNumericArray(
            $value2,
            $itemID,
            [$key, $key2]
          );
        }
      }
    }
    if (!empty($path)) {
      NestedArray::unsetValue($data, $path);
    }
  }

  /**
   * See if the haystack contains the needle and return full path.
   *
   * @param array $haystack
   *   Haystack to search.
   * @param mixed $needle
   *   Search needle.
   * @param array $earlierPath
   *   Array that contains the earlier path to return.
   *
   * @return array
   *   Array that has the earlier path and the final key.
   */
  private static function getPathFromInnerNumericArray(array $haystack,
                                                       mixed $needle,
                                                       array $earlierPath) {
    $numerickeys = array_filter(array_keys($haystack), 'is_int');
    if (!empty($numerickeys)) {
      foreach ($haystack as $key3 => $item) {
        if ($item['ID'] == $needle) {
          $earlierPath[] = $key3;
          return $earlierPath;
        }
      }
    }
    return [];
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
   *   Values
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
      'email',
    ];

    $values = AtvSchema::extractDataForWebForm($content, $keys);

    if (($values['applicantType'] ?? '') == self::REGISTERED_COMMUNITY) {
      $values['applicantType'] = 'registered_community';
      $values['applicant_type'] = 'registered_community';
    }
    if (($values['applicantType'] ?? '') == self::UNREGISTERED_COMMUNITY) {
      $values['applicantType'] = 'unregistered_community';
      $values['applicant_type'] = 'unregistered_community';
    }
    if (($values['applicantType'] ?? '') == self::PRIVATE_PERSON) {
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
