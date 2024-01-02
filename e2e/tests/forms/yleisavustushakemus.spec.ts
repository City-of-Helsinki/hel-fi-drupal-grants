import { expect, test } from '@playwright/test';
import { selectRole } from '../../utils/role';
import { clickContinueButton, clickGoToPreviewButton, expectApplicationToBeOpen, submitApplication } from '../../utils/helpers';

test('Kaupunginhallitus, yleisavustushakemus', async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  await page.goto('fi/uusi-hakemus/yleisavustushakemus');
  await expect(page.locator('#webform-submission-yleisavustushakemus-edit-form')).toBeVisible();
  await expectApplicationToBeOpen(page);

  // Step 1
  await page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('test@example.org');
  await page.getByLabel('Yhteyshenkilö').fill('mniimim');
  await page.getByLabel('Puhelinnumero').fill('0404004');
  await page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
  await page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
  await clickContinueButton(page);

  // Step 2
  await page.getByLabel('Vuosi, jolle haen avustusta').selectOption({ index: 1 });
  await page.locator('#edit-subventions-items-0-amount').fill('200,50€');
  await page.getByRole('textbox', { name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista' }).fill('foo');
  await clickContinueButton(page);

  // Step 3
  await page.getByRole('group', { name: 'Harjoittaako yhteisö liiketoimintaa' }).getByText('Ei', { exact: true }).click();
  await page.locator('#edit-fee-person').fill('200,00€');
  await page.locator('#edit-fee-community').fill('50,00€');
  await page.locator('#edit-members-applicant-person-global').fill('4');
  await page.locator('#edit-members-applicant-person-local').fill('5');
  await page.locator('#edit-members-applicant-community-global').fill('6');
  await page.locator('#edit-members-applicant-community-local').fill('7');
  await clickContinueButton(page);

  // Step 4
  await page.getByRole('group', { name: 'Yhteisön säännöt' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu tilinpäätös' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu toimintakertomus' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu tilin- tai toiminnanta' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vuosikokouksen pöytäkirja' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Toimintasuunnitelma' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Talousarvio' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByLabel('Lisäselvitys liitteistä').fill('qwfwqfwfq');
  await clickGoToPreviewButton(page);

  // Step 5
  await submitApplication(page);
});
