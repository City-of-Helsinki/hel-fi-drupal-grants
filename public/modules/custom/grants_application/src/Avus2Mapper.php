<?php

namespace Drupal\grants_application;


use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_application\User\GrantsProfile;

/**
 * Access control handler for form submission.
 */
final class Avus2Mapper {

  public function __construct() {
  }

  /**
   * Map the form data to Avus2 format.
   *
   * @param array $form_data
   *   Data from the frontend.
   * @param array $user_data
   *   Userdata from user service.
   * @param array $company_data
   *   Selected company data from user service.
   * @param array $user_profile_data
   *   User profile data from user service.
   *
   * @return array
   *   Avus2 mapped array with data.
   */
  public function mapApplicationData(
    array $form_data,
    array $user_data,
    array $company_data,
    array $user_profile_data,
    GrantsProfile $grants_profile,
    FormSettings $form_settings
  ): array {

    $applicant_type = $company_data['type'];
    $data = [];
    $data['applicantInfoArray'] = $this->getApplicantData(
      $applicant_type,
      $user_data,
      $company_data,
      $user_profile_data,
      $form_data,
      $grants_profile,
    );

    // Check LiikuntaSuunnistusDefinition for these values.
    $data['compensationInfo']['compensationArray'] = [
      ['ID' => 'subventionType', 'value' => '15', 'valueType' => 'string', 'label' => null],
      ['ID' => 'amount', 'value' => '0' ,'valueType' => 'float', 'label' => null],
    ];

    $data['applicantOfficialsArray'] = $this->getApplicantOfficials($form_data, $grants_profile);
    $data['currentAddressInfoArray'] = $this->getCurrentAddressData($form_data, $grants_profile);

    // Applicationinfoarray/status is super special field.
    $data['applicationInfoArray'] = $this->getApplicationData($form_settings, $form_data);

    $data['bankAccountArray'][] = $this->getBankData($form_data);

    $data['otherCompensationInfo'] = $this->getOtherCompensation($form_data);

    $data['benefitsInfoArray'] = $this->getBenefits();

    $data['activitiesInfoArray'][] = $this->getActivities($grants_profile);

    $data['additionalInformation'] = $form_data['attachments']['additional_information_section']['additional_information'];

    $data['senderInfoArray'] = $this->getSenderInfo($user_data, $user_profile_data['myProfile']['verifiedPersonalInformation']);

    $data['orienteeringMapInfo']['orienteeringMapsArray'] = $this->getOrienteeringMaps($form_data);

    // $this->fillSharedData();
    // $this->fillApplicationSpecificData(&$data);

    return $data;
  }

  /**
   * Map the user data to Avus2 -format.
   *
   * Original implementation UserInformationService::getApplicantInformation.
   *
   * @return array
   *   The applicant data.
   */
  public function getApplicantData(
    string $applicant_type,
    array $userdata,
    array $company_data,
    array $user_profile_data,
    array $form_data,
    GrantsProfile $grants_profile,
  ): array {
    // @todo Suuunnistushakemus doesn't support applicant type 1 or 3.

    $user_data = match($applicant_type) {
      'unregistered_community' => $this->getType1($userdata),
      'registered_community' => $this->getType2($userdata, $company_data, $user_profile_data, $form_data, $grants_profile),
      'private_person' => $this->getType3($userdata),
    };

    return $user_data;
  }

  public function getCompensationData($form_data): array {
    return [];
  }

  /**
   * Get applicant officials.
   *
   * @param array $form_data
   *   The form data.
   * @param Drupal\grants_application\User\GrantsProfile $grants_profile
   *   The grants profile.
   *
   * @return array
   *   The applicant officials
   */
  public function getApplicantOfficials(array $form_data, GrantsProfile $grants_profile): array {
    $data = [];
    $uuid = $form_data['applicant_info']['community_officials']['community_officials'][0]['official'];

    $official_data = $grants_profile->getCommunityOfficialByUuid($uuid);
    $fields = ['name','role','email','phone'];

    foreach ($fields as $field_name) {
      $row = match($field_name) {
        'name' => ['ID' => $field_name, 'value' => $official_data[$field_name], 'valueType' => 'string', 'label' => 'nimi'],
        'role' => ['ID' => $field_name, 'value' => $official_data[$field_name], 'valueType' => 'int', 'label' => 'Rooli'],
        'email' => ['ID' => $field_name, 'value' => $official_data[$field_name], 'valueType' => 'string', 'label' => 'Sähköposti'],
        'phone' => ['ID' => $field_name, 'value' => $official_data[$field_name], 'valueType' => 'string', 'label' => 'Puhelinnumero'],
      };
      $data[] = $row;
    }

    return $data;
  }

