import { faker } from '@faker-js/faker';
import { Locator, Page, expect, test } from '@playwright/test';
import { selectRole } from '../utils/role';

test('Oma asiointi', async ({ page }) => {
  await selectRole(page, 'PRIVATE_PERSON');
  await page.goto('/fi/oma-asiointi');

  // Headings
  await expect.soft(page.getByRole('heading', { name: 'Tietoa avustuksista ja ohjeita hakijalle' })).toBeVisible();
  await expect.soft(page.getByRole('heading', { name: 'Keskeneräiset hakemukset' })).toBeVisible();
  await expect.soft(page.getByRole('heading', { name: 'Lähetetyt hakemukset' })).toBeVisible();

  // Search controls
  await expect.soft(page.getByLabel('Etsi hakemusta')).toBeVisible();
  await expect.soft(page.getByRole('button', { name: 'Etsi hakemusta' })).toBeEnabled();
  await expect.soft(page.getByLabel('Näytä vain käsittelyssä olevat hakemukset')).toBeVisible();
  await expect.soft(page.getByLabel('Järjestä')).toBeVisible();
});

test.describe('Hakuprofiili', () => {
  test.describe('Private person', () => {
    let page: Page;

    test.beforeAll(async ({ browser }) => {
      page = await browser.newPage();
      await selectRole(page, 'PRIVATE_PERSON');
    });

    test.beforeEach(async () => {
      await page.goto('/fi/oma-asiointi/hakuprofiili');
    });

    test('Contact information is visible', async () => {
      await expect.soft(page.getByRole('heading', { name: 'Omat tiedot' })).toBeVisible();

      // Perustiedot
      await expect.soft(page.getByRole('heading', { name: 'Perustiedot' })).toBeVisible();
      await expect.soft(page.getByText('Etunimi')).toBeVisible();
      await expect.soft(page.getByText('Sukunimi')).toBeVisible();
      await expect.soft(page.getByText('Henkilötunnus')).toBeVisible();
      await expect.soft(page.getByRole('link', { name: 'Siirry Helsinki-profiiliin päivittääksesi sähköpostiosoitetta' })).toBeVisible();

      // Omat yhteystiedot
      await expect.soft(page.getByRole('heading', { name: 'Omat yhteystiedot' })).toBeVisible();
      await expect.soft(page.locator('#addresses').getByText('Osoite')).toBeVisible();
      await expect.soft(page.locator('#phone-number').getByText('Puhelinnumero')).toBeVisible();
      await expect.soft(page.locator('#officials-3').getByText('Tilinumerot')).toBeVisible();
      await expect.soft(page.getByRole('link', { name: 'Muokkaa omia tietoja' })).toBeVisible();
    });

    test('Contact information can be updated', async () => {
      const newStreetAddress = faker.location.streetAddress();
      const newPostalCode = faker.location.zipCode('#####');
      const newCity = faker.location.city();
      const newPhone = faker.phone.number();

      await page.getByRole('link', { name: 'Muokkaa omia tietoja' }).click();

      // Check for required fields
      await expect.soft(page.getByLabel('Katuosoite')).toHaveAttribute('required');
      await expect.soft(page.getByLabel('Postinumero')).toHaveAttribute('required');
      await expect.soft(page.getByLabel('Toimipaikka')).toHaveAttribute('required');
      await expect.soft(page.getByLabel('Puhelinnumero')).toHaveAttribute('required');

      // Fill new info and submit
      await page.getByLabel('Katuosoite').fill(newStreetAddress);
      await page.getByLabel('Postinumero').fill(newPostalCode);
      await page.getByLabel('Toimipaikka').fill(newCity);
      await page.getByLabel('Puhelinnumero').fill(newPhone);
      await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();

      // Profile info contains the new data
      const profileInfoText = await page.locator('.grants-profile--extrainfo').textContent();
      expect.soft(profileInfoText).toContain(newStreetAddress);
      expect.soft(profileInfoText).toContain(newPostalCode);
      expect.soft(profileInfoText).toContain(newCity);
      expect.soft(profileInfoText).toContain(newPhone);
    });

    test('Bank account requirement', async () => {
      const removeBankAccountButton = page.getByRole('button', { name: 'Poista' });
      await removeBankAccountAndCheckError(page, removeBankAccountButton);
    });

    test.afterAll(async () => {
      await page.close();
    });
  });

  test.describe('Registered community', () => {
    let page: Page;

    test.beforeAll(async ({ browser }) => {
      page = await browser.newPage();
      await selectRole(page, 'REGISTERED_COMMUNITY');
    });

    test.beforeEach(async () => {
      await page.goto('/fi/oma-asiointi/hakuprofiili');
    });

    test('Contact information is visible', async () => {
      await expect.soft(page.getByRole('heading', { name: 'Yhteisön tiedot', exact: true })).toBeVisible();
      await expect.soft(page.locator('#perustamisvuosi').getByText('Perustamisvuosi')).toBeVisible();
      await expect.soft(page.locator('#yhteison-lyhenne').getByText('Yhteisön lyhenne')).toBeVisible();
      await expect.soft(page.locator('#verkkosivujen-osoite').getByText('Verkkosivujen osoite')).toBeVisible();
      await expect.soft(page.locator('#toiminna-tarkoitus').getByText('Toiminnan tarkoitus')).toBeVisible();
      await expect.soft(page.locator('#addresses').getByText('Osoitteet')).toBeVisible();
      await expect.soft(page.locator('#officials').getByText('Toiminnasta vastaavat henkilöt')).toBeVisible();
      await expect.soft(page.locator('#officials-3').getByText('Tilinumerot')).toBeVisible();
      await expect.soft(page.getByRole('link', { name: 'Muokkaa yhteisön tietoja' })).toBeVisible();
    });

    test('Contact information can be updated', async () => {
      const description = faker.lorem.words(14);
      const streetAddress = faker.location.streetAddress();
      const zipCode = faker.location.zipCode('#####');
      const city = faker.location.city();

      const year = faker.number.int({ min: 1900, max: 2020 }).toString();
      const abbrevation = faker.lorem.word();
      const webPage = faker.internet.url();

      await page.getByRole('link', { name: 'Muokkaa yhteisön tietoja' }).click();

      // Check for required fields
      await expect.soft(page.getByLabel('Katuosoite')).toHaveAttribute('required');
      await expect.soft(page.getByLabel('Postinumero')).toHaveAttribute('required');
      await expect.soft(page.getByLabel('Toimipaikka')).toHaveAttribute('required');
      await expect.soft(page.locator('#edit-businesspurposewrapper-businesspurpose')).toHaveAttribute('required');

      // Fill new info and submit
      await page.getByLabel('Perustamisvuosi').fill(year);
      await page.getByLabel('Yhteisön lyhenne').fill(abbrevation);
      await page.getByLabel('Verkkosivujen osoite').fill(webPage);
      await page.locator('#edit-businesspurposewrapper-businesspurpose').fill(description);
      await page.getByLabel('Katuosoite').fill(streetAddress);
      await page.getByLabel('Postinumero').fill(zipCode);
      await page.getByLabel('Toimipaikka').fill(city);
      await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();

      // Profile info contains the new data
      const profileInfo = page.locator('.grants-profile--extrainfo');
      const profileInfoText = await profileInfo.textContent();

      [description, streetAddress, zipCode, city, year, abbrevation, webPage].forEach((element) => {
        expect.soft(profileInfoText).toContain(element);
      });
    });

    test('Bank account requirement', async () => {
      const removeBankAccountButton = page.getByRole('group', { name: 'Yhteisön pankkitili' }).getByRole('button');
      await removeBankAccountAndCheckError(page, removeBankAccountButton);
    });

    test.afterAll(async () => {
      await page.close();
    });
  });

  test.describe('Unregistered community', () => {
    let page: Page;

    test.beforeAll(async ({ browser }) => {
      page = await browser.newPage();
      await selectRole(page, 'UNREGISTERED_COMMUNITY');
    });

    test.afterAll(async () => {
      await page.close();
    });

    test.beforeEach(async () => {
      await page.goto('/fi/oma-asiointi/hakuprofiili');
    });

    test('Contact information is visible', async () => {
      await expect.soft(page.getByRole('heading', { name: 'Yhteisön tai ryhmän tiedot', exact: true })).toBeVisible();

      await expect.soft(page.getByRole('heading', { name: 'Yhteisön tai ryhmän tiedot avustusasioinnissa' })).toBeVisible();
      await expect.soft(page.getByText('Yhteisön tai ryhmän nimi')).toBeVisible();
      await expect.soft(page.getByText('Osoitteet')).toBeVisible();
      await expect.soft(page.getByText('Tilinumerot')).toBeVisible();
      await expect.soft(page.getByText('Toiminnasta vastaavat henkilöt')).toBeVisible();
    });

    test('Contact information can be updated', async () => {
      const companyName = faker.company.name();
      const personName = faker.person.fullName();
      const phoneNumber = faker.phone.number();
      const email = faker.internet.email();
      const streetAddress = faker.location.streetAddress();
      const zipCode = faker.location.zipCode('#####');
      const city = faker.location.city();

      await page.getByRole('link', { name: 'Muokkaa yhteisön tietoja' }).click();

      // Check for required fields
      await expect.soft(page.locator('#edit-companynamewrapper-companyname')).toHaveAttribute('required');
      await expect.soft(page.getByLabel('Katuosoite')).toHaveAttribute('required');
      await expect.soft(page.getByLabel('Postinumero')).toHaveAttribute('required');
      await expect.soft(page.getByLabel('Toimipaikka')).toHaveAttribute('required');
      await expect.soft(page.getByLabel('Sähköpostiosoite')).toHaveAttribute('required');
      await expect.soft(page.getByLabel('Puhelinnumero')).toHaveAttribute('required');

      // Fill new info and submit
      await page.locator('#edit-companynamewrapper-companyname').fill(companyName);
      await page.getByLabel('Katuosoite').fill(streetAddress);
      await page.getByLabel('Postinumero').fill(zipCode);
      await page.getByLabel('Toimipaikka').fill(city);
      await page.getByLabel('Nimi', { exact: true }).fill(personName);
      await page.getByLabel('Rooli').selectOption({ label: 'Vastuuhenkilö' });
      await page.getByLabel('Sähköpostiosoite').fill(email);
      await page.getByLabel('Puhelinnumero').fill(phoneNumber);
      await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();

      // Check that profile info contains the new data
      const profileInfoText = await page.locator('.grants-profile--extrainfo').textContent();
      expect.soft(profileInfoText).toContain(companyName);
      expect.soft(profileInfoText).toContain(streetAddress);
      expect.soft(profileInfoText).toContain(zipCode);
      expect.soft(profileInfoText).toContain(city);
      expect.soft(profileInfoText).toContain(personName);
      expect.soft(profileInfoText).toContain(phoneNumber);
    });

    test('An official is required', async () => {
      await page.goto('fi/oma-asiointi/hakuprofiili/muokkaa');

      await page.locator('#edit-officialwrapper-0-official-deletebutton').click();
      await expect(page.locator('#edit-officialwrapper-0-official')).not.toBeVisible();
      await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
      await expect(page.getByText('Sinun tulee lisätä vähintään yksi toiminnasta vastaava henkilö').first()).toBeVisible();
    });

    test('Bank account requirement', async () => {
      const removeBankAccountButton = page.getByRole('group', { name: 'Yhteisön tai ryhmän pankkitili' }).getByRole('button');
      await removeBankAccountAndCheckError(page, removeBankAccountButton);
    });
  });
});

const removeBankAccountAndCheckError = async (page: Page, deleteButtonLocator: Locator) => {
  const bankAccountSection = page.locator('#edit-bankaccountwrapper-0-bank');

  await page.goto('fi/oma-asiointi/hakuprofiili/muokkaa');
  await expect(bankAccountSection).toBeVisible();

  await Promise.all([page.waitForResponse((response) => response.status() === 200), deleteButtonLocator.click()]);

  await expect(bankAccountSection).not.toBeVisible();

  await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
  const warningText = page.getByLabel('Notification').getByText('Sinun tulee lisätä vähintään yksi pankkitili').first();
  await expect(warningText).toBeVisible();
};
