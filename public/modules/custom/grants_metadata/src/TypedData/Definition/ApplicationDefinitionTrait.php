<?php

namespace Drupal\grants_metadata\TypedData\Definition;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\grants_applicant_info\TypedData\Definition\ApplicantInfoDefinition;

/**
 * Base class for data typing & mapping.
 */
trait ApplicationDefinitionTrait {

  /**
   * Base data definitions for all.
   */
  public function getBaseProperties(): array {

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');
    $applicantType = $grantsProfileService->getApplicantType();

    $info['hakijan_tiedot'] = ApplicantInfoDefinition::create('applicant_info')
      ->setSetting('jsonPath', ['compensation', 'applicantOfficialsArray'])
      ->setSetting('defaultValue', [])
      ->setSetting('propertyStructureCallback', [
        'service' => 'grants_applicant_info.service',
        'method' => 'processApplicantInfo',
        'webform' => TRUE,
        'submittedData' => TRUE,
      ])
      ->setSetting('webformDataExtracter', [
        'service' => 'grants_applicant_info.service',
        'method' => 'extractDataForWebform',
      ]);

    $info['subventions'] = ListDataDefinition::create('grants_metadata_compensation_type')
      ->setSetting('jsonPath', [
        'compensation',
        'compensationInfo',
        'compensationArray',
      ])
      ->addConstraint('NotBlank')
      ->setRequired(TRUE)
      ->setSetting('formSettings', [
        'formElement' => 'subventions',
      ]);

    /*
     * Registered community
     */
    if ($applicantType === 'registered_community') {

      $info['email'] = DataDefinition::create('email')
        ->setSetting('defaultValue', '')
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

      $info['community_officials'] = ListDataDefinition::create('grants_profile_application_official')
        ->setSetting('jsonPath', ['compensation', 'applicantOfficialsArray'])
        ->setSetting('defaultValue', []);

      $info['contact_person'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'currentAddressInfoArray',
          'contactPerson',
        ]);

      $info['contact_person_phone_number'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'currentAddressInfoArray',
          'phoneNumber',
        ]);

