import { test, expect } from '@playwright/test';
import { clickContinueButton, clickGoToPreviewButton, expectApplicationToBeOpen, submitApplication } from '../../utils/helpers';
import { selectRole } from '../../utils/role';

test('Iltapäivätoiminnan harkinnanvarainen lisäavustushakemus', async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  await page.goto('/fi/uusi-hakemus/kasko_ip_lisa');
  await expectApplicationToBeOpen(page);

  // Step 1
  await page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('asadsdqwetest@example.org');
  await page.getByLabel('Yhteyshenkilö').fill('asddsa');
  await page.getByLabel('Puhelinnumero').fill('0234432243');
  await page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
  await page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
  await page.getByLabel('Valitse vastaava henkilö').selectOption({ index: 1 });
  await clickContinueButton(page);

  // Step 2
  await page.getByLabel('Vuosi, jolle haen avustusta').selectOption({ index: 1 });
  await page.locator('#edit-subventions-items-0-amount').fill('123,00€');
  await page.getByRole('textbox', { name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista' }).fill('lyhyt kuvasu');
  await page.getByLabel('Alkaen').fill('2024-09-23');
  await page.getByLabel('Päättyy').fill('2024-11-30');
  await clickContinueButton(page);

  // Step 3
  await expect(page.getByRole('textbox', { name: 'Lisätiedot' })).toBeVisible();
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill('asffsafsasfa');
  await page.getByLabel('Lisäselvitys liitteistä').fill('wefewffwfew');
  await clickGoToPreviewButton(page);

  // Step 4
  await submitApplication(page);
});
