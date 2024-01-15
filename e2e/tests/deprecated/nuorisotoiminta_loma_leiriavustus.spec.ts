import { test, expect } from '@playwright/test';
import { PATH_TO_TEST_EXCEL, checkErrorNofification, clickContinueButton, selectRole, uploadFile } from '../../utils/helpers';

test("Nuorisotoiminnan loma-aikojen leiriavustus", async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  await page.goto("/fi/uusi-hakemus/nuorlomaleir")

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
  await uploadFile(page, 'input[name="files[leiri_excel_attachment]"]', PATH_TO_TEST_EXCEL);
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

