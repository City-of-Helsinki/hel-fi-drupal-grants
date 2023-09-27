import { test, expect, Page } from '@playwright/test';
import { loginWithCompanyRole, startNewApplication } from '../utils/helpers';

const APPLICATION_TITLE = "Liikunnan kohdeavustus";

test(APPLICATION_TITLE, async ({ page }) => {
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
  await page.locator('#edit-acting-year').selectOption('2023');
  await page.getByText('Ei', { exact: true }).click();
  await page.locator('#edit-subventions-items-0-amount').fill('123');
  await page.getByRole('textbox', { name: 'Lyhyt kuvaus haettavan avustuksen käyttötarkoituksista' }).fill('lyhyt kuvasu');
  await page.getByRole('button', { name: 'Seuraava' }).click();

  // Fill step 3
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill('asffsafsasfa');
  await page.getByLabel('Lisäselvitys liitteistä').fill('wefewffwfew');
  await page.getByRole('button', { name: 'Esikatseluun' }).click();

  // check data on confirmation page
  await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
  
  // Submit application
  await page.getByRole('button', { name: 'Lähetä' }).click();
  await expect(page.getByRole('heading', { name: 'Avustushakemus lähetetty onnistuneesti' })).toBeVisible()
});

