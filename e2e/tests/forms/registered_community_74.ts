import {Page, test} from '@playwright/test';
import {FormData, PageHandlers, FormPage} from "../../utils/data/test_data";
import {fillHakijanTiedotRegisteredCommunity} from "../../utils/form_helpers";
import {fillFormField, fillInputField} from "../../utils/input_helpers";
import {generateTests} from "../../utils/test_generator_helpers";
import {Role, selectRole} from "../../utils/auth_helpers";
import {registeredCommunityApplications as applicationData} from '../../utils/data/application_data';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    await fillHakijanTiedotRegisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-acting-year']) {
      await fillFormField(page, items['edit-acting-year'], 'edit-acting-year');
    }

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    if (items['edit-compensation-purpose']) {
      await page.getByRole('textbox', {name: 'Mihin avustus käytetään?'})
        .fill(items['edit-compensation-purpose'].value ?? '');
    }

    if (items['edit-myonnetty-avustus']) {
      await fillFormField(page, items['edit-myonnetty-avustus'], 'edit-myonnetty-avustus')
    }

    // if (items['edit-olemme-saaneet-muita-avustuksia-1']) {
    //   await page.locator('#edit-olemme-saaneet-muita-avustuksia-1')
    //     .getByText(items['edit-olemme-saaneet-muita-avustuksia-1'].value ?? '').click();
    // }
    //
    // if (items['edit-myonnetty-avustus-items-0-item-issuer']) {
    //   await page.locator('#edit-myonnetty-avustus-items-0-item-issuer')
    //     .selectOption({ label: items['edit-myonnetty-avustus-items-0-item-issuer'].value});
    // }
    //
    // if (items['edit-myonnetty-avustus-items-0-item-issuer-name']) {
    //   await page.locator('#edit-myonnetty-avustus-items-0-item-issuer-name')
    //     .fill(items['edit-myonnetty-avustus-items-0-item-issuer-name'].value ?? '');
    // }
    //
    // if (items['edit-myonnetty-avustus-items-0-item-year']) {
    //   await page.locator('#edit-myonnetty-avustus-items-0-item-year')
    //     .fill(items['edit-myonnetty-avustus-items-0-item-year'].value ?? '');
    // }
    //
    // if (items['edit-myonnetty-avustus-items-0-item-amount']) {
    //   await page.locator('#edit-myonnetty-avustus-items-0-item-amount')
    //     .fill(items['edit-myonnetty-avustus-items-0-item-amount'].value ?? '');
    // }
    //
    // if (items['edit-myonnetty-avustus-items-0-item-purpose']) {
    //   await page.locator('#edit-myonnetty-avustus-items-0-item-purpose')
    //     .fill(items['edit-myonnetty-avustus-items-0-item-purpose'].value ?? '');
    // }

    if (items['edit-benefits-loans']) {
      await page.locator('#edit-benefits-loans')
        .fill(items['edit-benefits-loans'].value ?? '');
    }

    if (items['edit-benefits-premises']) {
      await page.locator('#edit-benefits-premises')
        .fill(items['edit-benefits-premises'].value ?? '');
    }

    if (items['edit-compensation-boolean']) {
      await page.locator('#edit-compensation-boolean')
        .getByText(items['edit-compensation-boolean'].value ?? '').click();
    }

    if (items['edit-compensation-explanation']) {
      await page.locator('#edit-compensation-explanation')
        .fill(items['edit-compensation-explanation'].value ?? '');
    }
  },

  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-business-purpose']) {
      await page.locator('#edit-business-purpose')
        .fill(items['edit-business-purpose'].value ?? '');
    }

    if (items['edit-community-practices-business-1']) {
      await page.getByText(items['edit-community-practices-business-1'].value ?? '', {exact: true})
        .click();
    }

    if (items['edit-fee-person']) {
      await fillInputField(
        items['edit-fee-person'].value ?? '',
        items['edit-fee-person'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-fee-person',
        },
        page,
        'edit-fee-person'
      );
    }

    if (items['edit-fee-community']) {
      await fillInputField(
        items['edit-fee-community'].value ?? '',
        items['edit-fee-community'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-fee-community',
        },
        page,
        'edit-fee-community'
      );
    }

    if (items['edit-members-applicant-person-global']) {
      await fillInputField(
        items['edit-members-applicant-person-global'].value ?? '',
        items['edit-members-applicant-person-global'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-members-applicant-person-global',
        },
        page,
        'edit-members-applicant-person-global'
      );
    }

    if (items['edit-members-applicant-person-local']) {
      await fillInputField(
        items['edit-members-applicant-person-local'].value ?? '',
        items['edit-members-applicant-person-local'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-members-applicant-person-local',
        },
        page,
        'edit-members-applicant-person-local'
      );
    }

    if (items['edit-members-applicant-community-global']) {
      await fillInputField(
        items['edit-members-applicant-community-global'].value ?? '',
        items['edit-members-applicant-community-global'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-members-applicant-community-global',
        },
        page,
        'edit-members-applicant-community-global'
      );
    }

    if (items['edit-members-applicant-community-local']) {
      await fillInputField(
        items['edit-members-applicant-community-local'].value ?? '',
        items['edit-members-applicant-community-local'].selector ?? {
          type: 'data-drupal-selector-sequential',
          name: 'data-drupal-selector',
          value: 'edit-members-applicant-community-local',
        },
        page,
        'edit-members-applicant-community-local'
      );
    }
  },
  'lisatiedot_ja_liitteet': async (page: Page, {items}: FormPage) => {

    // Loop all items from page 4.
    for (const [itemKey, item] of Object.entries(items)) {
      await fillFormField(page, item, itemKey);
    }
  },
  'webform_preview': async (page: Page, {items}: FormPage) => {
    if (items['accept_terms_1']) {
      // Check data on confirmation page
      await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
    }
  },
};

test.describe('TYOLLISYYS(74)', () => {
  let page: Page;

  const profileType = 'registered_community';
  const formId = '74';

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage();
    await selectRole(page, profileType.toUpperCase() as Role);
  });

  test.afterAll(async() => {
    await page.close();
  });

  const testDataArray: [string, FormData][] = Object.entries(applicationData[formId]);
  const tests = generateTests(profileType, formId, formPages, testDataArray);

  for (const { testName, testFunction } of tests) {
    test(testName, async ({browser}) => {
      await testFunction(page, browser);
    });
  }
});
