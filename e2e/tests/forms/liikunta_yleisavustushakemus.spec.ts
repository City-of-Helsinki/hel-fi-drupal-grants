import { test } from '@playwright/test';
import { clickContinueButton, clickGoToPreviewButton, expectApplicationToBeOpen, submitApplication } from '../../utils/helpers';
import { selectRole } from '../../utils/role';

test('Liikunnan kohdeavustus', async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  await page.goto('/fi/uusi-hakemus/liikunta_yleisavustushakemus');
  await expectApplicationToBeOpen(page);

  // Step 1
  await page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('asadsdqwetest@example.org');
  await page.getByLabel('Yhteyshenkilö').fill('asddsa');
  await page.getByLabel('Puhelinnumero').fill('0234432243');
  await page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
  await page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
  await clickContinueButton(page);

  // Step 2
  await page.locator('#edit-acting-year').selectOption({ index: 1 });
  await page.getByText('Ei', { exact: true }).click();
  await page.locator('#edit-subventions-items-0-amount').fill('123,00€');
  await page.getByRole('textbox', { name: 'Lyhyt kuvaus haettavan avustuksen käyttötarkoituksista' }).fill('lyhyt kuvasu');
  await clickContinueButton(page);

  // Step 3
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill('asffsafsasfa');
  await page.getByLabel('Lisäselvitys liitteistä').fill('wefewffwfew');
  await clickGoToPreviewButton(page);

  // Step 4
  await submitApplication(page);
});
