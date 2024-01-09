import { type Page, expect } from '@playwright/test';
import { ProfilePage } from './profilePage';
import { faker } from '@faker-js/faker';

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
