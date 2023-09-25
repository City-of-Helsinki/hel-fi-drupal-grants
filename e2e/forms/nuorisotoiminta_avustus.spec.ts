import { test } from '@playwright/test';
import { loginWithCompanyRole, startNewApplication } from '../utils/helpers';

const APPLICATION_TITLE = "Nuorisotoiminnan toiminta-avustus";

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
  await page.getByLabel('Select official').selectOption('0');
  await page.getByRole('button', { name: 'Seuraava' }).click();

  // Fill step 2
  await page.locator('#edit-acting-year').selectOption('2023');
  await page.locator('#edit-subventions-items-0-amount').fill('123');
  await page.locator('#edit-subventions-items-1-amount').fill('123');
  await page.getByRole('textbox', { name: 'Yhdistyksen kuluvan vuoden toiminta-avustus Yhdistyksen kuluvan vuoden toiminta-avustus' }).fill('34543');
  await page.getByRole('textbox', { name: 'Selvitys kuluvan vuoden toiminta-avustuksen käytöstä Selvitys kuluvan vuoden toiminta-avustuksen käytöstä' }).fill('565');
  await page.getByRole('textbox', { name: 'Yhdistyksen kuluvan vuoden palkkausavustus Yhdistyksen kuluvan vuoden palkkausavustus' }).fill('56757');
  await page.getByRole('textbox', { name: 'Selvitys kuluvan vuoden palkkausavustuksen käytöstä Selvitys kuluvan vuoden palkkausavustuksen käytöstä' }).fill('678687');
  await page.getByRole('textbox', { name: 'Kuvaus kuluvan vuoden avustuksen käytöstä Kuvaus kuluvan vuoden avustuksen käytöstä' }).fill('gfjgjjfggfjjgf');
  await page.getByRole('button', { name: 'Seuraava' }).click();

  // Fill step 3
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill('asffsafsasfa');
  await page.getByRole('group', { name: 'Yhteisön säännöt' }).getByLabel('Attachment will be delivered at later time').check();
  await page.getByRole('group', { name: 'Toimintasuunnitelma' }).getByLabel('Attachment will be delivered at later time').check();
  await page.getByRole('group', { name: 'Talousarvio' }).getByLabel('Attachment will be delivered at later time').check();
  await page.getByLabel('Lisäselvitys liitteistä').fill('wefewffwfewgfhgfhgfhhgf');
  await page.getByRole('button', { name: 'Esikatseluun' }).click();

  // check data on confirmation page
  await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
});

