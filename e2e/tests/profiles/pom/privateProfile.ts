import { type Page, expect } from '@playwright/test';
import { ProfilePage } from './profilePage';
import { faker } from '@faker-js/faker';
import { selectRole } from '../../../utils/role';
import { uploadFile } from '../../../utils/upload';
import { TEST_IBAN } from '../../../utils/test_data';

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

  checkOmaAsiointiPage = async () => {
    await this.page.goto('/fi/oma-asiointi');

    // Headings
    await expect.soft(this.page.getByRole('heading', { name: 'Tietoa avustuksista ja ohjeita hakijalle' })).toBeVisible();
    await expect.soft(this.page.getByRole('heading', { name: 'Keskeneräiset hakemukset' })).toBeVisible();
    await expect.soft(this.page.getByRole('heading', { name: 'Lähetetyt hakemukset' })).toBeVisible();

    // Search controls
    await expect.soft(this.page.getByLabel('Etsi hakemusta')).toBeVisible();
    await expect.soft(this.page.getByRole('button', { name: 'Etsi hakemusta' })).toBeEnabled();
    await expect.soft(this.page.getByLabel('Näytä vain käsittelyssä olevat hakemukset')).toBeVisible();
    await expect.soft(this.page.getByLabel('Järjestä')).toBeVisible();
  };

  checkRequiredFields = async () => {
    await expect.soft(this.page.getByLabel('Katuosoite')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Postinumero')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Toimipaikka')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Puhelinnumero')).toHaveAttribute('required');
  };

  setupProfile = async () => {
    await selectRole(this.page, 'PRIVATE_PERSON');
    await this.goToEditPage();

    await this.checkRequiredFields();

    await this.page.getByLabel('Katuosoite').fill(this.inputData.streetAddress);
    await this.page.getByLabel('Postinumero').fill(this.inputData.zipCode);
    await this.page.getByLabel('Toimipaikka').fill(this.inputData.city);
    await this.page.getByLabel('Puhelinnumero').fill(this.inputData.phoneNumber);

    await this.page.getByRole('button', { name: 'Lisää pankkitili' }).click();
    await this.page.getByLabel('Suomalainen tilinumero IBAN-muodossa').last().fill(TEST_IBAN);
    await uploadFile(this.page, this.page.getByText('Lisää tiedosto'));
    await this.page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
    await this.checkThatNewValuesAreVisible();
  };
}
