import {fakerFI as faker} from '@faker-js/faker';
import {bankAccountConfirmationPath} from "../helpers";
import {
    FormData, FormDataWithRemoveOptionalProps,
} from "./test_data";
import {createFormData} from "../form_helpers";


const profileDataBase: FormData =  {
    title: 'Profiledata: Successful',
    formSelector: 'grants-profile-unregistered-community',
    formPath: '/fi/oma-asiointi/hakuprofiili/muokkaa',
    formPages: {
        'onlyone': {
            items: {
                'companyname': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-companynamewrapper-companyname',
                    },
                    value: faker.company.name(),
                },
                'address-street': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-addresswrapper-0-address-street',
                    },
                    value: faker.location.streetAddress(),
                },
                'address-postcode': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-addresswrapper-0-address-postcode',
                    },
                    value: faker.location.zipCode(),
                },
                'address-city': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-addresswrapper-0-address-city',
                    },
                    value: 'Helsinki',
                },
                'bankaccount': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-bankaccountwrapper-0-bank-bankaccount',
                    },
                    value: 'FI1165467882414711',
                },
                'bankconfirm': {
                    role: 'fileupload',
                    selector: {
                        type: 'locator',
                        name: 'data-drupal-selector',
                        value: '[name="files[bankAccountWrapper_0_bank_confirmationFile]"]',
                        resultValue: '.form-item-bankaccountwrapper-0-bank-confirmationfile a',
                    },
                    value: bankAccountConfirmationPath,
                },
                'official_name': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-officialwrapper-0-official-name',
                    },
                    value: faker.person.fullName(),
                },
                'official-role': {
                    role: 'select',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-officialwrapper-0-official-role',
                    },
                    value: '11',
                },
                'official-email': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-officialwrapper-0-official-email',
                    },
                    value: faker.internet.email(),
                },
                'official-phone': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-officialwrapper-0-official-phone',
                    },
                    value: faker.phone.number(),
                },
                'button-submit': {
                    role: 'button',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-actions-submit',
                    }
                },
            }
        }
    },
    expectedDestination: "/fi/oma-asiointi/hakuprofiili",
    expectedErrors: {
        // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
    }
};

const missingValues: FormDataWithRemoveOptionalProps = {
    title: 'Missing values',
    formPages: {
        'onlypage': {
            items: {},
            itemsToRemove: ['bankaccounts'],
        },
    },
    expectedDestination: '',
    expectedErrors: {
        'bankaccounts': 'Virhe sivulla 1. Hakijan tiedot: Valitse tilinumero kenttä on pakollinen.'
    },
};

const profileDataUnregisteredCommunity = {
    success: profileDataBase,
    // missingValues: createFormData(profileDataBase, missingValues)
}

export {
    profileDataUnregisteredCommunity
}
