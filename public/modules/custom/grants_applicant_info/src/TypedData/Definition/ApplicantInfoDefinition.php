<?php

namespace Drupal\grants_applicant_info\TypedData\Definition;

use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Define Application official data.
 */
class ApplicantInfoDefinition extends ComplexDataDefinitionBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(): array {

    if (!isset($this->propertyDefinitions)) {
      /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
      // @phpstan-ignore-next-line
      $grantsProfileService = \Drupal::service('grants_profile.service');
      $applicantType = $grantsProfileService->getApplicantType();

      $info = &$this->propertyDefinitions;

      $info['applicantType'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'applicantInfoArray',
          'applicantType',
        ]);

      if ($applicantType == 'private_person') {
        $info['firstname'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'applicantInfoArray',
            'firstname',
          ]);
        $info['lastname'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'applicantInfoArray',
            'lastname',
          ]);
        $info['socialSecurityNumber'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'applicantInfoArray',
            'socialSecurityNumber',
          ]);

        $info['street'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'currentAddressInfoArray',
            'street',
          ]);
        $info['city'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'currentAddressInfoArray',
            'city',
          ]);
        $info['postCode'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'currentAddressInfoArray',
            'postCode',
          ]);
        $info['country'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'currentAddressInfoArray',
            'country',
          ]);
      }

      if ($applicantType == 'registered_community') {
        $info['companyNumber'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'applicantInfoArray',
            'companyNumber',
          ]);
        $info['registrationDate'] = DataDefinition::create('datetime_iso8601')
          ->setSetting('jsonPath', [
            'compensation',
            'applicantInfoArray',
            'registrationDate',
          ]);

        $info['foundingYear'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'applicantInfoArray',
            'foundingYear',
          ]);
        $info['home'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'applicantInfoArray',
            'home',
          ]);

        $info['homePage'] = DataDefinition::create('string')
          ->setSetting('jsonPath', [
            'compensation',
            'applicantInfoArray',
            'homePage',
          ])
          ->setSetting('defaultValue', "");
      }

      if ($applicantType == 'private_person') {
        $info['email'] = DataDefinition::create('email')
          ->setSetting('jsonPath', [
            'compensation',
            'applicantInfoArray',
            'email',
          ])
          ->setSetting('typeOverride', [
            'dataType' => 'email',
            'jsonType' => 'string',
          ])
          ->addConstraint('Email');
      }

      $info['applicantType'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'applicantInfoArray',
          'applicantType',
        ])
        ->addConstraint('NotBlank');

      $info['communityOfficialName'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'applicantInfoArray',
          'communityOfficialName',
        ]);

      $info['communityOfficialNameShort'] = DataDefinition::create('string')
        // ->setRequired(TRUE)
        ->setSetting('jsonPath', [
          'compensation',
          'applicantInfoArray',
          'communityOfficialNameShort',
        ]);

    }
    return $this->propertyDefinitions;
  }

}
