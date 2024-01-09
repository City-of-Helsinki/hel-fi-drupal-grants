import { type Page, expect } from '@playwright/test';
import { ProfilePage } from './profilePage';
import { faker } from '@faker-js/faker';
import { TEST_IBAN } from '../../../utils/test_data';
import { uploadFile } from '../../../utils/upload';

const inputData = {
  companyName: faker.company.name(),
  personName: faker.person.fullName(),
  phoneNumber: faker.phone.number(),
  email: faker.internet.email(),
  streetAddress: faker.location.streetAddress(),
  zipCode: faker.location.zipCode('#####'),
  city: faker.location.city(),
};

export class UnregisteredCommunityProfilePage extends ProfilePage {
  public inputData: typeof inputData;

  constructor(page: Page) {
    super(page, {
      inputData: inputData,
      deleteBankAccountButtonLocator: page.getByRole('group', { name: 'Yhteisön tai ryhmän pankkitili' }).getByRole('button'),
    });
    this.inputData = inputData;
  }

  createCommunity = async () => {
    await this.page.goto('/fi/asiointirooli-valtuutus');

    // Add new unregistered community
    await this.page.locator('#edit-unregistered-community-selection').selectOption('new');
    await this.page.getByRole('button', { name: 'Lisää uusi Rekisteröitymätön yhteisö tai ryhmä' }).click();

    // Fill form
    const communityName = faker.lorem.word();
    const personName = faker.person.fullName();
    const email = faker.internet.email();
    const phoneNumber = faker.phone.number();
    await this.page.getByRole('textbox', { name: 'Yhteisön tai ryhmän nimi' }).fill(communityName);
    await this.page.getByLabel('Suomalainen tilinumero IBAN-muodossa').last().fill(TEST_IBAN);
    await uploadFile(this.page, this.page.getByText('Lisää tiedosto'));
    await this.page.getByLabel('Nimi', { exact: true }).fill(personName);
    await this.page.getByLabel('Sähköpostiosoite').fill(email);
    await this.page.getByLabel('Puhelinnumero').fill(phoneNumber);

    // Submit
    await this.page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
    await expect(this.page.getByText('Profiilitietosi on tallennettu')).toBeVisible();
  };

  checkRequiredFields = async () => {
    await expect.soft(this.page.locator('#edit-companynamewrapper-companyname')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Katuosoite')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Postinumero')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Toimipaikka')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Sähköpostiosoite')).toHaveAttribute('required');
    await expect.soft(this.page.getByLabel('Puhelinnumero')).toHaveAttribute('required');
  };

  checkHeadings = async () => {
    await expect.soft(this.page.getByRole('heading', { name: 'Yhteisön tai ryhmän tiedot', exact: true })).toBeVisible();
    await expect.soft(this.page.getByRole('heading', { name: 'Yhteisön tai ryhmän tiedot avustusasioinnissa' })).toBeVisible();
    await expect.soft(this.page.getByText('Yhteisön tai ryhmän nimi')).toBeVisible();
    await expect.soft(this.page.getByText('Osoitteet')).toBeVisible();
    await expect.soft(this.page.getByText('Tilinumerot')).toBeVisible();
    await expect.soft(this.page.getByText('Toiminnasta vastaavat henkilöt')).toBeVisible();
  };

  updateInfo = async () => {
    await this.page.getByRole('link', { name: 'Muokkaa yhteisön tietoja' }).click();

    await this.checkRequiredFields();

    // Fill new info and submit
    await this.page.locator('#edit-companynamewrapper-companyname').fill(this.inputData.companyName);
    await this.page.getByLabel('Katuosoite').fill(this.inputData.streetAddress);
    await this.page.getByLabel('Postinumero').fill(this.inputData.zipCode);
    await this.page.getByLabel('Toimipaikka').fill(this.inputData.city);
    await this.page.getByLabel('Nimi', { exact: true }).fill(this.inputData.personName);
    await this.page.getByLabel('Rooli').selectOption({ label: 'Vastuuhenkilö' });
    await this.page.getByLabel('Sähköpostiosoite').fill(this.inputData.email);
    await this.page.getByLabel('Puhelinnumero').fill(this.inputData.phoneNumber);
    await this.page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
    await this.checkThatNewValuesAreVisible();
  };

  checkRequirementForOfficial = async () => {
    await this.page.goto('fi/oma-asiointi/hakuprofiili/muokkaa');
    await this.page.locator('#edit-officialwrapper-0-official-deletebutton').click();
    await expect(this.page.locator('#edit-officialwrapper-0-official')).not.toBeVisible();
    await this.page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
    await expect(this.page.getByText('Sinun tulee lisätä vähintään yksi toiminnasta vastaava henkilö').first()).toBeVisible();
  };
}
