import { test, expect } from '@playwright/test';
import { acceptCookies, loginWithCompanyRole, startNewApplication } from '../../utils/helpers';
import path from 'path';

const APPLICATION_TITLE = "Nuorisotoiminnan loma-aikojen leiriavustus";

// TODO; File upload keeps failing
test.skip(APPLICATION_TITLE, async ({ page }) => {
  // Login
  await loginWithCompanyRole(page)
  await startNewApplication(page, APPLICATION_TITLE)

  // Fill step 1
  await page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('asadsdqwetest@example.org');
  await page.getByLabel('Yhteyshenkilö').fill('asddsa');
  await page.getByLabel('Puhelinnumero').fill('0234432243');
  await page.locator('#edit-community-address-community-address-select').selectOption('0b78909a-1d05-4c50-af97-9f03ef183a11');
  await page.locator('#edit-bank-account-account-number-select').selectOption('FI4069674615287672');
  await page.getByLabel('Valitse vastaava henkilö').selectOption('0');
  await page.getByRole('button', { name: 'Seuraava' }).click();

  //Fill step 2
  await page.locator('#edit-acting-year').selectOption('2024');
  await page.locator('#edit-subventions-items-0-amount').fill('123');
  await page.getByText('Avustukset', { exact: true }).click(); // TODO: Seuraava button isnt getting clicked (a focus issue??)
  await page.getByRole('button', { name: 'Seuraava' }).click();

  // Fill step 3
  await page.getByLabel('Kuvaus tulosta').fill('fghhgfhfgjfgj');
  await page.getByRole('group', { name: 'Tulo Tulo' }).getByLabel('Määrä (€)').fill('345');
  await page.getByLabel('Kuvaus menosta').fill('jytjtjyjyjyjy');
  await page.getByRole('group', { name: 'Meno Meno' }).getByLabel('Määrä (€)').fill('5656');
  await page.getByRole('button', { name: 'Seuraava >' }).click();

  // Fill step 4
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill('fghhfghfghfghf');
  await page.getByRole('group', { name: 'Yhteisön säännöt' }).getByLabel('Liite toimitetaan myöhemmin').check();

  // TODO: Unreliable file upload method
  const inputElement = page.locator('input[name="files[leiri_excel_attachment]"]');
  const requestPromise = page.waitForRequest(req => req.method() === "POST");
  await inputElement.setInputFiles('e2e/utils/test.pdf');
  await requestPromise;
  await page.waitForSelector('a[type="application/pdf"]:visible');

  await page.getByRole('group', { name: 'Toimintasuunnitelma' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Talousarvio' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByLabel('Lisäselvitys liitteistä').fill('kjhkjhkjhk');
  await page.getByRole('button', { name: 'Esikatseluun' }).click();

  // Step 5: check data on confirmation page
  await expect(page.getByText('Tarkista lähetyksesi. Lähetyksesi on valmis vasta, kun')).toBeVisible()
  await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();

  // Submit application
  await page.getByRole('button', { name: 'Lähetä' }).click();
  await expect(page.getByRole('heading', { name: 'Avustushakemus lähetetty onnistuneesti' })).toBeVisible()
});

