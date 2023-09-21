import { expect, test } from '@playwright/test';
import { BASE_URL } from '../test_data';


test.beforeEach(async ({ page }) => {
    await page.goto(BASE_URL);
});




test('instructions page accordion', async ({ page }) => {
    await page.getByRole('link', { name: 'Ohjeita hakijalle' }).first().click();

    await expect(page.getByText('Osaa avustuksista voi hakea vain kerran vuodessa, mutta joillekin avustuksille')).not.toBeVisible();
    await page.getByRole('button', { name: 'Avustusten hakuajat' }).first().click();
    await expect(page.getByText('Osaa avustuksista voi hakea vain kerran vuodessa, mutta joillekin avustuksille')).toBeVisible();

    await expect(page.getByText('Helsingin kaupungin avustusta voidaan myöntää oikeushenkilöille, kuten rekisterö')).not.toBeVisible();
    await page.getByRole('button', { name: 'Kuka voi saada avustusta?' }).first().click();
    await expect(page.getByText('Helsingin kaupungin avustusta voidaan myöntää oikeushenkilöille, kuten rekisterö')).toBeVisible();

    await expect(page.getByText('Avustettavan toiminnan tulee kohdistua pääosin helsinkiläisiin. Lisäksi avustuks')).not.toBeVisible();
    await page.getByRole('button', { name: 'Mihin voi saada avustusta?' }).first().click();
    await expect(page.getByText('Avustettavan toiminnan tulee kohdistua pääosin helsinkiläisiin. Lisäksi avustuks')).toBeVisible();
});
