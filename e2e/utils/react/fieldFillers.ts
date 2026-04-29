import {expect, Locator, Page} from "@playwright/test";
import {assertFieldErrorGone} from "./utils";

/**
 * Returns a date in Finnish format (d.m.yyyy) a given number of
 * months from today.
 *
 * @param monthsFromNow
 *   How many months ahead the date should be.
 */
export function finnishDate(monthsFromNow: number): string {
  const d = new Date();
  d.setMonth(d.getMonth() + monthsFromNow);
  return `${d.getDate()}.${d.getMonth() + 1}.${d.getFullYear()}`;
}

/**
 * Opens an HDS dropdown, picks the first option, and returns its text.
 *
 * Also confirms the dropdown closed and the error message is gone,
 * so the caller knows the selection was accepted.
 *
 * @param page
 *   The Playwright page instance.
 * @param fieldDOM
 *   The Playwright locator for the dropdown container.
 * @param fieldId
 *   The HTML id of the dropdown container.
 */
export async function selectFirstDropdownOption(
  page: Page,
  fieldDOM: Locator,
  fieldId: string,
) {
  await fieldDOM.locator(`#${fieldId}-main-button`).click();
  const firstOption = fieldDOM.locator('[role="option"]').first();
  await expect(firstOption).toBeVisible();
  const optionText = (await firstOption.textContent()) ?? '';
  await firstOption.click();
  await page.keyboard.press('Escape');
  await expect(page.locator(`#${fieldId}-main-button`)).toHaveAttribute('aria-expanded', 'false');
  await assertFieldErrorGone(page, fieldId);
  return optionText.trim();
}