  public function getCurrentAddressData($form_data, GrantsProfile $grants_profile): array {
    $data = [];
    $fields = ['contactPerson','phoneNumber','street','city','postCode','country'];

    $address_array = $grants_profile->getAddressByStreetname($form_data['applicant_info']['community_address']['community_address']);
    $email = $form_data['applicant_info']['applicant_email']['email'];
    $phone = $form_data['applicant_info']['contact_person']['contact_person_phone_number'];

    // It can be null in the grants profile addresses.
    foreach ($fields as $field_name) {
      $row = match($field_name) {
        'contactPerson' => ['ID' => $field_name, 'value' => $email, 'valueType' => 'string', 'label' => 'Yhteyshenkilö'],
        'phoneNumber' => ['ID' => $field_name, 'value' => $phone, 'valueType' => 'string', 'label' => 'Puhelinnumero'],
        'street' => array('ID' => $field_name, 'value' => $address_array[$field_name], 'valueType' => 'string', 'label' => 'Lähiosoite'),
        'city' => array('ID' => $field_name, 'value' => $address_array[$field_name], 'valueType' => 'string', 'label' => 'Kaupunki'),
        'postCode' => array('ID' => $field_name, 'value' => $address_array[$field_name], 'valueType' => 'string', 'label' => 'Postinumero'),

        // @todo Check where that country is actually set in original code
        'country' => array('ID' => $field_name, 'value' => $address_array[$field_name] ?? 'Suomi', 'valueType' => 'string', 'label' => 'Maa'),
      };

      $data[] = $row;
    }

    return $data;
  }

  public function getApplicationData(FormSettings $form_settings, $form_data): array {
    $application_type = $form_settings->toArray()['settings']['application_type'];
    $acting_year = $form_data['orienteering_maps']['acting_year']['acting_year'];

    // @todo Draft is a super special case, don't know how.
    return [
      ['ID' => 'applicationType', 'value' => $application_type, 'valueType' => 'string', 'label' => NULL],
      ['ID' => 'status', 'value' => 'DRAFT', 'valueType' => 'string', 'label' => 'Hakemuksen tila'],
      ['ID' => 'actingYear', 'value' => $acting_year, 'valueType' => 'string', 'label' => 'Vuosi, jolle haen avustusta'],
    ];
  }

  /**
   * Get bank account data.
   *
   * @param array $form_data
   *   The form data.
   *
   * @return array
   *   The bank account data.
   */
  public function getBankData(array $form_data): array {
    return [
      'ID' => 'accountNumber',
      'value' => $form_data['applicant_info']['bank_account']['bank_account'],
      'valueType' => 'string',
      'label' => 'Tilinumero',
    ];
  }

  /**
   * Get other compensation.
   *
   * @param array $form_data
   * @return array[]
   */
  public function getOtherCompensation(array $form_data): array {
    return [
      'otherCompensationsArray' => [],
      'otherCompensationsInfoArray' => [
        ['ID' => 'otherCompensationsTotal', 'value' => 0, 'valueType' => 'double', 'label' => NULL],
        ['ID' => 'otherAppliedCompensationsTotal', 'value' => 0, 'valueType' => 'double', 'label' => NULL],
      ],
    ];
  }

  public function getBenefits(): array {
    return [
      ['ID' => 'loans', 'value' => '','valueType' => 'string', 'label' => NULL],
      ['ID' => 'premises', 'value' => '','valueType' => 'string', 'label' => NULL],
    ];
  }

  /**
   *
   *
   * @param GrantsProfile $grants_profile
   *   The grants profile.
   *
   * @return array
   *   The activities array.
   */
  public function getActivities(GrantsProfile $grants_profile): array {
    return [
      'ID' => 'businessPurpose',
      'value' => $grants_profile->getBusinessPurpose(),
      'valueType' => 'string',
      'label' => NULL
    ];
  }

