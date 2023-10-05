import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
    await page.goto('/fi/tietoa-avustuksista');
});


test('verify title', async ({ page }) => {
    await expect(page).toHaveTitle(/.*Tietoa avustuksista/);
});


test('verify hero', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Tietoa avustuksista' })).toBeVisible();
    await expect(page.getByText('Tältä sivulta löydät tietoa erilaisista avustuksista.')).toBeVisible();
});

test('navigation links are enabled', async ({ page }) => {
    await expect(page.getByRole('link', { name: 'Kasvatus ja koulutus' })).toBeEnabled();
    await expect(page.getByRole('link', { name: 'Kulttuuri ja vapaa-aika' })).toBeEnabled();    
    await expect(page.getByRole('link', { name: 'Muut avustukset' })).toBeEnabled();
});

test('verify section links', async ({ page }) => {
    // Checking visibility of elements under "Kasvatus ja koulutus"
    await expect(page.locator('#kasvatus-ja-koulutus-2')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Iltapäivätoiminnan harkinnanvarainen lisäavustushakemus' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Toiminta-avustushakemus koululaisten iltapäivätoiminnan järjestäjille' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Yleisavustushakemus', exact: true })).toBeVisible();

    // Checking visibility of elements under "Kulttuuri ja vapaa-aika"
    await expect(page.locator('#kulttuuri-ja-vapaa-aika-2')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Kulttuurin avustukset' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Liikunnan avustukset' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Nuorisotoiminnan avustukset' })).toBeVisible();

    // Checking visibility of elements under "Asukasosallisuuden avustukset"
    await expect(page.locator('#asukasosallisuuden-avustukset')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Asukasosallisuuden avustukset' })).toBeVisible();

    // Checking visibility of elements under "Muut Avustukset"
    await expect(page.locator('#muut-avustukset-2')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Hyvinvoinnin ja terveyden edistämisen sekä sosiaali-, terveys- ja pelastustoimen avustukset' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Kaupunginhallituksen yleisavustus' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Työllisyysavustushakemus' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Ympäristötoimen yleisavustushakemus' })).toBeVisible();
});

test('news section is visible', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Ajankohtaista avustuksista' })).toBeVisible();
});
