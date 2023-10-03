import { expect, test } from '@playwright/test';
import { faker } from '@faker-js/faker';
import { loginAsPrivatePerson } from '../utils/helpers';


test.describe('oma asiointi', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsPrivatePerson(page)
        await page.goto("/fi/oma-asiointi")
    })

    test('check headings', async ({ page }) => {
        // headings and texts
        await expect(page.getByRole('heading', { name: 'Tietoa avustuksista ja ohjeita hakijalle' })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Roger Weberman' })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Tietoa avustuksista ja ohjeita hakijalle' })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Löydä avustuksesi' })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Tutustu yleisiin ohjeisiin' })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Keskeneräiset hakemukset' })).toBeVisible()
        await expect(page.getByRole('heading', { name: 'Lähetetyt hakemukset' })).toBeVisible()

        // search controls
        await expect(page.getByLabel('Näytä vain käsittelyssä olevat hakemukset')).toBeVisible()
        await expect(page.getByRole('button', { name: 'Etsi hakemusta' })).toBeEnabled()
    });
})

test.describe('hakuprofiili', () => {
    test.beforeEach(async ({ page }) => {
        await loginAsPrivatePerson(page);
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

        const profileInfo = page.locator(".grants-profile--extrainfo")

        await page.goto('https://hel-fi-drupal-grant-applications.docker.so/fi/oma-asiointi/hakuprofiili');
        await page.getByRole('link', { name: 'Muokkaa omia tietoja' }).click();

        // Fill new info and submit
        await page.getByLabel('Katuosoite').fill(newStreetAddress);
        await page.getByLabel('Postinumero').fill(newPostalCode);
        await page.getByLabel('Toimipaikka').fill(newCity);
        await page.getByLabel('Puhelinnumero').fill(newPhone);
        await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();

        // Profile info contains the new data
        const profileInfoText = await profileInfo.textContent();

        ([newStreetAddress, newPostalCode, newCity, newPhone]).forEach(element => {
            expect(profileInfoText).toContain(element)
        });
    });

})