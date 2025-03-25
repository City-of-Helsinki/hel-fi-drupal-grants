import { RJSFValidationError } from '@rjsf/utils';

export const testErrors: RJSFValidationError[] = [
  {
    message: 'Error 1',
    schemaPath: 'step-1',
    property: '.step-1.random-string',
    stack: '',
  },
  {
    message: 'Error 2',
    schemaPath: 'step-2',
    property: '.step-2.abstract-string',
    stack: '',
  }
];

export const testKeyedErrors: Array<[number, RJSFValidationError]> = [
  [0, testErrors[0]],
  [1, testErrors[1]],
];

export const testSteps = new Map([
  [0, {
    id: 'step-1',
    label: 'Step 1',
  }],
  [1, {
    id: 'step-2',
    label: 'Step 2',
  }],
]);

export const testEmptySchema = {
  type: 'object',
  properties: {},
  definitions: {},
};

export const emptyUiSchema = {};

export const testGrantsProfile = {
  companyNameShort: 'ABC Inc.',
  companyName: 'ABC Incorporated',
  companyHome: 'USA',
  companyHomePage: 'https://www.abcinc.com',
  companyStatus: 'Active',
  companyStatusSpecial: '',
  businessPurpose: 'Software Development',
  foundingYear: '2010',
  registrationDate: '2010-01-01',
  officials: [
    { name: 'John Doe', title: 'CEO' },
    { name: 'Jane Smith', title: 'CTO' }
  ],
  addresses: [
    {
      address_id: 'addr-1',
      street: '123 Main St',
      postCode: '12345',
      city: 'New York',
      country: 'USA'
    },
    {
      address_id: 'addr-2',
      street: '456 Elm St',
      postCode: '67890',
      city: 'Los Angeles',
      country: 'USA'
    }
  ],
  bankAccounts: [
    {
      bankAccount: '1234567890',
      confirmationFile: 'file.pdf',
      bank_account_id: 'ba-1',
      ownerName: 'John Doe',
      ownerSsn: '123-45-6789'
    },
    {
      bankAccount: '9876543210',
      confirmationFile: 'file2.pdf',
      bank_account_id: 'ba-2'
    }
  ],
  businessId: 'BUS-12345'
};

export const testResponseData = {
  grants_profile: testGrantsProfile,
  schema: testEmptySchema,
  translations: {},
  ui_schema: emptyUiSchema,
};
