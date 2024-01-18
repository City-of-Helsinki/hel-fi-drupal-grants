import { test, expect } from '@playwright/test';
import { checkErrorNofification, clickContinueButton } from '../../utils/helpers';

import {selectRole} from "../../utils/auth_helpers";

test("Liikunnan kohdeavustus", async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  await page.goto("/fi/uusi-hakemus/liikunta_yleisavustushakemus")

  // Fill step 1
  await page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('asadsdqwetest@example.org');
  await page.getByLabel('Yhteyshenkilö').fill('asddsa');
  await page.getByLabel('Puhelinnumero').fill('0234432243');
  await page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
  await page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
  await clickContinueButton(page);

  //Fill step 2
  await page.locator('#edit-acting-year').selectOption('2023');
  await page.getByText('Ei', { exact: true }).click();
  await page.locator('#edit-subventions-items-0-amount').fill('123,00€');
  await page.getByRole('textbox', { name: 'Lyhyt kuvaus haettavan avustuksen käyttötarkoituksista' }).fill('lyhyt kuvasu');
  await clickContinueButton(page);

  // Fill step 3
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill('asffsafsasfa');
  await page.getByLabel('Lisäselvitys liitteistä').fill('wefewffwfew');
  await page.getByRole('button', { name: 'Esikatseluun' }).click();

  // check data on confirmation page
  await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
  await checkErrorNofification(page);

  // Submit application
  await page.getByRole('button', { name: 'Lähetä' }).click();
  await expect(page.getByRole('heading', { name: 'Avustushakemus lähetetty onnistuneesti' })).toBeVisible()
});
