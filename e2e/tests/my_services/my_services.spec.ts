import { Page, expect, test } from '@playwright/test';
import { faker } from '@faker-js/faker';
import { selectRole } from '../../utils/helpers';

test.describe('oma asiointi', () => {
    test.beforeEach(async ({ page }) => {
        await selectRole(page, 'PRIVATE_PERSON')
        await page.goto("/fi/oma-asiointi")
    })

    test('check headings', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Tietoa avustuksista ja ohjeita hakijalle' })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Löydä avustuksesi' })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Tutustu yleisiin ohjeisiin' })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Keskeneräiset hakemukset' })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Lähetetyt hakemukset' })).toBeVisible()
    });

    test('controls for searching applications', async ({ page }) => {
        await expect(page.getByLabel('Etsi hakemusta')).toBeVisible()
        await expect(page.getByRole('button', { name: 'Etsi hakemusta' })).toBeEnabled()
        await expect(page.getByLabel('Näytä vain käsittelyssä olevat hakemukset')).toBeVisible()
        await expect(page.getByLabel('Järjestä')).toBeVisible()
    });

    test('application drafts are visible', async ({ page }) => {
        const applicationDraftCount = await page.locator('[id*="GRANTS"].draft').count()
        expect(applicationDraftCount).toBeTruthy()
    });

    test('sent applications are visible', async ({ page }) => {
        const receivedApplications = await page.locator('[id*="GRANTS"].received').count()
        expect(receivedApplications).toBeTruthy()
    });

    test('applications can be sorted by date', async ({ page }) => {
        let dates = await getDateValues(page)

        expect(isDescending(dates)).toBeTruthy()

        await page.getByLabel('Järjestä').selectOption('asc application-list__item--submitted');

        dates = await getDateValues(page)
        expect(isAscending(dates)).toBeTruthy()
    });


    test('search functionality', async ({ page }) => {
        const INPUT_DELAY = 50;

        let receivedApplicationCount = await getReceivedApplicationCount(page);

        if (receivedApplicationCount < 2) {
            test.skip()
        }

        const firstApplicationId = await page.locator('[id*="GRANTS"].received').first().getAttribute("id");

        if (!firstApplicationId) {
            test.fail()
            return;
        }

        await page.getByLabel('Etsi hakemusta').pressSequentially(firstApplicationId, { delay: INPUT_DELAY });

        receivedApplicationCount = await getReceivedApplicationCount(page);
        expect(receivedApplicationCount).toBe(1)
    });
})

test.describe('hakuprofiili', () => {
    test.beforeEach(async ({ page }) => {
        await selectRole(page, 'PRIVATE_PERSON')
        await page.goto("/fi/oma-asiointi/hakuprofiili")
    })

    test('contact information is visible', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Omat tiedot' })).toBeVisible()

        // Perustiedot
        await expect(page.getByRole('heading', { name: 'Perustiedot' })).toBeVisible()
        await expect(page.getByText('Etunimi')).toBeVisible()
        await expect(page.getByText('Sukunimi')).toBeVisible()
        await expect(page.getByText('Henkilötunnus')).toBeVisible()
        await expect(page.getByText('Sähköposti')).toBeVisible()
        await expect(page.getByRole('link', { name: 'Siirry Helsinki-profiiliin päivittääksesi tietoja' })).toBeVisible()

        // Omat yhteystiedot
        await expect(page.getByRole('heading', { name: 'Omat yhteystiedot' })).toBeVisible()
        await expect(page.getByText('Osoite')).toBeVisible()
        await expect(page.getByText('Puhelinnumero')).toBeVisible()
        await expect(page.getByText('Tilinumerot')).toBeVisible()
        await expect(page.getByRole('link', { name: 'Muokkaa omia tietoja' })).toBeVisible()
    });

    test('contact information can be updated', async ({ page }) => {
        const newStreetAddress = faker.location.streetAddress();
        const newPostalCode = faker.location.zipCode("#####");
        const newCity = faker.location.city();
        const newPhone = faker.phone.number()

        await page.getByRole('link', { name: 'Muokkaa omia tietoja' }).click();

        // Fill new info and submit
        await page.getByLabel('Katuosoite').fill(newStreetAddress);
        await page.getByLabel('Postinumero').fill(newPostalCode);
        await page.getByLabel('Toimipaikka').fill(newCity);
        await page.getByLabel('Puhelinnumero').fill(newPhone);
        await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();

        // Profile info contains the new data
        const profileInfo = page.locator(".grants-profile--extrainfo")
        const profileInfoText = await profileInfo.textContent();

        ([newStreetAddress, newPostalCode, newCity, newPhone]).forEach(element => {
            expect(profileInfoText).toContain(element)
        });
    });

})

const isAscending = (dates: Date[]) => dates.every((x, i) => i === 0 || x >= dates[i - 1]);
const isDescending = (dates: Date[]) => dates.every((x, i) => i === 0 || x <= dates[i - 1]);

const getDateValues = async (page: Page) => {
    const receivedApplications = await page.locator('[id*="GRANTS"].received').locator(".application-list__item--submitted").all()

    const datePromises = receivedApplications.map(async a => {
        const innerText = await a.innerText()
        const trimmedText = innerText.trim()
        return new Date(trimmedText)
    })

    const dates = await Promise.all(datePromises)
    return dates;
}

const getReceivedApplicationCount = async (page: Page) => await page.locator('[id*="GRANTS"].received').count();
