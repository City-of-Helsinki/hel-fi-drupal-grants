import { type Page, expect } from '@playwright/test';
import { ProfilePage } from './profilePage';
import { faker } from '@faker-js/faker';
import { selectRole } from '../../../utils/role';
import { TEST_IBAN } from '../../../utils/test_data';
import { uploadFile } from '../../../utils/upload';

const inputData = {
  description: faker.lorem.words(14),
  streetAddress: faker.location.streetAddress(),
  zipCode: faker.location.zipCode('#####'),
  city: faker.location.city(),
  year: faker.number.int({ min: 1900, max: 2020 }).toString(),
  abbrevation: faker.lorem.word(),
  webPage: faker.internet.url(),
};

export class RegisteredCommunityProfilePage extends ProfilePage {
  public inputData: typeof inputData;

  constructor(page: Page) {
    super(page, {
      inputData: inputData,
      deleteBankAccountButtonLocator: page.getByRole('group', { name: 'Yhteisön pankkitili' }).getByRole('button'),
    });
    this.inputData = inputData;
  }

  setupProfile = async () => {
    await selectRole(this.page, 'REGISTERED_COMMUNITY');
    await this.page.goto('/fi/oma-asiointi/hakuprofiili/muokkaa');

    await expect(this.page.getByText('Yhteisön tietoja ei löytynyt järjestelmistä')).toBeHidden();

    // Delete any existing profile information
    const removeButtons = await this.page.getByRole('button', { name: 'Poista' }).all();
    removeButtons.forEach(async (removeButton) => await removeButton.click());

    // Basic info
    await this.page.getByLabel('Perustamisvuosi').fill('1950');
    await this.page.getByLabel('Yhteisön lyhenne').fill('ABC');
    await this.page.getByLabel('Verkkosivujen osoite').fill('www.example.org');
    await this.page.getByRole('textbox', { name: 'Kuvaus yhteisön toiminnan tarkoituksesta' }).fill('kdsjgksdjgkdsjgkidsdgs');

    // Address
    await this.page.getByRole('button', { name: 'Lisää osoite' }).click();
    await this.page.getByLabel('Katuosoite').fill('Testiosoite 123');
    await this.page.getByLabel('Postinumero').fill('00100');
    await this.page.getByLabel('Toimipaikka').fill('Helsinki');

    // Contact Person
    await this.page.getByRole('button', { name: 'Lisää vastuuhenkilö' }).click();
    await this.page.getByLabel('Nimi').fill('Testi Testityyppi');
    await this.page.getByLabel('Rooli').selectOption('2'); // 2: Yhteyshenkilö
    await this.page.getByLabel('Sähköpostiosoite').fill('test@example.org');
    await this.page.getByLabel('Puhelinnumero').fill('040123123123');

    // Bank account
    await this.page.getByRole('button', { name: 'Lisää pankkitili' }).click();
    await this.page.getByLabel('Suomalainen tilinumero IBAN-muodossa').last().fill(TEST_IBAN);
    await uploadFile(this.page, this.page.getByText('Lisää tiedosto'));

    await this.page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
  };

  updateInfo = async () => {
    await this.page.getByRole('link', { name: 'Muokkaa yhteisön tietoja' }).click();

    // Check for required fields
    await expect.soft(this.page.getByLabel('Katuosoite')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Postinumero')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Toimipaikka')).toHaveAttribute('required');
    await expect.soft(this.page.locator('#edit-businesspurposewrapper-businesspurpose')).toHaveAttribute('required');

    // Fill new info and submit
    await this.page.getByLabel('Perustamisvuosi').fill(this.inputData.year);
    await this.page.getByLabel('Yhteisön lyhenne').fill(this.inputData.abbrevation);
    await this.page.getByLabel('Verkkosivujen osoite').fill(this.inputData.webPage);
    await this.page.locator('#edit-businesspurposewrapper-businesspurpose').fill(this.inputData.description);
    await this.page.getByLabel('Katuosoite').fill(this.inputData.streetAddress);
    await this.page.getByLabel('Postinumero').fill(this.inputData.zipCode);
    await this.page.getByLabel('Toimipaikka').fill(this.inputData.city);
    await this.page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
    await this.checkThatNewValuesAreVisible();
  };
}
