import { faker } from '@faker-js/faker';
import { Page, expect, test as setup } from '@playwright/test';
import { AUTH_FILE_PATH } from '../../utils/constants';
import { acceptCookies, login, selectRole, uploadBankConfirmationFile } from '../../utils/helpers';
import { TEST_IBAN } from '../../utils/test_data';


setup.setTimeout(180 * 1000);

setup('Setup profiles', async ({ page }) => {
    await setup.step('Log in', async () => {
        await login(page);
        await acceptCookies(page);
    });

    await setup.step('Private person', async () => await setupUserProfile(page));
    await setup.step('Unregistered community', async () => await setupUnregisteredCommunity(page));
    await setup.step('Registered community', async () => await setupCompanyProfile(page));

    await page.context().storageState({ path: AUTH_FILE_PATH });
});


const setupCompanyProfile = async (page: Page) => {
    await selectRole(page, 'REGISTERED_COMMUNITY')
    await page.goto('/fi/oma-asiointi/hakuprofiili/muokkaa')

    // Basic info
    await page.getByLabel('Perustamisvuosi').fill('1950');
    await page.getByLabel('Yhteisön lyhenne').fill('ABC');
    await page.getByLabel('Verkkosivujen osoite').fill('www.example.org');
    await page.getByRole('textbox', { name: 'Kuvaus yhteisön toiminnan tarkoituksesta' }).fill('kdsjgksdjgkdsjgkidsdgs');

    // Address
    await page.getByRole('button', { name: 'Lisää osoite' }).click();
    await page.getByLabel('Katuosoite').fill('Testiosoite 123');
    await page.getByLabel('Postinumero').fill('00100');
    await page.getByLabel('Toimipaikka').fill('Helsinki');

    // Contact Person
    await page.getByRole('button', { name: 'Lisää vastuuhenkilö' }).click();
    await page.getByLabel('Nimi').fill('Testi Testityyppi');
    await page.getByLabel('Rooli').selectOption('2'); // 2: Yhteyshenkilö
    await page.getByLabel('Sähköpostiosoite').fill('test@example.org');
    await page.getByLabel('Puhelinnumero').fill('040123123123');

    // Bank account
    await page.getByRole('button', { name: 'Lisää pankkitili' }).click();
    await page.getByLabel('Suomalainen tilinumero IBAN-muodossa').fill(TEST_IBAN);
    await uploadBankConfirmationFile(page, 'input[type="file"]')

    await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
};

const setupUnregisteredCommunity = async (page: Page) => {
    await page.goto('/fi/asiointirooli-valtuutus');

    // Add new unregistered community
    await page.locator('#edit-unregistered-community-selection').selectOption('new');
    await page.getByRole('button', { name: 'Lisää uusi Rekisteröitymätön yhteisö tai ryhmä' }).click();

    // Fill form
    const communityName = faker.lorem.word()
    const personName = faker.person.fullName()
    const email = faker.internet.email()
    const phoneNumber = faker.phone.number()
    await page.getByRole('textbox', { name: 'Yhteisön tai ryhmän nimi' }).fill(communityName);
    await page.getByLabel('Suomalainen tilinumero IBAN-muodossa').fill(TEST_IBAN);
    await uploadBankConfirmationFile(page, '[name="files[bankAccountWrapper_0_bank_confirmationFile]"]')
    await page.getByLabel('Nimi', { exact: true }).fill(personName);
    await page.getByLabel('Sähköpostiosoite').fill(email);
    await page.getByLabel('Puhelinnumero').fill(phoneNumber);

    // Submit
    await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
    await expect(page.getByText('Profiilitietosi on tallennettu')).toBeVisible()
}

const setupUserProfile = async (page: Page) => {
    const streetAddress = faker.location.streetAddress()
    const city = faker.location.city()
    const phoneNumber = faker.phone.number()

    await selectRole(page, 'PRIVATE_PERSON')
    await page.goto('/fi/oma-asiointi/hakuprofiili/muokkaa')

    await page.getByLabel('Katuosoite').fill(streetAddress);
    await page.getByLabel('Postinumero').fill('00100');
    await page.getByLabel('Toimipaikka').fill(city);
    await page.getByLabel('Puhelinnumero').fill(phoneNumber);

    await page.getByRole('button', { name: 'Lisää pankkitili' }).click();
    await page.getByLabel('Suomalainen tilinumero IBAN-muodossa').fill(TEST_IBAN);
    await uploadBankConfirmationFile(page, 'input[type="file"]')

    await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
};
