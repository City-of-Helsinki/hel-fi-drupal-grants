import { expect, test } from '@playwright/test';
import { selectRole } from '../../utils/role';

test('Oma asiointi', async ({ page }) => {
  await selectRole(page, 'PRIVATE_PERSON');
  await page.goto('/fi/oma-asiointi');

  // Headings
  await expect.soft(page.getByRole('heading', { name: 'Tietoa avustuksista ja ohjeita hakijalle' })).toBeVisible();
  await expect.soft(page.getByRole('heading', { name: 'Keskeneräiset hakemukset' })).toBeVisible();
  await expect.soft(page.getByRole('heading', { name: 'Lähetetyt hakemukset' })).toBeVisible();

  // Search controls
  await expect.soft(page.getByLabel('Etsi hakemusta')).toBeVisible();
  await expect.soft(page.getByRole('button', { name: 'Etsi hakemusta' })).toBeEnabled();
  await expect.soft(page.getByLabel('Näytä vain käsittelyssä olevat hakemukset')).toBeVisible();
  await expect.soft(page.getByLabel('Järjestä')).toBeVisible();
});
