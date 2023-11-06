import { expect, test } from '@playwright/test';


test.beforeEach(async ({ page }) => {
    await page.goto('/');
});

// Accordion removed from the frontpage
test.skip('instructions page accordion', async ({ page }) => {
    await page.getByRole('link', { name: 'Ohjeita hakijalle' }).first().click();

    await expect(page.getByText('Osaa avustuksista voi hakea vain kerran vuodessa, mutta joillekin avustuksille')).toBeHidden();
    await page.getByRole('button', { name: 'Avustusten hakuajat' }).first().click();
    await expect(page.getByText('Osaa avustuksista voi hakea vain kerran vuodessa, mutta joillekin avustuksille')).toBeVisible();

    await expect(page.getByText('Helsingin kaupungin avustusta voidaan myöntää oikeushenkilöille, kuten rekisterö')).toBeHidden();
    await page.getByRole('button', { name: 'Kuka voi saada avustusta?' }).first().click();
    await expect(page.getByText('Helsingin kaupungin avustusta voidaan myöntää oikeushenkilöille, kuten rekisterö')).toBeVisible();

    await expect(page.getByText('Avustettavan toiminnan tulee kohdistua pääosin helsinkiläisiin. Lisäksi avustuks')).toBeHidden();
    await page.getByRole('button', { name: 'Mihin voi saada avustusta?' }).first().click();
    await expect(page.getByText('Avustettavan toiminnan tulee kohdistua pääosin helsinkiläisiin. Lisäksi avustuks')).toBeVisible();
});
