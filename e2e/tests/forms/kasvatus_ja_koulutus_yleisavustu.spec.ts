import { test } from '@playwright/test';
import { clickContinueButton, clickGoToPreviewButton, expectApplicationToBeOpen, submitApplication } from '../../utils/helpers';
import { selectRole } from '../../utils/role';

test('Kasvatus ja koulutus: yleisavustuslomake', async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  await page.goto('/fi/uusi-hakemus/kasvatus_ja_koulutus_yleisavustu');
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
  await page.locator('#edit-subventions-items-0-amount').fill('128,00€');
  await page.getByRole('textbox', { name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista' }).fill('lyhyt kuvasu');
  await page.getByLabel('Kuvaus lainoista ja takauksista').fill('asdadsdadaas');
  await page.getByLabel('Kuvaus tiloihin liittyvästä tuesta').fill('sdfdfsfdsdsf');
  await clickContinueButton(page);

  // Step 3
  await page.getByRole('textbox', { name: 'Toiminnan kuvaus' }).fill('asffsafsasfa');
  await page.getByText('Ei', { exact: true }).click();
  await page.locator('#edit-fee-person').fill('64');
  await page.locator('#edit-fee-community').fill('64');
  await page.getByRole('textbox', { name: 'Henkilöjäseniä yhteensä Henkilöjäseniä yhteensä' }).fill('123');
  await page.getByRole('textbox', { name: 'Helsinkiläisiä henkilöjäseniä yhteensä' }).fill('22');
  await page.getByRole('textbox', { name: 'Yhteisöjäseniä Yhteisöjäseniä' }).fill('44');
  await page.getByRole('textbox', { name: 'Helsinkiläisiä yhteisöjäseniä yhteensä' }).fill('55');
  await clickContinueButton(page);

  // Step 4
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill('qwfqwfqwfwfqfwq');
  await page.getByRole('group', { name: 'Yhteisön säännöt Yhteisön säännöt' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu tilinpäätös' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu toimintakertomus' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu tilin- tai toiminna' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.locator('#edit-vuosikokouksen-poytakirja--wrapper').getByText('Liite toimitetaan myöhemmin').click();
  await page.locator('#edit-toimintasuunnitelma--wrapper').getByText('Liite toimitetaan myöhemmin').click();
  await page.locator('#edit-talousarvio--wrapper').getByText('Liite toimitetaan myöhemmin').click();
  await page.getByLabel('Lisäselvitys liitteistä').fill('sdfdfsdfsdfsdfsdfsdfs');
  await clickGoToPreviewButton(page);

  // Step 5
  await submitApplication(page);
});
