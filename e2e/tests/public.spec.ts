import { expect, test } from '@playwright/test';

test('Frontpage', async ({ page }) => {
  await page.goto('/fi/avustukset');
  await expect(page.getByRole('heading', { name: 'Avustukset' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Kirjaudu' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Palaute', exact: true })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Katso kaikki ajankohtaiset' })).toBeVisible();

  await expect(page.getByRole('heading', { name: 'Ohjeita hakijalle' }).getByRole('link')).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Etsi avustusta' }).getByRole('link')).toBeVisible();
});

test('Change language', async ({ page }) => {
  await page.goto('/fi/avustukset');

  await page.getByRole('link', { name: 'Svenska' }).click();
  await expect(page.getByRole('heading', { name: 'Understöd', exact: true })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Bidragstjänsten' })).toBeVisible();

  await page.getByRole('link', { name: 'English' }).click();
  await expect(page.getByRole('heading', { name: 'Grants', exact: true })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Grants service' })).toBeVisible();

  await page.getByRole('link', { name: 'Suomi' }).click();
  await expect(page.getByRole('heading', { name: 'Avustukset' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Avustusasiointi', exact: true })).toBeVisible();
});

test('News', async ({ page }) => {
  await page.goto('/fi/uutiset');

  await expect(page.getByRole('heading', { name: 'Pääuutiset' })).toBeVisible();

  const pageTitle = await page.title();
  expect(pageTitle).toContain('Ajankohtaista avustuksista');

  const articleCount = await page.locator('#block-views-block-frontpage-news-main-news').getByRole('listitem').count();
  expect(articleCount).toBeGreaterThan(0);
});

test('Search page', async ({ page }) => {
  await page.goto('/fi/etsi-avustusta');

  // Heading
  await expect(page.getByRole('heading', { name: 'Etsi avustusta' })).toBeVisible();

  // Search controls
  await expect(page.getByRole('heading', { name: 'Rajaa hakua' })).toBeVisible();
  await expect(page.getByText('Kohderyhmä')).toBeVisible();
  await expect(page.getByText('Avustuslaji')).toBeVisible();
  await expect(page.getByText('Avustuksen hakija')).toBeVisible();
  await expect(page.getByText('Hakusana')).toBeVisible();
  await expect(page.getByLabel('Näytä vain haettavissa olevat avustukset')).toBeVisible();

  const amountOfSearchResults = await page.locator('.application_search--link').count();
  expect(amountOfSearchResults).toBeGreaterThan(1);
});
