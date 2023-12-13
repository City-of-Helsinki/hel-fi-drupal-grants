import {faker} from '@faker-js/faker';
import {Locator, Page, expect, test} from '@playwright/test';
import {
  selectRole,
  setupUnregisteredCommunity,
  slowLocator
} from '../../utils/helpers';
import {checkContactInfoPrivatePerson} from '../../utils/profile_helpers';
import {
  fillForm,
  FormData
} from '../../utils/form_helpers'

test.describe('Private Person', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    page.locator = slowLocator(page, 500);
    await selectRole(page, 'PRIVATE_PERSON');
  });

  test('Test oma asiointi', async () => {
    console.log('Oma asiointi')
    await page.goto("/fi/oma-asiointi");
  });

  test('Test oma hakuprofiili', async () => {
    console.log('Hakuprofiili');
    await page.goto("/fi/oma-asiointi/hakuprofiili");
    await checkContactInfoPrivatePerson(page);
  });

  test('Test edit hakuprofiili', async () => {
    console.log('Hakuprofiili edit');
    await page.goto("/fi/oma-asiointi/hakuprofiili/muokkaa");

    const streetAddress = faker.location.streetAddress()
    const postCode = faker.location.zipCode();
    // const postCode = "asdfasdfasdfasdf";

    let formFields: FormData = {
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

    };

    await fillForm(page, formFields);
  });


})
