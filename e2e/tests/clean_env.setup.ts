import { Page, expect, test as setup } from '@playwright/test';
import { AUTH_FILE, acceptCookies, login, selectRole } from '../utils/helpers';
import { TEST_IBAN } from '../utils/test_data';
import path from 'path';


setup.setTimeout(60000)

type ATVDocument = {
    id: string;
    type: string;
    service: string;
    transaction_id: string;
}

type PaginatedDocumentlist = {
    count: number;
    next?: string;
    previous?: string;
    results: ATVDocument[]
}

const ATV_API_KEY = process.env.ATV_API_KEY;
const ATV_BASE_URL = process.env.ATV_BASE_URL;
const APP_ENV: string = process.env.APP_ENV || '';


setup.beforeAll(() => {
    expect(ATV_API_KEY).toBeTruthy()
    expect(ATV_BASE_URL).toBeTruthy()
    expect(APP_ENV).toBeTruthy()
})

setup('clean environment', async () => {

    if (APP_ENV.toUpperCase().startsWith("LOCAL")) {
        const url = `${ATV_BASE_URL}/v1/documents/?lookfor=appenv:${APP_ENV}&service_name=AvustushakemusIntegraatio`
        const headers = { headers: { 'X-API-KEY': ATV_API_KEY } }
        const res = await fetch(url, headers)

        const json: PaginatedDocumentlist = await res.json()
        const documentIds = json.results.filter(r => r.type === "grants_profile").map(r => r.id)

        let deletedDocumentsCount = 0;

        for (const id of documentIds) {
            const url = `${ATV_BASE_URL}/v1/documents/${id}`
            const headers = { method: 'DELETE', headers: { 'X-API-KEY': ATV_API_KEY } }

            const res = await fetch(url, headers)

            if (res.ok) deletedDocumentsCount += 1
        }
        console.log(`Deleted ${deletedDocumentsCount} documents from ATV`)
    };
});

setup('setup user and company profile', async ({ page }) => {
    await login(page);
    await acceptCookies(page);
    await setupUserProfile(page);
    await setupCompanyProfile(page);
    await page.context().storageState({ path: AUTH_FILE });
})

const setupCompanyProfile = async (page: Page) => {
    await selectRole(page, 'REGISTERED_COMMUNITY')
    await page.goto('/fi/oma-asiointi/hakuprofiili/muokkaa')

    // Basic info
    await page.getByLabel('Perustamisvuosi').click();
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
    await Promise.all([
        page.waitForResponse(r => r.status() === 200),
        page.locator('input[type="file"]').setInputFiles(path.join(__dirname, '../utils/test.pdf'))
    ])

    await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
}

const setupUserProfile = async (page: Page) => {
    await selectRole(page, 'PRIVATE_PERSON')
    await page.goto('/fi/oma-asiointi/hakuprofiili/muokkaa')

    await page.getByLabel('Katuosoite').fill('katuosoite');
    await page.getByLabel('Postinumero').fill('00100');
    await page.getByLabel('Toimipaikka').fill('hesa');
    await page.getByLabel('Puhelinnumero').fill('01230230023023');
    await page.getByLabel('Sähköpostiosoite').fill('email@example.org');

    await page.getByRole('button', { name: 'Lisää pankkitili' }).click();
    await page.getByLabel('Suomalainen tilinumero IBAN-muodossa').fill(TEST_IBAN);

    await Promise.all([
        page.waitForResponse(r => r.status() === 200),
        page.locator('input[type="file"]').setInputFiles(path.join(__dirname, '../utils/test.pdf'))
    ])

    await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
}
