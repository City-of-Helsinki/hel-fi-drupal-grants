import {Page, test} from '@playwright/test';
import {
  FormData, FormPage,
  PageHandlers,
} from '../../utils/data/test_data';
import {fakerFI as faker} from '@faker-js/faker'
import {
  fillGrantsFormPage, fillSelectField,
  hideSlidePopup,
  fillHakijanTiedotRegisteredCommunity, fillInputField, uploadFile, fillFormField
} from '../../utils/form_helpers';

import {
  registeredCommunityApplications as applicationData
} from '../../utils/data/application_data';
import {selectRole} from '../../utils/auth_helpers';
import {getObjectFromEnv, slowLocator} from '../../utils/helpers';
import {validateSubmission} from '../../utils/validation_helpers';
import {deleteDraftApplication} from "../../utils/deletion_helpers";
import {copyApplication} from "../../utils/copying_helpers";

const profileType = 'registered_community';
const formId = '64';

const formPages: PageHandlers = {
  '1_hakijan_tiedot': async (page: Page, {items}: FormPage) => {
    // First page is always same, so use function to fill this.
    await fillHakijanTiedotRegisteredCommunity(items, page);
  },
  '2_avustustiedot': async (page: Page, {items}: FormPage) => {

    if (items['edit-acting-year']) {
      // await fillSelectField(items['edit-acting-year'].selector, page, '');
      await page.locator('#edit-acting-year').selectOption(items['edit-acting-year'].value ?? '');
    }

    if (items['edit-subventions-items-0-amount']) {
      await page.locator('#edit-subventions-items-0-amount')
        .fill(items['edit-subventions-items-0-amount'].value ?? '');
    }

    if (items['edit-purpose']) {
      await page.getByRole('textbox', {name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista'})
        .fill(items['edit-purpose'].value ?? '');
    }

    if (items['edit-benefits-loans']) {
      await page.getByLabel('Kuvaus lainoista ja takauksista', {exact: true})
        .fill(items['edit-benefits-loans'].value ?? '');
    }

    if (items['edit-benefits-premises']) {
      await page.getByLabel('Kuvaus tiloihin liittyvästä tuesta', {exact: true})
        .fill(items['edit-benefits-premises'].value ?? '');
    }
  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {

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

    if (items['edit-additional-information']) {
      await page.getByRole('textbox', {name: 'Lisätiedot'})
        .fill(items['edit-additional-information'].value ?? '');
    }

    if (items['edit-extra-info']) {
      await page.getByLabel('Lisäselvitys liitteistä')
        .fill(items['edit-extra-info'].value ?? '');
    }

    if (items['edit-muu-liite']) {
      await fillFormField(page, items['edit-muu-liite'], 'edit-muu-liite')
    }

  },
  'webform_preview': async (page: Page, {items}: FormPage) => {
    // Check data on confirmation page
    await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
  },
};

test.describe('ASUKASPIEN(64)', () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage()
    await selectRole(page, 'REGISTERED_COMMUNITY');
  });

  const testDataArray: [string, FormData][] = Object.entries(applicationData[formId]);

  for (const [key, obj] of testDataArray) {
    test(`Form: ${obj.title}`, async () => {
      await fillGrantsFormPage(
        key,
        page,
        obj,
        obj.formPath,
        obj.formSelector,
        formId,
        profileType,
        formPages
      );
    });
  }

  for (const [key, obj] of testDataArray) {
    if (!obj.testFormCopying) continue;
    test(`Copy form: ${obj.title}`, async () => {
      const storedata = getObjectFromEnv(profileType, formId);
      await copyApplication(
        key,
        profileType,
        formId,
        page,
        obj,
        storedata
      );
    });
  }

  for (const [key, obj] of testDataArray) {
    if (obj.viewPageSkipValidation || obj.testFormCopying) continue;
    test(`Validate: ${obj.title}`, async () => {
      const storedata = getObjectFromEnv(profileType, formId);
      await validateSubmission(
        key,
        page,
        obj,
        storedata
      );
    });
  }

  for (const [key, obj] of testDataArray) {
    test(`Delete drafts: ${obj.title}`, async () => {
      const storedata = getObjectFromEnv(profileType, formId);
      await deleteDraftApplication(
        key,
        page,
        obj,
        storedata
      );
    });
  }

});
