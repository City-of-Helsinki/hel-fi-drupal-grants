import { type Page, expect } from '@playwright/test';
import { ProfilePage } from './profilePage';
import { faker } from '@faker-js/faker';

const inputData = {
  phoneNumber: faker.phone.number(),
  streetAddress: faker.location.streetAddress(),
  zipCode: faker.location.zipCode('#####'),
  city: faker.location.city(),
};

export class PrivatePersonProfilePage extends ProfilePage {
  public inputData: typeof inputData;

  constructor(page: Page) {
    super(page, {
      inputData: inputData,
    });
    this.inputData = inputData;
  }

  checkRequiredFields = async () => {
    await expect.soft(this.page.getByLabel('Katuosoite')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Postinumero')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Toimipaikka')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Puhelinnumero')).toHaveAttribute('required');
  };

  updateInfo = async () => {
    await this.page.getByRole('link', { name: 'Muokkaa omia tietoja' }).click();

    await this.checkRequiredFields();

    // Fill new info and submit
    await this.page.getByLabel('Katuosoite').fill(this.inputData.streetAddress);
    await this.page.getByLabel('Postinumero').fill(this.inputData.zipCode);
    await this.page.getByLabel('Toimipaikka').fill(this.inputData.city);
    await this.page.getByLabel('Puhelinnumero').fill(this.inputData.phoneNumber);
    await this.page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
    await this.checkThatNewValuesAreVisible();
  };
}