  public function getSenderInfo(array $user_data, array $verified_personal_information): array {
    // Avus2key => information array key.
    $fields = [
      'firstname' => 'firstName',
      'lastname' => 'lastName',
      'personID' => 'nationalIdentificationNumber',
      'userID' => 'sub',
      'email' => 'email',
    ];

    $data = [];
    foreach ($fields as $field_name => $user_data_field_name) {
      $row = match($field_name) {
        'firstname', 'lastname', 'personID' => ['ID' => $field_name, 'value' => $verified_personal_information[$user_data_field_name], "valueType" => "string", 'label' => NULL],
        'userID', 'email' => ['ID' => $field_name, 'value' =>$user_data[$user_data_field_name], "valueType" => "string", 'label' => NULL ],
      };
      $data[] = $row;
    }

    return $data;
  }

  /**
   * Get orienteering maps.
   *
   * @param $form_data
   *   The form data.
   *
   * @return array
   *   The mapped map data.
   */
  public function getOrienteeringMaps($form_data): array {
    $fields = ['mapName', 'size', 'voluntaryHours', 'cost', 'otherCompensations'];
    $maps = $form_data['orienteering_maps']['orienteering_subvention']['orienteering_maps'];

    $data = [];
    foreach($maps as $map) {
      $mapData = [];
      foreach ($fields as $field_name) {
        $row = match($field_name) {
          'mapName' => ['ID' => $field_name, 'value' => $map[$field_name], 'valueType' => 'string', 'label' => 'Kartan nimi, sijainti ja karttatyyppi'],
          'size' => ['ID' => $field_name, 'value' => $map[$field_name], 'valueType' => 'double', 'label' => 'Koko km2'],
          'voluntaryHours' => ['ID' => $field_name, 'value' => $map[$field_name], 'valueType' => 'float', 'label' => 'Talkootyö tuntia'],
          'cost' => ['ID' => $field_name, 'value' => $map[$field_name], 'valueType' => 'double', 'label' => 'Kustannukset euroa'],
          'otherCompensations' => ['ID' => $field_name, 'value' => $map[$field_name], 'valueType' => 'double', 'label' => 'Muilta saadut avustukset euroa'],
        };
        $mapData[] = $row;
      }
      $data[] = $mapData;
    }

    return $data;
  }


  private function getType1($userdata): array {
    // unregistered_community ???
    $fields = [
      'applicantType',
      'communityOfficialName',
      'firstname',
      'lastname',
      'socialSecurityNumber',
      'email',
      'street',
      'city',
      'postCode',
      'country',
    ];

    return [];
  }

  /**
   * Get registered community data.
   *
   * @param array $user_data
   *   The user data.
   *
   * @return array
   *   Avus2 -mapped user data.
   */
  private function getType2(
    array $user_data,
    array $company_data,
    array $user_profile_data,
    array $form_data,
    GrantsProfile $grants_profile,
  ): array {
    $fields = [
      'applicantType',
      'companyNumber',
      'registrationDate', // Tää on muodossa d.m.Y !.
      'foundingYear',
      'home',
      'homePage',
      'communityOfficialName',
      'communityOfficialNameShort',
      'email',
    ];

    $data = [];
    // Handle the exceptions.
    foreach ($fields as $field_name) {
      $row = match($field_name) {
        default =>
          ['ID' => $field_name, 'value' => NULL, 'valueType' => 'string', 'label' => NULL],
        'registrationDate' =>
          ['ID' => $field_name, 'value' => NULL, 'valueType' => 'datetime', 'label' => NULL],
        'email' =>
          ['ID' => $field_name, 'value' => NULL, 'valueType' => 'string', 'label' => 'Sähköpostiosoite'],
      };

      // Handle the values field by field.
      match($field_name) {
        // 'applicantType' => $row['value'] = $company_data['type'],
        'applicantType' => $row['value'] = 2,
        'companyNumber' => $row['value'] = $company_data['identifier'],
        'registrationDate' => $row['value'] = $grants_profile->getRegistrationDate(TRUE),
        'foundingYear' => $row['value'] = $grants_profile->getFoundingYear(),
        'home' => $row['value'] = $grants_profile->getHome(),
        'homePage' => $row['value'] = $grants_profile->getHomePage(),
        'communityOfficialName' => $row['value'] = $grants_profile->getCompanyName(),
        'communityOfficialNameShort' => $row['value'] = $grants_profile->getCompanyShortName(),
        'email' => $row['value'] = $form_data['applicant_info']['applicant_email']['email'],
      };

      $data[] = $row;
    }

    return $data;
  }

  private function getType3($userdata): array {
    // Private person ??
    $fields = [
      'applicantType',
      'firstname',
      'lastname',
      'socialSecurityNumber',
      'email',
      'street',
      'city',
      'postCode',
      'country',
    ];

    return [];
  }

}
