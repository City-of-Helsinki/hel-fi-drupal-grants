import { test } from '@playwright/test';
import { clickContinueButton, clickGoToPreviewButton, expectApplicationToBeOpen, submitApplication } from '../../utils/helpers';
import { selectRole } from '../../utils/role';
import { PATH_TO_TEST_EXCEL } from '../../utils/constants';
import { uploadFile } from '../../utils/upload';

test('Nuorisotoiminnan loma-aikojen leiriavustus', async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  await page.goto('/fi/uusi-hakemus/nuorlomaleir');
  await expectApplicationToBeOpen(page);

  // Step 1
  await page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('asadsdqwetest@example.org');
  await page.getByLabel('Yhteyshenkilö').fill('asddsa');
  await page.getByLabel('Puhelinnumero').fill('0234432243');
  await page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
  await page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
  await page.getByLabel('Valitse vastaava henkilö').selectOption('0');
  await clickContinueButton(page);

  // Step 2
  await page.locator('#edit-acting-year').selectOption({ index: 1 });
  await page.locator('#edit-subventions-items-0-amount').fill('123,00€');
  await clickContinueButton(page);

  // Step 3
  await page.getByLabel('Kuvaus tulosta').fill('fghhgfhfgjfgj');
  await page.getByRole('group', { name: 'Tulo' }).getByLabel('Määrä (€)').fill('345');
  await page.getByLabel('Kuvaus menosta').fill('jytjtjyjyjyjy');
  await page.getByRole('group', { name: 'Meno' }).getByLabel('Määrä (€)').fill('5656');
  await clickContinueButton(page);

  // Step 4
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill('fghhfghfghfghf');
  await page.getByRole('group', { name: 'Yhteisön säännöt' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await uploadFile(page, page.locator('#edit-leiri-excel-attachment').getByText('Lisää tiedosto'), PATH_TO_TEST_EXCEL);
  await page.getByRole('group', { name: 'Toimintasuunnitelma' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Talousarvio' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByLabel('Lisäselvitys liitteistä').fill('kjhkjhkjhk');
  await clickGoToPreviewButton(page);

  // Step 5
  await submitApplication(page);
});
