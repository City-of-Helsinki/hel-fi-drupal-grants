import {expect, Page, test} from '@playwright/test';
import {pageCollection} from "../../utils/data/public_page_data";
import {selectRole} from "../../utils/auth_helpers";
import {
  getReceivedApplicationCount,
  getReceivedApplicationDateValues, isAscending,
  isDescending,
  validateComponent,
  validatePageTitle
} from "../../utils/public_helpers";

const scenario = pageCollection['my_services'];

test.describe(`Testing page: ${scenario.url}`, () => {
  let page: Page;

  test.beforeAll(async ({browser}) => {
    page = await browser.newPage();
    await selectRole(page, 'REGISTERED_COMMUNITY');
  });

  test.beforeEach(async () => {
    await page.goto(scenario.url);
  });

  test.afterAll(async () => {
    await page.close();
  });

  test(`Validate page title: ${scenario.url}`, async () => {
    test.skip(!scenario.validatePageTitle, 'Skip page title test');
    await validatePageTitle(page);
  });

  test(`Validate components: ${scenario.url}`, async () => {
    for (const component of scenario.components) {
      await validateComponent(page, component);
    }
  });

  test('Application search: Controls', async () => {
    await expect(page.getByLabel('Etsi hakemusta')).toBeVisible()
    await expect(page.getByRole('button', {name: 'Etsi hakemusta'})).toBeEnabled()
    await expect(page.getByLabel('Näytä vain käsittelyssä olevat hakemukset')).toBeVisible()
    await expect(page.getByLabel('Järjestä')).toBeVisible()
  });

  test('Application search: Can be sorted by date', async () => {
    const receivedApplications = await getReceivedApplicationCount(page);
    test.skip(!receivedApplications, 'Skip application search sort test');

    let applicationDates = await getReceivedApplicationDateValues(page);
    expect(isDescending(applicationDates)).toBeTruthy();

    await page.getByLabel('Järjestä').selectOption('asc application-list__item--submitted');

    applicationDates = await getReceivedApplicationDateValues(page);
    expect(isAscending(applicationDates)).toBeTruthy();
  });

  test('Application search: Filter by text', async () => {
    const receivedApplicationsCount = await getReceivedApplicationCount(page);
    test.skip(!receivedApplicationsCount, 'Skip application search sort test');

    const firstApplication = await page.locator('#oma-asiointi__sent .application-list__item').first();
    const applicationId = await firstApplication.getAttribute('data-drupal-selector');

    if (!applicationId) return;

    await page.getByLabel('Etsi hakemusta').pressSequentially(applicationId, {delay: 50});

    const visibleApplicationsCount = await getReceivedApplicationCount(page);
    expect(visibleApplicationsCount).toBe(1);
  });

});

