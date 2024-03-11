import {fakerFI as faker} from '@faker-js/faker';
import {FormData, FormDataWithRemoveOptionalProps,} from "./test_data";
import {PROFILE_INPUT_DATA} from "./profile_input_data";
import {ATTACHMENTS} from "./attachment_data";

// @ts-ignore
const profileDataBase: FormData = {
    title: 'Successful',
    formSelector: 'grants-profile-private-person',
    formPath: '/fi/oma-asiointi/hakuprofiili/muokkaa',
    formPages: {
        'onlypage': {
            items: {
                'streetaddress': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-addresswrapper-0-address-street',
                    },
                    value: PROFILE_INPUT_DATA.address,
                },
                'postcode': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-addresswrapper-0-address-postcode',
                    },
                    value: PROFILE_INPUT_DATA.zipCode,
                },
                'city': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-addresswrapper-0-address-city',
                    },
                    value: PROFILE_INPUT_DATA.city,
                },
                'edit-phonewrapper-phone-number': {
                    role: 'input',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-phonewrapper-phone-number',
                    },
                    value: faker.phone.number(),
                },
                'bankaccounts': {
                    role: 'multivalue',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-bankaccountwrapper',
                    },
                    multi: {
                        buttonSelector: {
                            type: 'add-more-button',
                            name: 'data-drupal-selector',
                            value: 'Lisää pankkitili',
                            resultValue: 'edit-bankaccountwrapper-[INDEX]-bank',
                        },
                        // @ts-ignore
                        items: {
                            0: [
                                {
                                    role: 'input',
                                    selector: {
                                        type: 'data-drupal-selector',
                                        name: 'data-drupal-selector',
                                        value: 'edit-bankaccountwrapper-[INDEX]-bank-bankaccount',
                                    },
                                    value: PROFILE_INPUT_DATA.iban,
                                },
                                {
                                    role: 'fileupload',
                                    selector: {
                                        type: 'locator',
                                        name: 'data-drupal-selector',
                                        value: '[name="files[bankAccountWrapper_[INDEX]_bank_confirmationFile]"]',
                                        resultValue: '.form-item-bankaccountwrapper-[INDEX]-bank-confirmationfile a',
                                    },
                                    value: ATTACHMENTS.BANK_ACCOUNT_CONFIRMATION,
                                },
                            ],
                            1: [
                                {
                                    role: 'input',
                                    selector: {
                                        type: 'data-drupal-selector',
                                        name: 'data-drupal-selector',
                                        value: 'edit-bankaccountwrapper-[INDEX]-bank-bankaccount',
                                    },
                                    value: 'FI5777266988169614',
                                },
                                {
                                    role: 'fileupload',
                                    selector: {
                                        type: 'locator',
                                        name: 'data-drupal-selector',
                                        value: '[name="files[bankAccountWrapper_[INDEX]_bank_confirmationFile]"]',
                                        resultValue: '.form-item-bankaccountwrapper-[INDEX]-bank-confirmationfile a',
                                    },
                                    value: ATTACHMENTS.BANK_ACCOUNT_CONFIRMATION,
                                },
                            ]
                        },
                        expectedErrors: {}
                    },
                },
                'submit': {
                    role: 'button',
                    selector: {
                        type: 'data-drupal-selector',
                        name: 'data-drupal-selector',
                        value: 'edit-actions-submit',
                    }
                },
            },
        }
    },
    expectedDestination: "/fi/oma-asiointi/hakuprofiili",
    expectedErrors: {}
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

const profileDataPrivatePerson = {
    success: profileDataBase,
    // missingValues: createFormData(profileDataBase, missingValues)
}

export {
    profileDataPrivatePerson
}
