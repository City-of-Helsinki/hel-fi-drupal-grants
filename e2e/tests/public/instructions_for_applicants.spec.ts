import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
    await page.goto('/fi/ohjeita-hakijalle');
});


test('verify title', async ({ page }) => {
    await expect(page).toHaveTitle(/.*Ohjeita hakijalle/);
});

test('verify hero', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Ohjeita hakijalle' })).toBeVisible();
    await expect(page.getByText('Tältä sivulta löydät tietoa myönnettävistä avustuksista ja niiden hakemisesta')).toBeVisible();
});

test('table of contents', async ({ page }) => {
    const tableOfContents = page.locator('#helfi-toc-table-of-contents-list');

    await expect(tableOfContents.getByRole('link', { name: 'Tutustu myönnettäviin avustuksiin' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Yleistä tietoa avustuksista' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Miten avustushakemuksen tekeminen etenee?' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Avustushakemuksen täyttäminen' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Hakemuksen käsittely ja päätökset' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Avustusten maksaminen' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Avustusten käyttäminen ja käytön selvitys' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Lisää aiheesta' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Helsingin kaupungin kirjaamo' })).toBeEnabled();
});


test('Lisää aiheesta', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Lisää aiheesta' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Palvelun käyttöohjeet' })).toBeVisible();
});


test('Helsingin kaupungin kirjaamo', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Helsingin kaupungin kirjaamo' }).getByRole('link')).toBeVisible()
    await expect(page.getByText('Kirjaamon asiakaspalvelu palvelee')).toBeVisible();
});
