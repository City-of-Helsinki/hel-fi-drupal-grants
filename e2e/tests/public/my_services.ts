import {Page, test} from '@playwright/test';
import {pageCollection} from "../../utils/data/public_page_data";
import {validateComponent, validatePageTitle} from "../../utils/public_helpers";
import {selectRole} from "../../utils/auth_helpers";

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

});


/*
test('check headings', async () => {
  await expect(page.getByRole('heading', {name: 'Tietoa avustuksista ja ohjeita hakijalle'})).toBeVisible()
  await expect(page.getByRole('heading', {name: 'Löydä avustuksesi'})).toBeVisible()
  await expect(page.getByRole('heading', {name: 'Tutustu yleisiin ohjeisiin'})).toBeVisible()
  await expect(page.getByRole('heading', {name: 'Keskeneräiset hakemukset'})).toBeVisible()
  await expect(page.getByRole('heading', {name: 'Lähetetyt hakemukset'})).toBeVisible()
});

test('controls for searching applications', async () => {
  await expect(page.getByLabel('Etsi hakemusta')).toBeVisible()
  await expect(page.getByRole('button', {name: 'Etsi hakemusta'})).toBeEnabled()
  await expect(page.getByLabel('Näytä vain käsittelyssä olevat hakemukset')).toBeVisible()
  await expect(page.getByLabel('Järjestä')).toBeVisible()
});

test('applications can be sorted by date', async () => {
  let amountOfReceivedApplications = await getReceivedApplicationCount(page);
  test.skip(!amountOfReceivedApplications, "No received applications, skip testing sort functionality")

  let dates = await getDateValues(page)

  expect(isDescending(dates)).toBeTruthy()

  await page.getByLabel('Järjestä').selectOption('asc application-list__item--submitted');

  dates = await getDateValues(page)
  expect(isAscending(dates)).toBeTruthy()
});


test('search functionality', async () => {
  let amountOfReceivedApplications = await getReceivedApplicationCount(page);
  test.skip(!amountOfReceivedApplications, "No received applications, skip testing search functionality")

  const INPUT_DELAY = 50;

  const firstApplicationId = await page.locator(receivedApplicationLocator).first().getAttribute("id");

  if (!firstApplicationId) return;

  await page.getByLabel('Etsi hakemusta').pressSequentially(firstApplicationId, {delay: INPUT_DELAY});

  const visibleApplications = await getReceivedApplicationCount(page);
  expect(visibleApplications).toBe(1)
});

test.afterAll(async () => {
  await page.close();
});

const receivedApplicationLocator = '.application-list [data-status="RECEIVED"]';

const getReceivedApplicationCount = async (page: Page) => await page.locator(receivedApplicationLocator).count();

const isAscending = (dates: Date[]) => dates.every((x, i) => i === 0 || x >= dates[i - 1]);
const isDescending = (dates: Date[]) => dates.every((x, i) => i === 0 || x <= dates[i - 1]);

const getDateValues = async (page: Page) => {
  const receivedApplications = await page.locator(receivedApplicationLocator).locator(".application-list__item--submitted").all()

  const datePromises = receivedApplications.map(async a => {
    const innerText = await a.innerText()
    const trimmedText = innerText.trim()
    return new Date(trimmedText)
  })

  const dates = await Promise.all(datePromises)
  return dates;
}
*/
