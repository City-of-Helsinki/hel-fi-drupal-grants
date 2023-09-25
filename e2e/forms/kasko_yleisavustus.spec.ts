import { test, expect } from '@playwright/test';
import { loginWithCompanyRole, startNewApplication } from '../utils/helpers';

const APPLICATION_TITLE = "Kasvatus ja koulutus: yleisavustuslomake";

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

  //Fill step 2
  await page.getByLabel('Vuosi, jolle haen avustusta').selectOption('2023');
  await page.locator('#edit-subventions-items-0-amount').fill('128');
  await page.locator('#edit-subventions-items-1-amount').fill('256');
  await page.locator('#edit-subventions-items-2-amount').fill('512');
  await page.getByRole('textbox', { name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista' }).fill('lyhyt kuvasu');
  await page.getByLabel('Kuvaus lainoista ja takauksista').fill('asdadsdadaas');
  await page.getByLabel('Kuvaus tiloihin liittyvästä tuesta').fill('sdfdfsfdsdsf');
  await page.getByRole('button', { name: 'Seuraava' }).click();

  // Fill step 3
  await page.getByRole('textbox', { name: 'Toiminnan kuvaus' }).fill('asffsafsasfa');
  await page.getByText('Ei', { exact: true }).click();
  await page.locator('#edit-fee-person').fill('64');
  await page.locator('#edit-fee-community').fill('64');
  await page.getByRole('textbox', { name: 'Henkilöjäseniä yhteensä Henkilöjäseniä yhteensä' }).fill('123');
  await page.getByRole('textbox', { name: 'Helsinkiläisiä henkilöjäseniä yhteensä Helsinkiläisiä henkilöjäseniä yhteensä' }).fill('22');
  await page.getByRole('textbox', { name: 'Yhteisöjäseniä Yhteisöjäseniä' }).fill('44');
  await page.getByRole('textbox', { name: 'Helsinkiläisiä yhteisöjäseniä yhteensä Helsinkiläisiä yhteisöjäseniä yhteensä' }).fill('55');
  await page.getByRole('button', { name: 'Seuraava' }).click();

  // Fill step 4
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill('qwfqwfqwfwfqfwq');
  await page.getByRole('group', { name: 'Yhteisön säännöt Yhteisön säännöt' }).getByLabel('Attachment will be delivered at later time').check();
  await page.getByRole('group', { name: 'Vahvistettu tilinpäätös' }).getByLabel('Attachment will be delivered at later time').check();
  await page.getByRole('group', { name: 'Vahvistettu toimintakertomus' }).getByLabel('Attachment will be delivered at later time').check();
  await page.getByRole('group', { name: 'Vahvistettu tilin- tai toiminnantarkastuskertomus' }).getByLabel('Attachment will be delivered at later time').check();
  await page.locator('#edit-vuosikokouksen-poytakirja--wrapper').getByText('Attachment will be delivered at later time').click();
  await page.locator('#edit-toimintasuunnitelma--wrapper').getByText('Attachment will be delivered at later time').click();
  await page.locator('#edit-talousarvio--wrapper').getByText('Attachment will be delivered at later time').click();
  await page.getByLabel('Lisäselvitys liitteistä').fill('sdfdfsdfsdfsdfsdfsdfs');
  await page.getByRole('button', { name: 'Esikatseluun >' }).click();

  // Step 5: Check preview page
  await page.getByText('Tarkista lähetyksesi. Lähetyksesi on valmis vasta, kun painat "Lähetä"-painikett').click();
  await expect(page.getByText('Helsingin kaupungin myöntämiin avustuksiin sovelletaan seuraavia avustusehtoja.')).toBeVisible();
  await expect(page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot.')).toBeVisible();
  await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
});

