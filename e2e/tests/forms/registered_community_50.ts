import { Page, test } from '@playwright/test';
import {FormData, PageHandlers, FormPage} from "../../utils/data/test_data";
import { fillHakijanTiedotRegisteredCommunity } from '../../utils/form_helpers';
import { fillFormField, fillInputField, uploadFile } from '../../utils/input_helpers';
import { Role, selectRole } from '../../utils/auth_helpers';
import { generateTests } from '../../utils/test_generator_helpers';
import {registeredCommunityApplications as applicationData} from '../../utils/data/application_data';

const formPages: PageHandlers = {
  '1_hakijan_tiedot' : async (page: Page, {items}: FormPage) => {
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
    if (items['edit-ensisijainen-taiteen-ala']) {
      await fillFormField(page, items['edit-ensisijainen-taiteen-ala'], 'edit-ensisijainen-taiteen-ala');
    }
    if (items['edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti']) {
      await page.locator('#edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti')
        .fill(items['edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti'].value);
    }
    if (items['edit-myonnetty-avustus']) {
      await fillFormField(page, items['edit-myonnetty-avustus'], 'edit-myonnetty-avustus')
    }
  },
  '3_yhteison_tiedot': async (page: Page, {items}: FormPage) => {
    if (items['edit-members-applicant-person-global']) {
      await page.locator('#edit-members-applicant-person-global')
        .fill(items['edit-members-applicant-person-global'].value);
    }
    if (items['edit-members-applicant-person-local']) {
      await page.locator('#edit-members-applicant-person-local')
        .fill(items['edit-members-applicant-person-local'].value);
    }
    if (items['edit-members-applicant-community-global']) {
      await page.locator('#edit-members-applicant-community-global')
        .fill(items['edit-members-applicant-community-global'].value);
    }
    if (items['edit-members-applicant-community-local']) {
      await page.locator('#edit-members-applicant-community-local')
        .fill(items['edit-members-applicant-community-local'].value);
    }
    if (items['edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p']) {
      await fillFormField(page, items['edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p'], 'edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p');
    }
  },
  '4_toteutunut_toiminta': async (page: Page, {items}: FormPage) => {
    const {
      'edit-helsingissa-jarjestettava-tila': premiseField,
      nextbutton,
      ...rest
    } = items;

    if (items['edit-helsingissa-jarjestettava-tila']) {
      await fillFormField(page, items['edit-helsingissa-jarjestettava-tila'], 'edit-helsingissa-jarjestettava-tila');
    }

    for (const [itemKey, item] of Object.entries(rest)) {
      await fillInputField(
        item.value.toString(),
        item.selector ?? {
          type: 'data-drupal-selector',
          name: 'data-drupal-selector',
          value: itemKey,
        },
        page,
        itemKey,
        true,
        true,
      );
    }
  },
  '5_toiminnan_lahtokohdat': async (page: Page, {items}: FormPage) => {
    for (const [itemKey, item] of Object.entries(items)) {
      await fillInputField(
        item.value,
        item.selector ?? {
          type: 'data-drupal-selector',
          name: 'data-drupal-selector',
          value: itemKey,
        },
        page,
        itemKey,
        true,
      );
    }
  },
  '6_talous': async (page: Page, {items}: FormPage) => {
    const {
      'edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-1': vos_now,
      'edit-organisaatio-kuului-valtionosuusjarjestelmaan-vos-1': vos_past,
      nextbutton,
      ...rest
    } = items;

    if (items['edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-']) {
      await page.locator('#edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-')
        .getByText(items['edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-'].value ?? '').click();
    }

    if (items['edit-organisaatio-kuului-valtionosuusjarjestelmaan-vos-']) {
      await page.locator('#edit-organisaatio-kuului-valtionosuusjarjestelmaan-vos-')
        .getByText(items['edit-organisaatio-kuului-valtionosuusjarjestelmaan-vos-'].value ?? '').click();
    }

    for (const [itemKey, item] of Object.entries(rest)) {
      await fillInputField(
        item.value,
        item.selector ?? {
          type: 'data-drupal-selector',
          name: 'data-drupal-selector',
          value: itemKey,
        },
        page,
        itemKey,
        true,
        true,
      );
    }
  },
  'lisatiedot_ja_liitteet': async (page: Page, {items}: FormPage) => {
    if (items['edit-additional-information']) {
      await page.getByRole('textbox', {name: 'Lisätiedot'})
        .fill(items['edit-additional-information'].value ?? '');
    }
    if (items['edit-yhteison-saannot-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-yhteison-saannot-attachment-upload'].selector?.value ?? '',
        items['edit-yhteison-saannot-attachment-upload'].selector?.resultValue ?? '',
        items['edit-yhteison-saannot-attachment-upload'].value
      )
    }
    if (items['edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload'].selector?.value ?? '',
        items['edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload'].selector?.resultValue ?? '',
        items['edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload'].value
      )
    }
    if (items['edit-vahvistettu-toimintakertomus-edelliselta-paattyneelta-tilikaudel-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-vahvistettu-toimintakertomus-edelliselta-paattyneelta-tilikaudel-attachment-upload'].selector?.value ?? '',
        items['edit-vahvistettu-toimintakertomus-edelliselta-paattyneelta-tilikaudel-attachment-upload'].selector?.resultValue ?? '',
        items['edit-vahvistettu-toimintakertomus-edelliselta-paattyneelta-tilikaudel-attachment-upload'].value
      )
    }
    if (items['edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-edelliselta-paa-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-edelliselta-paa-attachment-upload'].selector?.value ?? '',
        items['edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-edelliselta-paa-attachment-upload'].selector?.resultValue ?? '',
        items['edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-edelliselta-paa-attachment-upload'].value
      )
    }
    if (items['edit-toimintasuunnitelma-sille-vuodelle-jolle-haet-avustusta-monivuot-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-toimintasuunnitelma-sille-vuodelle-jolle-haet-avustusta-monivuot-attachment-upload'].selector?.value ?? '',
        items['edit-toimintasuunnitelma-sille-vuodelle-jolle-haet-avustusta-monivuot-attachment-upload'].selector?.resultValue ?? '',
        items['edit-toimintasuunnitelma-sille-vuodelle-jolle-haet-avustusta-monivuot-attachment-upload'].value
      )
    }
    if (items['edit-talousarvio-sille-vuodelle-jolle-haet-avustusta-monivuotisissa-k-attachment-upload']) {
      await uploadFile(
        page,
        items['edit-talousarvio-sille-vuodelle-jolle-haet-avustusta-monivuotisissa-k-attachment-upload'].selector?.value ?? '',
        items['edit-talousarvio-sille-vuodelle-jolle-haet-avustusta-monivuotisissa-k-attachment-upload'].selector?.resultValue ?? '',
        items['edit-talousarvio-sille-vuodelle-jolle-haet-avustusta-monivuotisissa-k-attachment-upload'].value
      )
    }
    if (items['edit-muu-liite']) {
      await fillFormField(page, items['edit-muu-liite'], 'edit-muu-liite')
    }
    if (items['edit-extra-info']) {
      await page.getByLabel('Lisäselvitys liitteistä')
        .fill(items['edit-extra-info'].value ?? '');
    }
  },
  'webform_preview': async (page: Page, {items}: FormPage) => {
    // Check data on confirmation page
    await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
  },
};

test.describe('KUVAPERUS(50)', () => {
  let page: Page;

  const profileType = 'registered_community';
  const formId = '50';

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage();
    await selectRole(page, profileType.toUpperCase() as Role);
  });

  test.afterAll(async() => {
    await page.close();
  })

  const testDataArray: [string, FormData][] = Object.entries(applicationData[formId]);
  const tests = generateTests(profileType, formId, formPages, testDataArray);

  for (const { testName, testFunction } of tests) {
    test(testName, async ({ browser }) => {
      await testFunction(page, browser);
    });
  }
});
