import { expect, test } from '@playwright/test';
import { faker } from '@faker-js/faker';
import { acceptCookies, login, selectRole } from '../../utils/helpers';

test.describe('oma asiointi', () => {
    test.beforeEach(async ({ page }) => {
        await selectRole(page, 'PRIVATE_PERSON')
        await page.goto("/fi/oma-asiointi")
    })

    test('check headings', async ({ page }) => {
        // headings and texts
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

    test.describe('private person', () => {
        test.beforeEach(async ({ page }) => {
            await selectRole(page, 'PRIVATE_PERSON')
            await acceptCookies(page);
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

        test('Helsinki-profiili link opens a new tab', async ({ page, context }) => {
            const linkToHelsinkiProfile = page.getByRole('link', { name: "Siirry Helsinki-profiiliin" });
            await expect(linkToHelsinkiProfile).toBeVisible()

            const timeoutPromise = new Promise(resolve => setTimeout(() => resolve(null), 2000));
            const [newPagePromise] = [context.waitForEvent('page'), linkToHelsinkiProfile.click()];

            const linkOpensToNewTab = await Promise.race([newPagePromise, timeoutPromise]);

            expect(linkOpensToNewTab).toBeTruthy()
        });


        test('required fields', async ({ page }) => {
            await page.goto("fi/oma-asiointi/hakuprofiili/muokkaa");

            const labels = ['Katuosoite', 'Postinumero', 'Toimipaikka', 'Puhelinnumero', 'Sähköpostiosoite'];

            for (const label of labels) {
                const isRequired = await page.getByLabel(label).getAttribute("required");
                expect(isRequired).toBeTruthy();
            }
        });

        test('a bank account is required', async ({ page }) => {
            await page.goto("fi/oma-asiointi/hakuprofiili/muokkaa");

            await Promise.all([
                page.waitForResponse(response => response.status() === 200),
                page.getByRole('button', { name: 'Poista' }).click()
            ]);

            await expect(page.getByText("Tilinumero ja vahvistusliite poistettiin")).toBeVisible()

            await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
            const warningText = page.getByLabel("Notification").getByText("Sinun tulee lisätä vähintään yksi pankkitili")
            await expect(warningText).toBeVisible()
        });
    })

    test.describe("registered community", () => {
        test.beforeEach(async ({ page }) => {
            await selectRole(page, 'REGISTERED_COMMUNITY')
            await acceptCookies(page);
            await page.goto("/fi/oma-asiointi/hakuprofiili")
        })

        test('contact information is visible', async ({ page }) => {
            await expect(page.getByRole('heading', { name: 'Yhteisön tiedot', exact: true })).toBeVisible()

            await expect(page.getByRole('heading', { name: 'Yhteisön tiedot avustusasioinnissa' })).toBeVisible()
            await expect(page.getByText('Perustamisvuosi')).toBeVisible()
            await expect(page.getByText('Yhteisön lyhenne')).toBeVisible()
            await expect(page.getByText('Verkkosivujen osoite')).toBeVisible()
            await expect(page.getByText('Toiminnan tarkoitus')).toBeVisible()
            await expect(page.getByText('Osoitteet')).toBeVisible()
            await expect(page.getByText('Toiminnasta vastaavat henkilöt')).toBeVisible()
            await expect(page.getByText('Tilinumerot')).toBeVisible()
        });

        test('contact information can be updated', async ({ page }) => {
            const description = faker.lorem.words(14)
            const streetAddress = faker.location.streetAddress()
            const zipCode = faker.location.zipCode("#####");
            const city = faker.location.city();

            const year = faker.number.int({ min: 1900, max: 2020 }).toString()
            const abbrevation = faker.lorem.word()
            const webPage = faker.internet.url()

            await page.getByRole('link', { name: 'Muokkaa yhteisön tietoja' }).click();

            // Fill new info and submit
            await page.getByLabel('Perustamisvuosi').fill(year);
            await page.getByLabel('Yhteisön lyhenne').fill(abbrevation);
            await page.getByLabel('Verkkosivujen osoite').fill(webPage);
            await page.locator("#edit-businesspurposewrapper-businesspurpose").fill(description);
            await page.getByLabel('Katuosoite').fill(streetAddress);
            await page.getByLabel('Postinumero').fill(zipCode);
            await page.getByLabel('Toimipaikka').fill(city);
            await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();

            // Profile info contains the new data
            const profileInfo = page.locator(".grants-profile--extrainfo")
            const profileInfoText = await profileInfo.textContent();

            ([description, streetAddress, zipCode, city, year, abbrevation, webPage]).forEach(element => {
                expect(profileInfoText).toContain(element)
            });
        });

        test('a bank account is required', async ({ page }) => {
            await page.goto("fi/oma-asiointi/hakuprofiili/muokkaa");

            await Promise.all([
                page.waitForResponse(response => response.status() === 200),
                page.getByRole('group', { name: 'Yhteisön pankkitili' }).getByRole('button').click()
            ]);

            await expect(page.getByText("Tilinumero ja vahvistusliite poistettiin")).toBeVisible()

            await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
            const warningText = page.getByLabel("Notification").getByText("Sinun tulee lisätä vähintään yksi pankkitili")
            await expect(warningText).toBeVisible()
        });

        // TODO: Test if applications can be submitted without an official in the registered community
        test.skip('a community official is required', async ({ page }) => {
            await page.goto("fi/oma-asiointi/hakuprofiili/muokkaa");

            await Promise.all([
                page.waitForResponse(response => response.status() === 200),
                page.getByRole('group', { name: 'Yhteisön vastuuhenkilö' }).getByRole('button').click()
            ]);

            await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
        });

        test('required fields', async ({ page }) => {
            await page.goto("fi/oma-asiointi/hakuprofiili/muokkaa");

            const labels = ['Katuosoite', 'Postinumero', 'Toimipaikka'];

            for (const label of labels) {
                const isRequired = await page.getByLabel(label).getAttribute("required");
                expect(isRequired).toBeTruthy();
            }

            const streetAddressIsRequired = await page.locator("#edit-businesspurposewrapper-businesspurpose").getAttribute("required")
            expect(streetAddressIsRequired).toBeTruthy()
        });
    })

    test.describe("unregistered community", () => {
        test.beforeEach(async ({ page }) => {
            await selectRole(page, 'UNREGISTERED_COMMUNITY')
            await acceptCookies(page);
            await page.goto("/fi/oma-asiointi/hakuprofiili")
        })

        test('contact information is visible', async ({ page }) => {
            await expect(page.getByRole('heading', { name: 'Yhteisön tai ryhmän tiedot', exact: true })).toBeVisible()

            await expect(page.getByRole('heading', { name: 'Yhteisön tai ryhmän tiedot avustusasioinnissa' })).toBeVisible()
            await expect(page.getByText('Yhteisön tai ryhmän nimi')).toBeVisible()
            await expect(page.getByText('Osoitteet')).toBeVisible()
            await expect(page.getByText('Tilinumerot')).toBeVisible()
            await expect(page.getByText('Toiminnasta vastaavat henkilöt')).toBeVisible()
        });

        test('contact information can be updated', async ({ page }) => {
            const companyName = faker.company.name()
            const personName = faker.person.fullName()
            const phoneNumber = faker.phone.number()
            const email = faker.internet.email()
            const streetAddress = faker.location.streetAddress()
            const zipCode = faker.location.zipCode("#####");
            const city = faker.location.city();

            await page.getByRole('link', { name: 'Muokkaa yhteisön tietoja' }).click();

            // Fill new info and submit
            await page.locator("#edit-companynamewrapper-companyname").fill(companyName);
            await page.getByLabel('Katuosoite').fill(streetAddress);
            await page.getByLabel('Postinumero').fill(zipCode);
            await page.getByLabel('Toimipaikka').fill(city);

            await page.getByLabel('Nimi', { exact: true }).fill(personName);
            await page.getByLabel('Rooli').selectOption({ label: "Vastuuhenkilö" });
            await page.getByLabel('Sähköpostiosoite').fill(email);
            await page.getByLabel('Puhelinnumero').fill(phoneNumber);

            await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();

            // Profile info contains the new data
            const profileInfo = page.locator(".grants-profile--extrainfo")
            const profileInfoText = await profileInfo.textContent();

            ([companyName, streetAddress, zipCode, city, personName, email, phoneNumber]).forEach(element => {
                expect(profileInfoText).toContain(element)
            });
        });

        test('required fields', async ({ page }) => {
            await page.goto("fi/oma-asiointi/hakuprofiili/muokkaa");

            const labels = ['Katuosoite', 'Postinumero', 'Toimipaikka'];

            for (const label of labels) {
                const isRequired = await page.getByLabel(label).getAttribute("required");
                expect(isRequired).toBeTruthy();
            }

            const streetAddressIsRequired = await page.locator("#edit-companynamewrapper-companyname").getAttribute("required")
            expect(streetAddressIsRequired).toBeTruthy()
        });

        test('a bank account is required', async ({ page }) => {
            await page.goto("fi/oma-asiointi/hakuprofiili/muokkaa");

            await Promise.all([
                page.waitForResponse(response => response.status() === 200),
                page.getByRole('group', { name: 'Yhteisön tai ryhmän pankkitili' }).getByRole('button', { name: 'Poista' }).click()
            ]);

            await expect(page.getByText("Tilinumero ja vahvistusliite poistettiin")).toBeVisible()

            await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
            const warningText = page.getByLabel("Notification").getByText("Sinun tulee lisätä vähintään yksi pankkitili")
            await expect(warningText).toBeVisible()
        });

        test('a new group can be created and deleted', async ({ page }) => {
            const iban = "FI3147372044000048"
            const communityName= faker.lorem.word()
            const personName = faker.person.fullName()
            const email = faker.internet.email()
            const phoneNumber = faker.phone.number()

            await page.goto("/fi/asiointirooli-valtuutus");

            await page.locator('#edit-unregistered-community-selection').selectOption('new');
            await page.getByRole('button', { name: 'Lisää uusi Rekisteröitymätön yhteisö tai ryhmä' }).click();
            await page.getByRole('textbox', { name: 'Yhteisön tai ryhmän nimi*' }).fill(communityName);
            await page.getByLabel('Suomalainen tilinumero IBAN-muodossa').fill(iban);

            await Promise.all([
                page.waitForResponse(response => response.status() === 200),
                page.locator('#edit-bankaccountwrapper-0-bank-confirmationfile-upload').setInputFiles('utils/test.pdf')
            ]);

            await expect(page.locator('[id*=edit-bankaccountwrapper-0-bank-confirmationfile-remove-button]')).toBeVisible()
            
            await page.getByLabel('Sähköpostiosoite').fill(email);
            await page.getByLabel('Nimi', { exact: true }).fill(personName);
            await page.getByLabel('Puhelinnumero').fill(phoneNumber);

            // Submit
            await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
            await expect(page.getByText('Profiilitietosi on tallennettu')).toBeVisible()
            
            // Remove profile
            await page.getByRole('link', { name: 'Poista asiointiprofiili' }).click();
            await page.getByRole('button', { name: 'Vahvista' }).click();
            await expect(page.getByText('Yhteisö poistettu')).toBeVisible()
        });
    })
})