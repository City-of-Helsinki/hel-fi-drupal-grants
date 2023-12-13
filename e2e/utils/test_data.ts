import { fakerFI as faker } from '@faker-js/faker';

const TEST_IBAN = "FI31 4737 2044 0000 48"
const TEST_SSN = "090797-999P"
const TEST_USER_UUID = "13cb60ae-269a-46da-9a43-da94b980c067"

interface MultiValueField {
    buttonSelector: string,
    items: Array<FormField>,
    expectedErrors?: Object
}

interface FormField {
    label?: string
    role: string;
    selectorType: string;
    selector: string;
    value?: string | Array<MultiValueField>;
}

interface FormData {
    formPages: Array<Array<FormField>>,
    expectedDestination: string,
    expectedErrors: Object
}

interface ProfileData {
    success: FormData
}

//     // Address
//     await page.getByRole('button', { name: 'Lisää osoite' }).click();
//     await page.getByLabel('Katuosoite').fill('Testiosoite 123');
//     await page.getByLabel('Postinumero').fill('00100');
//     await page.getByLabel('Toimipaikka').fill('Helsinki');

//     // Bank account
//     await page.getByRole('button', { name: 'Lisää pankkitili' }).click();
//     await page.getByLabel('Suomalainen tilinumero IBAN-muodossa').fill(TEST_IBAN);
//     await uploadBankConfirmationFile(page, 'input[type="file"]')

const streetAddress = faker.location.streetAddress()
const postCode = faker.location.zipCode();

const profileData = {
    success: {
        formPages: [
            [
                {
                    role: 'input',
                    selectorType: 'data-drupal-selector',
                    selector: 'edit-addresswrapper-0-address-street',
                    value: streetAddress,
                },
                {
                    role: 'input',
                    selectorType: 'data-drupal-selector',
                    selector: 'edit-addresswrapper-0-address-postcode',
                    value: postCode,
                },
                {
                    role: 'input',
                    selectorType: 'data-drupal-selector',
                    selector: 'edit-addresswrapper-0-address-city',
                    value: 'Helsinki',
                },
                {
                    role: 'input',
                    selectorType: 'data-drupal-selector',
                    selector: 'edit-phonewrapper-phone-number',
                    value: faker.phone.number(),
                },
                {
                    role: 'multivalue',
                    selectorType: 'data-drupal-selector',
                    selector: 'officialwrapper',
                    value: [
                        {
                            buttonSelector: 'Lisää osoite',
                            items: [
                                {
                                    role: 'input',
                                    selectorType: 'data-drupal-selector',
                                    selector: 'yhteyshenkilönimi',
                                    value: faker.personName(),
                                },
                                {
                                    role: 'input',
                                    selectorType: 'data-drupal-selector',
                                    selector: 'yhteyshenkilöemail',
                                    value: faker.email(),
                                },
                                {
                                    role: 'select',
                                    selectorType: 'data-drupal-selector',
                                    selector: 'yhteyshenkilörooli',
                                    value: 1,
                                },
                                {
                                    role: 'input',
                                    selectorType: 'data-drupal-selector',
                                    selector: 'yhteyshenkilöpuhelin',
                                    value: faker.phoneNumber(),
                                },
                            ]
                        }
                    ],
                },
                {
                    role: 'multivalue',
                    selectorType: 'data-drupal-selector',
                    selector: 'bankaccount',
                    value: [
                        {
                            buttonSelector: 'Lisää pankkitili',
                            items: [
                                {
                                    role: 'input',
                                    selectorType: 'data-drupal-selector',
                                    selector: 'pankkitili-iban',
                                    value: faker.bankaccount.iban(),
                                },
                                {
                                    role: 'fileupload',
                                    selectorType: 'data-drupal-selector',
                                    selector: 'yhteyshenkilöemail',
                                    value: faker.email(),
                                },
                            ]
                        }
                    ],
                },
                {
                    role: 'button',
                    selectorType: 'data-drupal-selector',
                    selector: 'edit-actions-submit'
                }
            ]
        ],
        expectedDestination: "/fi/oma-asiointi/hakuprofiili",
        expectedErrors: {
            // "edit-addresswrapper-0-address-postcode": `${postCode} ei ole suomalainen postinumero`
        },
    }
};

const applicationData = {}

export {
    TEST_IBAN,
    TEST_SSN,
    TEST_USER_UUID,
    profileData,
    applicationData
}
