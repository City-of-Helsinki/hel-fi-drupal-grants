import { test, expect } from '@playwright/test';
import { clickContinueButton, clickGoToPreviewButton, expectApplicationToBeOpen, submitApplication } from '../../utils/helpers';
import { selectRole } from '../../utils/role';

test('Liikunta, tapahtuma-avustushakemus', async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  await page.goto('/fi/uusi-hakemus/liikunta_tapahtuma');
  await expectApplicationToBeOpen(page);
  await expect(page.locator('#webform-submission-liikunta-tapahtuma-edit-form')).toBeVisible();

  // Step 1
  await page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('asadsdqwetest@example.org');
  await page.getByLabel('Yhteyshenkilö').fill('asddsa');
  await page.getByLabel('Puhelinnumero').fill('0234432243');
  await page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
  await page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
  await clickContinueButton(page);

  // Step 2
  await page.locator('#edit-acting-year').selectOption({ index: 1 });
  await page.locator('#edit-subventions-items-0-amount').fill('123,00€');
  await page.getByRole('textbox', { name: 'Tapahtuma, johon avustusta haetaan' }).fill('foo');
  await page.getByRole('textbox', { name: 'Tapahtuman kohderyhmä' }).fill('qwe');
  await page.getByRole('textbox', { name: 'Tapahtumapaikka' }).fill('qwe');
  await page.getByRole('textbox', { name: 'Tarkemmat tiedot tapahtumasta ja sen sisällöstä' }).fill('qwe');
  await page.locator('#edit-equality-radios').getByText('Kyllä').click();
  await page.getByLabel('Miten tapahtuma edistää yhdenvertaisuutta ja tasa-arvoa?').fill('qweqweqwe');
  await page.locator('#edit-inclusion-radios').getByText('Ei').click();
  await page.locator('#edit-environment-radios').getByText('Kyllä').click();
  await page.getByLabel('Miten tapahtumassa on huomioitu ympäristöasiat?').fill('qwrqwrqwr');
  await page.locator('#edit-exercise-radios').getByText('Kyllä').click();
  await page.getByLabel('Miten tapahtuma innostaa uusia harrastajia omatoimisen tai ohjatun liikunnan pariin?').fill('wegewgweg');
  await page.locator('#edit-activity-radios').getByText('Kyllä').click();
  await page.getByLabel('Miten tapahtuma innostaa ihmisiä arkiaktiivisuuteen?').fill('wegewgewgewg');
  await page.getByRole('group', { name: '20 vuotta täyttäneet osallistujat' }).getByRole('textbox', { name: 'Miehet' }).fill('10');
  await page.getByRole('group', { name: '20 vuotta täyttäneet osallistujat' }).getByRole('textbox', { name: 'Naiset' }).fill('10');
  await page.getByRole('group', { name: '20 vuotta täyttäneet osallistujat' }).getByRole('textbox', { name: 'Muut' }).fill('10');
  await page.getByRole('group', { name: 'Alle 20-vuotiaat osallistujat' }).getByRole('textbox', { name: 'Pojat' }).fill('10');
  await page.getByRole('group', { name: 'Alle 20-vuotiaat osallistujat' }).getByRole('textbox', { name: 'Tytöt' }).fill('10');
  await page.getByRole('group', { name: 'Alle 20-vuotiaat osallistujat' }).getByRole('textbox', { name: 'Muut' }).fill('10');
  await page.getByLabel('Alkaa').fill('2024-12-12');
  await page.getByLabel('Päättyy').fill('2025-12-12');
  await clickContinueButton(page);

  // Step 3
  await page.getByRole('group', { name: 'Tulo' }).getByLabel('Kuvaus tulosta').fill('qweqweqwqew');
  await page.getByRole('group', { name: 'Tulo' }).getByLabel('Määrä').fill('3000');
  await page.getByRole('group', { name: 'Meno' }).getByLabel('Kuvaus menosta').fill('qweqweqwqew');
  await page.getByRole('group', { name: 'Meno' }).getByLabel('Määrä').fill('3000');
  await clickContinueButton(page);

  // Step 4
  await page.getByRole('textbox', { name: 'Lisätiedot Lisätiedot' }).fill('qwfqwfwqf');
  await page.getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByLabel('Lisäselvitys liitteistä').fill('asfafsasf');
  await clickGoToPreviewButton(page);

  // Step 5
  await submitApplication(page);
});
