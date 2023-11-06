import { faker } from '@faker-js/faker';
import { Locator, Page, expect, test } from '@playwright/test';
import { selectRole, setupUnregisteredCommunity } from '../utils/helpers';

test.describe('oma asiointi', () => {
    test.beforeEach(async ({ page }) => {
        await selectRole(page, 'REGISTERED_COMMUNITY');
        await page.goto("/fi/oma-asiointi");
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

    test('applications can be sorted by date', async ({ page }) => {
        let amountOfReceivedApplications = await getReceivedApplicationCount(page);
        test.skip(!amountOfReceivedApplications, "No received applications, skip testing sort functionality")

        let dates = await getDateValues(page)

        expect(isDescending(dates)).toBeTruthy()

        await page.getByLabel('Järjestä').selectOption('asc application-list__item--submitted');

        dates = await getDateValues(page)
        expect(isAscending(dates)).toBeTruthy()
    });


    test('search functionality', async ({ page }) => {
        let amountOfReceivedApplications = await getReceivedApplicationCount(page);
        test.skip(!amountOfReceivedApplications, "No received applications, skip testing search functionality")

        const INPUT_DELAY = 50;

        const firstApplicationId = await page.locator(receivedApplicationLocator).first().getAttribute("id");

        if (!firstApplicationId) return;

        await page.getByLabel('Etsi hakemusta').pressSequentially(firstApplicationId, { delay: INPUT_DELAY });

        const visibleApplications = await getReceivedApplicationCount(page);
        expect(visibleApplications).toBe(1)
    });
})

test.describe('hakuprofiili', () => {
    test.describe('private person', () => {
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
            await expect(page.getByRole('link', { name: 'Siirry Helsinki-profiiliin päivittääksesi sähköpostiosoitetta' })).toBeVisible()

            // Omat yhteystiedot
            await expect(page.getByRole('heading', { name: 'Omat yhteystiedot' })).toBeVisible()
            await expect(page.locator("#addresses").getByText('Osoite')).toBeVisible()
            await expect(page.locator("#phone-number").getByText('Puhelinnumero')).toBeVisible()
            await expect(page.locator("#officials-3").getByText('Tilinumerot')).toBeVisible()
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

            const labels = ['Katuosoite', 'Postinumero', 'Toimipaikka', 'Puhelinnumero'];

            for (const label of labels) {
                const isRequired = await page.getByLabel(label).getAttribute("required");
                expect(isRequired).toBeTruthy();
            }
        });

        test('a bank account is required', async ({ page }) => {
            const removeBankAccountButton = page.getByRole('button', { name: 'Poista' });
            await removeBankAccountAndCheckError(page, removeBankAccountButton);
        });
    });

    test.describe("registered community", () => {
        test.beforeEach(async ({ page }) => {
            await selectRole(page, 'REGISTERED_COMMUNITY')
            await page.goto("/fi/oma-asiointi/hakuprofiili")
        })

        test('contact information is visible', async ({ page }) => {
            await expect(page.getByRole('heading', { name: 'Yhteisön tiedot', exact: true })).toBeVisible()
            await expect(page.locator("#perustamisvuosi").getByText('Perustamisvuosi')).toBeVisible()
            await expect(page.locator("#yhteison-lyhenne").getByText('Yhteisön lyhenne')).toBeVisible()
            await expect(page.locator("#verkkosivujen-osoite").getByText('Verkkosivujen osoite')).toBeVisible()
            await expect(page.locator("#toiminna-tarkoitus").getByText('Toiminnan tarkoitus')).toBeVisible()
            await expect(page.locator("#addresses").getByText('Osoitteet')).toBeVisible()
            await expect(page.locator("#officials").getByText('Toiminnasta vastaavat henkilöt')).toBeVisible()
            await expect(page.locator("#officials-3").getByText('Tilinumerot')).toBeVisible()
            await expect(page.getByRole('link', { name: 'Muokkaa yhteisön tietoja' })).toBeVisible()
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
            const removeBankAccountButton = page.getByRole('group', { name: 'Yhteisön pankkitili' }).getByRole('button');
            await removeBankAccountAndCheckError(page, removeBankAccountButton);
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
    });

    test.describe("unregistered community", () => {
        test.beforeEach(async ({ page }) => {
            await selectRole(page, 'UNREGISTERED_COMMUNITY')
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

            const streetAddressIsRequired = await page.locator("#edit-companynamewrapper-companyname").getAttribute("required")
            expect(streetAddressIsRequired).toBeTruthy()

            const labels = [
                'Katuosoite',
                'Postinumero',
                'Toimipaikka',
                'Sähköpostiosoite',
                'Puhelinnumero'
            ];

            for (const label of labels) {
                const isRequired = await page.getByLabel(label).getAttribute("required");
                expect(isRequired).toBeTruthy();
            }

        });

        test('an official is required', async ({ page }) => {
            await page.goto("fi/oma-asiointi/hakuprofiili/muokkaa");

            await page.locator('#edit-officialwrapper-0-official-deletebutton').click();
            await expect(page.locator("#edit-officialwrapper-0-official")).not.toBeVisible();
            await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
            await expect(page.getByText('Sinun tulee lisätä vähintään yksi toiminnasta vastaava henkilö')).toBeVisible()
        });

        test('a bank account is required', async ({ page }) => {
            const removeBankAccountButton = page.getByRole('group', { name: 'Yhteisön tai ryhmän pankkitili' }).getByRole('button');
            await removeBankAccountAndCheckError(page, removeBankAccountButton);
        });

        test('a new group can be created and deleted', async ({ page }) => {
            await setupUnregisteredCommunity(page);
            await page.getByRole('link', { name: 'Poista asiointiprofiili' }).click();
            await page.getByRole('button', { name: 'Vahvista' }).click();
            await expect(page.getByText('Yhteisö poistettu')).toBeVisible()
        });
    });
})

const receivedApplicationLocator = '.application-list [data-status="RECEIVED"]';

const getReceivedApplicationCount = async (page: Page) => await page.locator(receivedApplicationLocator).count();

const isAscending = (dates: Date[]) => dates.every((x, i) => i === 0 || x >= dates[i - 1]);
const isDescending = (dates: Date[]) => dates.every((x, i) => i === 0 || x <= dates[i - 1]);

const getDateValues = async (page: Page) => {
    const receivedApplications = await page.locator(receivedApplicationLocator).locator(".application-list__item--submitted").all()

    const datePromises = receivedApplications.map(async a => {
        const innerText = await a.innerText()
        const trimmedText = innerText.trim()
        return new Date(trimmedText)
    })

    const dates = await Promise.all(datePromises)
    return dates;
}

const removeBankAccountAndCheckError = async (page: Page, deleteButtonLocator: Locator) => {
    const bankAccountSection = page.locator("#edit-bankaccountwrapper-0-bank");

    await page.goto("fi/oma-asiointi/hakuprofiili/muokkaa");
    await expect(bankAccountSection).toBeVisible();

    await Promise.all([
        page.waitForResponse(response => response.status() === 200),
        deleteButtonLocator.click()
    ]);

    await expect(bankAccountSection).not.toBeVisible();

    await page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
    const warningText = page.getByLabel("Notification").getByText("Sinun tulee lisätä vähintään yksi pankkitili")
    await expect(warningText).toBeVisible()
}