      $info['community_street'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'currentAddressInfoArray',
          'street',
        ])
        ->setSetting('formSettings', [
          'formElement' => 'community_address',
          'formError' => 'You must select address',
        ]);

      $info['community_city'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'currentAddressInfoArray',
          'city',
        ])
        ->setSetting('formSettings', [
          'formElement' => 'community_address',
          'formError' => 'You must select address',
        ]);

      $info['community_post_code'] = DataDefinition::create('string')
        ->setSetting('defaultValue', '')
        ->setSetting('jsonPath', [
          'compensation',
          'currentAddressInfoArray',
          'postCode',
        ])
        ->setSetting('formSettings', [
          'formElement' => 'community_address',
          'formError' => 'You must select address',
        ])
        ->addConstraint('ValidPostalCode');

      $info['community_country'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'currentAddressInfoArray',
          'country',
        ])
        ->setSetting('formSettings', [
          'formElement' => 'community_address',
          'formError' => 'You must select address',
        ])
        ->setSetting('defaultValue', 'Suomi');
    }

    /*
     * Unregistered community.
     */
    if ($applicantType === 'unregistered_community') {
      $info['community_officials'] = ListDataDefinition::create('grants_profile_application_official')
        ->setSetting('jsonPath', ['compensation', 'applicantOfficialsArray'])
        ->setSetting('defaultValue', []);

      $info['account_number_owner_name'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'bankAccountArray',
          'accountOwnerName',
        ])
        ->addConstraint('NotBlank');

      $info['account_number_ssn'] = DataDefinition::create('string')
        ->setSetting('jsonPath', [
          'compensation',
          'bankAccountArray',
          'socialSecurityNumber',
        ])
        ->addConstraint('NotBlank');
    }

    $info['application_type'] = DataDefinition::create('string')
      ->setRequired(TRUE)
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'applicationType',
      ]);
    // ->addConstraint('NotBlank')
    $info['application_type_id'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'applicationTypeID',
      ]);

    $info['form_timestamp'] = DataDefinition::create('string')
      ->setRequired(TRUE)
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'formTimeStamp',
      ]);

    $info['form_timestamp_created'] = DataDefinition::create('string')
      ->setRequired(TRUE)
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'createdFormTimeStamp',
      ]);

    $info['form_timestamp_submitted'] = DataDefinition::create('string')
      ->setRequired(FALSE)
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'submittedFormTimeStamp',
      ]);

    $info['application_number'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'applicationNumber',
      ]);
    $info['status'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'status',
      ]);
    $info['acting_year'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'applicationInfoArray',
        'actingYear',
      ])
      ->addConstraint('NotBlank')
      ->setRequired(TRUE)
      ->setSetting('formSettings', [
        'formElement' => 'acting_year',
      ]);

    $info['account_number'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'bankAccountArray',
        'accountNumber',
      ])
      ->addConstraint('NotBlank');

    $info['myonnetty_avustus'] = ListDataDefinition::create('grants_metadata_other_compensation')
      ->setSetting('defaultValue', [])
      ->setSetting('jsonPath', [
        'compensation',
        'otherCompensationsInfo',
        'otherCompensationsArray',
      ])
      ->setSetting('requiredInJson', TRUE)
      ->setSetting('webformDataExtracter', [
        'service' => 'grants_metadata.atv_schema',
        'method' => 'returnRelations',
        'mergeResults' => TRUE,
        'arguments' => [
          'relations' => [
            'master' => 'myonnetty_avustus',
            'slave' => 'olemme_saaneet_muita_avustuksia',
            'type' => 'boolean',
          ],
        ],
      ]);

    $info['haettu_avustus_tieto'] = ListDataDefinition::create('grants_metadata_other_compensation')
      ->setSetting('defaultValue', [])
      ->setSetting('jsonPath', [
        'compensation',
        'otherCompensationsInfo',
        'otherAppliedCompensationsArray',
      ])
      ->setSetting('webformDataExtracter', [
        'service' => 'grants_metadata.atv_schema',
        'method' => 'returnRelations',
        'mergeResults' => TRUE,
        'arguments' => [
          'relations' => [
            'master' => 'haettu_avustus_tieto',
            'slave' => 'olemme_hakeneet_avustuksia_muualta_kuin_helsingin_kaupungilta',
            'type' => 'boolean',
          ],
        ],
      ]);

    $info['myonnetty_avustus_total'] = DataDefinition::create('float')
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'double',
      ])
      ->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToFloat',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'otherCompensationsInfo',
        'otherCompensationsInfoArray',
        'otherCompensationsTotal',
      ])
      ->addConstraint('NotBlank');

    $info['haettu_avustus_tieto_total'] = DataDefinition::create('float')
      ->setSetting('defaultValue', 0)
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'double',
      ])
      ->setSetting('jsonPath', [
        'compensation',
        'otherCompensationsInfo',
        'otherCompensationsInfoArray',
        'otherAppliedCompensationsTotal',
      ])
      ->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'convertToFloat',
      ])
      ->addConstraint('NotBlank');

    $info['benefits_loans'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'benefitsInfoArray',
        'loans',
      ]);

    $info['benefits_premises'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'compensation',
        'benefitsInfoArray',
        'premises',
      ]);

    $info['business_purpose'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'businessPurpose',
      ]);

    $info['community_practices_business'] = DataDefinition::create('string')
      ->setSetting('jsonPath', [
        'compensation',
        'activitiesInfoArray',
        'communityPracticesBusiness',
      ])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'bool',
      ]);

    $info['additional_information'] = DataDefinition::create('string')
      ->setSetting('jsonPath', ['compensation', 'additionalInformation'])
      ->setSetting('defaultValue', "");

    /*
     * Sender details are taken from HP login information.
     *
     * Also programmatic fields need Labels bc they are not taken from Webforms.
     * */

    $info['sender_firstname'] = DataDefinition::create('string')
      ->setRequired(TRUE)
      ->setSetting('jsonPath', [
        'compensation',
        'senderInfoArray',
        'firstname',
      ]);
    $info['sender_lastname'] = DataDefinition::create('string')
      ->setRequired(TRUE)
      ->setSetting('jsonPath', [
        'compensation',
        'senderInfoArray',
        'lastname',
      ]);

    $info['sender_person_id'] = DataDefinition::create('string')
      ->setRequired(TRUE)
      ->setSetting('jsonPath', [
        'compensation',
        'senderInfoArray',
        'personID',
      ]);
    $info['sender_user_id'] = DataDefinition::create('string')
      ->setRequired(TRUE)
      ->setSetting('jsonPath', ['compensation', 'senderInfoArray', 'userID']);
    $info['sender_email'] = DataDefinition::create('string')
      ->setRequired(TRUE)
      ->setSetting('jsonPath', ['compensation', 'senderInfoArray', 'email']);

    // Attachments.
    $info['attachments'] = ListDataDefinition::create('grants_metadata_attachment')
      ->setSetting('jsonPath', ['attachmentsInfo', 'attachmentsArray'])
      ->setSetting('hiddenFields', ['integrationID', 'fileType']);

    $info['extra_info'] = DataDefinition::create('string')
      ->setSetting('defaultValue', '')
      ->setSetting('jsonPath', [
        'attachmentsInfo',
        'generalInfoArray',
        'extraInfo',
      ]);

    $info['form_update'] = DataDefinition::create('boolean')
      ->setRequired(TRUE)
      ->setSetting('jsonPath', ['formUpdate'])
      ->setSetting('typeOverride', [
        'dataType' => 'string',
        'jsonType' => 'bool',
      ])
      ->setSetting('defaultValue', FALSE);

    $info['status_updates'] = MapDataDefinition::create()
      ->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'cleanUpArrayValues',
      ])
      ->setPropertyDefinition(
        'caseId',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['statusUpdates', 'caseId'])
      )
      ->setPropertyDefinition(
        'citizenCaseStatus',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['statusUpdates', 'citizenCaseStatus'])
      )
      ->setPropertyDefinition(
        'eventType',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['statusUpdates', 'eventType'])
      )
      ->setPropertyDefinition(
        'eventCode',
        DataDefinition::create('integer')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['statusUpdates', 'eventCode'])
      )
      ->setPropertyDefinition(
        'eventSource',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['statusUpdates', 'eventSource'])
      )
      ->setPropertyDefinition(
        'timeUpdated',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['statusUpdates', 'timeUpdated'])
      )
      ->setSetting('jsonPath', ['statusUpdates'])
      ->setRequired(FALSE);

    $info['events'] = MapDataDefinition::create()
      ->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'cleanUpArrayValues',
      ])
      ->setPropertyDefinition(
        'caseId',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['events', 'caseId'])
      )
      ->setPropertyDefinition(
        'eventType',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['events', 'eventType'])
      )
      ->setPropertyDefinition(
        'eventCode',
        DataDefinition::create('integer')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['events', 'eventCode'])
      )
      ->setPropertyDefinition(
        'eventSource',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['events', 'eventSource'])
      )
      ->setPropertyDefinition(
        'timeUpdated',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['events', 'timeUpdated'])
      )
      ->setSetting('jsonPath', ['events'])
      ->setRequired(FALSE);

    $info['messages'] = MapDataDefinition::create()
      ->setSetting('valueCallback', [
        '\Drupal\grants_handler\Plugin\WebformHandler\GrantsHandler',
        'cleanUpArrayValues',
      ])
      ->setPropertyDefinition(
        'caseId',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['messages', 'caseId'])
      )
      ->setPropertyDefinition(
        'messageId',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['messages', 'messageId'])
      )
      ->setPropertyDefinition(
        'body',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['messages', 'body'])
      )
      ->setPropertyDefinition(
        'sentBy',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['messages', 'sentBy'])
      )
      ->setPropertyDefinition(
        'sendDateTime',
        DataDefinition::create('string')
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['messages', 'sendDateTime'])
      )
      ->setPropertyDefinition(
        'attachments',
        MapDataDefinition::create()
          ->setPropertyDefinition('description',
            DataDefinition::create('string')
              ->setRequired(FALSE)
              ->setSetting('jsonPath', ['description'])
          )
          ->setPropertyDefinition('fileName',
            DataDefinition::create('string')
              ->setRequired(FALSE)
              ->setSetting('jsonPath', ['fileName'])
          )
          ->setRequired(FALSE)
          ->setSetting('jsonPath', ['messages', 'attachments'])
      )
      ->setSetting('jsonPath', ['messages'])
      ->setRequired(FALSE);

    return $info;
  }

}
