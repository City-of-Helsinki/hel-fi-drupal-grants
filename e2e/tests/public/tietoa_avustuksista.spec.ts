import { expect, test } from '@playwright/test';


test.beforeEach(async ({ page }) => {
    await page.goto('/fi/tietoa-avustuksista');
});


test('has title', async ({ page }) => {
    const pageTitle = await page.title()
    expect(pageTitle).toContain('Tietoa avustuksista')
});


test('has hero', async ({ page }) => {
    const heroElement = page.locator(".hero")
    await expect(heroElement).toContainText(["Tietoa avustuksista"])
    await expect(heroElement).toContainText(["Tältä sivulta löydät tietoa erilaisista avustuksista"])

});


test('contains links to sections', async ({ page }) => {
    const linkNames = [
        "Kasvatus ja koulutus",
        "Kulttuuri ja vapaa-aika",
        "Muut avustukset"
    ]

    for (const name of linkNames) await expect(page.getByRole('link', { name })).toBeEnabled()
});


test('has all sections', async ({ page }) => {
    const sectionIds = [
        "#kasvatus-ja-koulutus-2",
        "#kulttuuri-ja-vapaa-aika-2",
        "#asukasosallisuuden-avustukset",
        "#muut-avustukset-2"
    ]

    for (const item of sectionIds) await expect(page.locator(item)).toBeVisible()
});


test('contains links to service pages', async ({ page }) => {
    const amountOfLinks = await page.locator(".list-of-links__item").count()
    expect(amountOfLinks).toBeTruthy()
});
