import type { UiSchema } from '@rjsf/utils';
import type { JSONSchema7Definition, JSONSchema7TypeName } from 'json-schema';

const objectType: JSONSchema7TypeName = 'object';
const stringType: JSONSchema7TypeName = 'string';

export const privatePersonSettings: [JSONSchema7Definition, JSONSchema7Definition, UiSchema] = [
  { title: 'Omat yhteystiedot', type: objectType, $ref: '#/definitions/applicant_info' },
  {
    type: objectType,
    properties: {
      applicant_address_data: {
        title: 'Osoite',
        type: objectType,
        properties: {
          address: {
            title: 'Henkilökohtainen osoite',
            type: objectType,
            properties: {
              address_name: { title: 'Katuosoite', type: stringType, minLength: 1 },
              postal_code: { title: 'Postinumero', type: stringType, minLength: 5 },
              post_area: { title: 'Toimipaikka', type: stringType, minLength: 1 },
            },
          },
        },
      },
      applicant_phone: {
        title: 'Puhelinnumero',
        type: objectType,
        properties: { phone: { title: 'Henkilökohtainen puhelinnumero', type: stringType, minLength: 1 } },
      },
    },
  },
  {},
];

export const communitySettings: [JSONSchema7Definition, JSONSchema7Definition, UiSchema] = [
  { title: 'applicant_info.title', type: objectType, $ref: '#/definitions/applicant_info' },
  {
    type: objectType,
    properties: {
      applicant_email: {
        description: 'applicant_email.description',
        properties: { email: { format: 'email', title: 'applicant_email_email.title', type: stringType } },
        required: ['email'],
        title: 'applicant_email.title',
        type: objectType,
      },
      contact_person_info: {
        properties: {
          contact_person: { minLength: 1, title: 'contact_person.title', type: stringType },
          contact_person_phone_number: { minLength: 1, title: 'contact_person_phone_number.title', type: stringType },
        },
        required: ['contact_person', 'contact_person_phone_number'],
        title: 'contact_person_info.title',
        type: objectType,
      },
      community_address: {
        properties: { community_address: { title: 'community_address_community_address.title', type: stringType } },
        required: ['community_address'],
        title: 'community_address.title',
        type: objectType,
      },
      bank_account: {
        properties: { bank_account: { title: 'bank_account_bank_account.title', type: stringType } },
        required: ['bank_account'],
        title: 'bank_account.title',
        type: objectType,
      },
      community_officials: {
        properties: {
          community_officials: {
            additionalItems: {
              title: 'community_officials_community_officials.title',
              type: objectType,
              properties: {
                official: { title: 'community_officials_community_officials_official.title', type: stringType },
              },
            },
            items: [
              {
                properties: {
                  official: { title: 'community_officials_community_officials_official.title', type: stringType },
                },
                title: 'community_officials_community_officials.title',
                type: objectType,
              },
            ],
            title: 'community_officials_community_officials.title',
            type: 'array',
            minItems: 1,
          },
        },
        title: 'community_officials.title',
        type: objectType,
      },
    },
    required: ['applicant_email', 'bank_account', 'community_address', 'contact_person_info'],
  },
  {
    applicant_info: {
      bank_account: {
        bank_account: { 'ui:widget': 'bank_account', 'ui:options': { printableName: 'bank_account.title' } },
      },
      community_address: {
        community_address: { 'ui:widget': 'address', 'ui:options': { printableName: 'community_address.title' } },
      },
      community_officials: {
        community_officials: {
          additionalItems: {
            official: { 'ui:widget': 'community_officials' },
            'ui:options': { removeText: 'community_officials_community_officials.removeText' },
          },
          items: { official: { 'ui:widget': 'community_officials', 'ui:options': { hideNameFromPrint: true } } },
          'ui:options': { addable: true, hideNameFromPrint: true, orderable: false, removable: true },
        },
      },
    },
  },
];
