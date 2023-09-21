import { test } from '@playwright/test';
import { TEST_SSN } from '../test_data';

test('can login through Tunnistamo', async ({ page }) => {
  // Navigate to the page
  await page.goto('/');

  // Login flow
  const loginLink = page.getByRole('link', { name: 'Kirjaudu' });
  await loginLink.click();

  const acceptCookiesButton = page.getByRole('button', { name: 'Hyväksy vain välttämättömät evästeet' });
  await acceptCookiesButton.click();

  const signInButton = page.getByRole('button', { name: 'Kirjaudu sisään' });
  await signInButton.click();

  const testIdpLink = page.getByRole('link', { name: 'Test IdP' });
  await testIdpLink.click();

  // Fill credentials and authenticate
  const credentialField = page.getByPlaceholder('210281-9988');
  await credentialField.fill(TEST_SSN);

  const boxLocator = page.locator('.box');
  await boxLocator.click();

  const authenticateButton = page.getByRole('button', { name: 'Tunnistaudu' });
  await authenticateButton.click();

  // Continue to service flow
  const continueToServiceButton = page.getByRole('button', { name: 'Continue to service' });
  await continueToServiceButton.click();

  // Ensure heading is visible
  const headingLocator = page.getByRole('heading', { name: 'Valitse asiointiroolin tyyppi' });
  await headingLocator.isVisible();
});
