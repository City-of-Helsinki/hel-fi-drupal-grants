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
import {logger} from "../../utils/logger";

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

  test('Application filtering: Can be sorted by date', async () => {
    logger('Validating application filtering date sorting...');
    const receivedApplications = await getReceivedApplicationCount(page);
    test.skip(!receivedApplications, 'Skip application search sort test');

    let applicationDates = await getReceivedApplicationDateValues(page);
    expect(isDescending(applicationDates)).toBeTruthy();

    await page.getByLabel('Järjestä').selectOption('asc application-list__item--submitted');

    applicationDates = await getReceivedApplicationDateValues(page);
    expect(isAscending(applicationDates)).toBeTruthy();
    logger('Application filtering date sorting validated.');
  });

  test('Application filtering: Filter by text', async () => {
    logger('Validating application filtering text filtering...');
    const receivedApplicationsCount = await getReceivedApplicationCount(page);
    test.skip(!receivedApplicationsCount, 'Skip application search sort test');

    const firstApplication = await page.locator('#oma-asiointi__sent .application-list__item').first();
    const applicationId = await firstApplication.getAttribute('data-drupal-selector');

    if (!applicationId) return;

    await page.getByLabel('Etsi hakemusta').pressSequentially(applicationId, {delay: 50});

    const visibleApplicationsCount = await getReceivedApplicationCount(page);
    expect(visibleApplicationsCount).toBe(1);
    logger('Application filtering text filtering validated.');
  });

});

