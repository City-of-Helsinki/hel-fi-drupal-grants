import { expect, test } from '@playwright/test';


test.beforeEach(async ({ page }) => {
    await page.goto('/fi/etsi-avustusta');
});


test('has title', async ({ page }) => {
    const pageTitle = await page.title()
    expect(pageTitle).toContain('Etsi avustusta')
});

test('contains header', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Etsi avustusta' })).toBeVisible()
});

test('search filter fields are visible', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Rajaa hakua' })).toBeVisible()
    await expect(page.getByText('Kohderyhmä')).toBeVisible()
    await expect(page.getByText('Avustuslaji')).toBeVisible()
    await expect(page.getByText('Avustuksen hakija')).toBeVisible()
    await expect(page.getByText('Hakusana')).toBeVisible()
    await expect(page.getByLabel('Näytä vain haettavissa olevat avustukset')).toBeVisible();
});

test('search results are initially visible', async ({ page }) => {
    const resultCount = await page.locator(".application_search--link").count()
    expect(resultCount).toBeTruthy()
});

test('can search for grants', async ({ page }) => {
    const inputField = page.getByPlaceholder('Etsi nimellä tai hakusanalla, esim toiminta-avustus')
    await inputField.fill('avustus');

    await page.getByRole('button', { name: 'Etsi' }).click();

    // results are visible
    const resultCount = await page.locator(".application_search--link").count()
    expect(resultCount).toBeGreaterThan(0)
});

test('invalid search returns no results', async ({ page }) => {
    const inputField = page.getByPlaceholder('Etsi nimellä tai hakusanalla, esim toiminta-avustus')
    await inputField.fill('xxxxxx');


    await page.getByRole('button', { name: 'Etsi' }).click();

    // no grants should be found
    const resultCount = await page.locator(".application_search--link").count()
    expect(resultCount).toBeFalsy()
});

test('search result link can be opened', async ({ page }) => {
    const searchResultLinks = page.locator(".application_search--link")
    const firstLink = searchResultLinks.first()

    const firstLinkTitle = await firstLink.locator("h3").textContent()
    await firstLink.click()
    const pageContent = await page.locator("main").textContent()
    expect(pageContent).toContain("Myöntämisperusteet")
});
