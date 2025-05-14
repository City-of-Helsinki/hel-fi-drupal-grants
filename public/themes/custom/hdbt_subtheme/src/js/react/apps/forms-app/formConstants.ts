import { UiSchema } from '@rjsf/utils';
import { JSONSchema7Definition, JSONSchema7TypeName } from 'json-schema';

const objectType: JSONSchema7TypeName = 'object';
const stringType: JSONSchema7TypeName = 'string';

export const privatePersonSettings: [JSONSchema7Definition, JSONSchema7Definition, UiSchema] = [
  {
    title: 'Omat yhteystiedot',
    type: objectType,
    $ref: '#/definitions/applicant_info',
  },
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
              address_name: {
                title: 'Katuosoite',
                type: stringType,
                minLength: 1,
              },
              postal_code: {
                title: 'Postinumero',
                type: stringType,
                minLength: 5,
              },
              post_area: {
                title: 'Toimipaikka',
                type: stringType,
                minLength: 1
              },
            },
          },
        },
      },
      applicant_phone: {
        title: 'Puhelinnumero',
        type: objectType,
        properties: {
          phone: {
            title: 'Henkilökohtainen puhelinnumero',
            type: stringType,
            minLength: 1,
          }
        }
      }
    },
  },
  {}
];

export const communitySettings: [JSONSchema7Definition, JSONSchema7Definition, UiSchema] = [
  {
    title: 'Yhteisö, jolle haetaan avustusta',
    type: objectType,
    '$ref': '#/definitions/applicant_info',
  },
  {
    type: objectType,
    properties: {
      applicant_email: {
        type: objectType,
        properties: {
          email: {
            type: stringType,
            format: 'email'
          }
        },
        required: ['email']
      },
      contact_person_info: {
        type: objectType,
        properties: {
          contact_person: {
            minLength: 1,
            type: stringType,
          },
          contact_person_phone_number: {
            minLength: 1,
            type: stringType,
          }
        },
      },
      community_address: {
        type: objectType,
        properties: {
          community_address: {
            type: stringType,
          }
        },
        required: ['community_address']
      },
      bank_account: {
        type: objectType,
        properties: {
          bank_account: {
            type: stringType,
          }
        },
        required: ['bank_account']
      },
      community_officials: {
        type: objectType,
        properties: {
          community_officials: {
            type: 'array',
            items: [
              {
                type: objectType,
                properties: {
                  official: {
                    type: stringType,
                  },
                },
              },
            ],
            additionalItems: {
              title: 'Valitse toiminnasta vastaavat henkilöt',
              type: objectType,
              properties: {
                official: {
                  title: 'Valitse vastaava henkilö',
                  type: stringType,
                },
              },
            },
          },
        },
      },
    },
    required: ['applicant_email', 'contact_person_info', 'bank_account', 'community_address'],
  },
  {
    applicant_info: {
      bank_account: {
        bank_account: {
          'ui:widget': 'bank_account'
        }
      },
      community_address: {
        community_address: {
          'ui:widget': 'address'
        }
      },
      community_officials: {
        community_officials: {
          additionalItems: {
            official: {
              'ui:widget': 'community_officials',
            },
            'ui:options': {
              removeText: 'root_applicant_info_community_officials_community_officials.removeText',
            }
          },
          items: {
            official: {
              'ui:widget': 'community_officials',
            },
          },
          'ui:options': {
            addable: true,
            orderable: false,
            removable: true,
          }
        }
      }
    },
  }
];
