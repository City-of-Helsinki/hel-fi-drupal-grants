import { test, expect } from '@playwright/test';
import { checkErrorNofification, clickContinueButton, selectRole, startNewApplication } from '../../utils/helpers';

const APPLICATION_TITLE = "Nuorisotoiminnan loma-aikojen leiriavustus";

// TODO; File upload keeps failing
test.fixme(APPLICATION_TITLE, async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  await startNewApplication(page, APPLICATION_TITLE)

  // Fill step 1
  await page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('asadsdqwetest@example.org');
  await page.getByLabel('Yhteyshenkilö').fill('asddsa');
  await page.getByLabel('Puhelinnumero').fill('0234432243');
  await page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
  await page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
  await page.getByLabel('Valitse vastaava henkilö').selectOption('0');
  await clickContinueButton(page);

  //Fill step 2
  await page.locator('#edit-acting-year').selectOption('2024');
  await page.locator('#edit-subventions-items-0-amount').fill('123,00€');
  await clickContinueButton(page);

  // Fill step 3
  await page.getByLabel('Kuvaus tulosta').fill('fghhgfhfgjfgj');
  await page.getByRole('group', { name: 'Tulo Tulo' }).getByLabel('Määrä (€)').fill('345');
  await page.getByLabel('Kuvaus menosta').fill('jytjtjyjyjyjy');
  await page.getByRole('group', { name: 'Meno Meno' }).getByLabel('Määrä (€)').fill('5656');
  await clickContinueButton(page);

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
  await checkErrorNofification(page);

  // Submit application
  await page.getByRole('button', { name: 'Lähetä' }).click();
  await expect(page.getByRole('heading', { name: 'Avustushakemus lähetetty onnistuneesti' })).toBeVisible()
});

