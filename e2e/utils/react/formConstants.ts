type JSONSchema7TypeName =
  | "string"
  | "number"
  | "integer"
  | "boolean"
  | "object"
  | "array"
  | "null";

const objectType: JSONSchema7TypeName = 'object';
const stringType: JSONSchema7TypeName = 'string';
const booleanType: JSONSchema7TypeName = 'boolean';

/**
 * Local schema for the final confirmation and submit step.
 *
 * The API response does not include this step, so we define it
 * here and inject it into the schema before the tests run.
 *
 * The tuple holds three things in order:
 *   1. The root property entry added to schema.properties.
 *   2. The field definitions added to schema.definitions.
 *   3. The UI schema additions merged into ui_schema.
 */
export const confirmAndSubmitSettings: [any, any, any] = [
  // Root property (referenced from schema.properties.confirm_and_submit).
  { title: 'confirm_and_submit.title', type: objectType, $ref: '#/definitions/confirm_and_submit' },
  // Definition.
  {
    type: objectType,
    properties: {
      terms_section: {
        title: 'terms_section.title',
        type: objectType,
        properties: {
          final_acceptance: { title: 'final_acceptance.title', type: booleanType },
        },
        required: ['final_acceptance'],
      },
    },
  },
  // UI schema additions.
  {
    confirm_and_submit: {},
  },
];

/**
 * Local schema for the applicant info step.
 *
 * The server response does not include this step, so we define it
 * here and inject it into the schema before the tests run.
 *
 * The tuple holds three things in order:
 *   1. The root property entry added to schema.properties.
 *   2. The field definitions (email, contact person, address,
 *      bank account, and community officials).
 *   3. The UI schema additions merged into ui_schema.
 */
export const communitySettings: [any, any, any] = [
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
          contact_person: { minLength: 1, title: 'contact_person_info_contact_person.title', type: stringType },
          contact_person_phone_number: { minLength: 1, title: 'contact_person_info_contact_person_phone_number.title', type: stringType },
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
      applicant_email: {
        email: {
          'ui:options': {
            hideNameFromPrint: true,
          },
        },
      },
      contact_person_info: {
        contact_person: {
          'ui:options': {
            hideNameFromPrint: true,
          },
        },
        contact_person_phone_number: {
          'misc:phone': true,
        },
      },
      bank_account: {
        bank_account: {
          'ui:widget': 'bank_account',
          'ui:options': {
            hideNameFromPrint: true,
            tooltipLabel: 'bank_account.title',
            tooltipButtonLabel: 'bank_account.title',
            tooltipText: 'bank_account.tooltip',
          },
        },
      },
      community_address: {
        community_address: {
          'ui:widget': 'address',
          'ui:options': {
            hideNameFromPrint: true,
            tooltipLabel: 'community_address.title',
            tooltipButtonLabel: 'community_address.title',
            tooltipText: 'community_address.tooltip',
          },
        },
      },
      community_officials: {
        community_officials: {
          'ui:options': {
            hideNameFromPrint: true,
          },
          additionalItems: {
            official: {
              'ui:widget': 'community_officials',
              'ui:options': {
                hideNameFromPrint: true,
              },
            },
            'ui:options': {
              hideNameFromPrint: true,
              removeText: 'community_officials_community_officials.removeText',
            },
          },
          items: {
            official: {
              'ui:options': {
                hideNameFromPrint: true,
              },
              'ui:widget': 'community_officials',
            },
            'ui:options': {
              addable: true,
              hideNameFromPrint: true,
              orderable: false,
              removable: true,
              tooltipLabel: 'community_officials.title',
              tooltipButtonLabel: 'community_officials.title',
              tooltipText: 'community_officials.tooltip',
            },
          },
        },
      },
    },
  },
];
