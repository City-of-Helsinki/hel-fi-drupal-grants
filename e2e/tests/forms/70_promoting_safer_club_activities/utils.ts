import { expect, type Page, test } from '@playwright/test';
import { type FormTestCase, type OtherCompensation } from './fixtures';

// ---------------------------------------------------------------------------
// Selectors
// ---------------------------------------------------------------------------

export const ids = {
  // Step 0 — applicant_info
  email: 'root_applicant_info_applicant_email_email',
  contactPerson: 'root_applicant_info_contact_person_info_contact_person',
  contactPersonPhone: 'root_applicant_info_contact_person_info_contact_person_phone_number',
  communityAddress: 'root_applicant_info_community_address_community_address',
  bankAccount: 'root_applicant_info_bank_account_bank_account',
  // The CommunityOfficialsSelect widget renders on the `official` field inside each array item.
  communityOfficial: (index: number) =>
    `root_applicant_info_community_officials_community_officials_${index}_official`,

  // Step 1 — grant_info_step
  actingYear: 'root_grant_info_step_acting_year_section_acting_year',
  purpose: 'root_grant_info_step_purpose_section_purpose',
  haveReceivedOtherCompensations: (value: 'false' | 'true') =>
    `root_grant_info_step_other_compensations_section_have_received_other_compensations_${value}`,
  otherCompensationsContainer:
    'root_grant_info_step_other_compensations_section_other_compensations',
  otherCompensationField: (index: number, field: string) =>
    `root_grant_info_step_other_compensations_section_other_compensations_${index}_${field}`,
  haveAppliedOtherCompensations: (value: 'false' | 'true') =>
    `root_grant_info_step_other_applied_compensations_section_have_applied_other_compensations_${value}`,

  // Step 2 — information_in_more_detail_step
  grantTarget: (field: string) =>
    `root_information_in_more_detail_step_grant_target_section_${field}`,
  activeParticipantsWrapper: (field: string) =>
    `root_information_in_more_detail_step_grant_target_section_active_participants_wrapper_${field}`,
  scheduleWrapper: (field: string) =>
    `root_information_in_more_detail_step_grant_target_section_schedule_wrapper_${field}`,

  // Step 3 — budget_step
  incomeRow: (index: number, field: string) =>
    `root_budget_step_income_section_income_rows_${index}_${field}`,
  expenditureRow: (index: number, field: string) =>
    `root_budget_step_expenditure_section_expenditure_rows_${index}_${field}`,

  // Preview step
  finalAcceptance: 'final-acceptance',
} as const;

// ---------------------------------------------------------------------------
// Action functions
// ---------------------------------------------------------------------------



export const getFieldInfo = (step: string, section: string, fieldName: string) => {
  return {
    'fieldTitle': `${section}_${fieldName}.title`,
    'fieldId': `root_${step}_${section}_${fieldName}`,
  };
}

export const selectActingYear = (page: Page) =>
  test.step('Select acting year 2026', async () => {
    await page.selectOption(`#${ids.actingYear}`, '2026');
  });

export const fillPurpose = (page: Page, value: string) =>
  test.step('Fill purpose', async () => {
    await page.fill(`#${ids.purpose}`, value);
  });

export const selectHaveReceivedOtherCompensations = (page: Page, value: boolean) =>
  test.step(`Select have received other compensations: ${value}`, async () => {
    await page.click(`#${ids.haveReceivedOtherCompensations(String(value) as 'false' | 'true')}`);
  });

export const selectHaveAppliedOtherCompensations = (page: Page, value: boolean) =>
  test.step(`Select have applied other compensations: ${value}`, async () => {
    await page.click(`#${ids.haveAppliedOtherCompensations(String(value) as 'false' | 'true')}`);
  });

export const addOtherCompensation = (page: Page, index: number, data: OtherCompensation) =>
  test.step(`Add other compensation item ${index + 1}`, async () => {
    await page.getByRole('button', { name: 'other_compensation.add' }).click();
    await page.selectOption(`#${ids.otherCompensationField(index, 'other_compensation_issuer')}`, data.issuer);
    await page.fill(`#${ids.otherCompensationField(index, 'other_compensation_issuer_name')}`, data.issuerName);
    await page.fill(`#${ids.otherCompensationField(index, 'other_compensation_issuer_year')}`, data.issuerYear);
    await page.fill(`#${ids.otherCompensationField(index, 'other_compensation_amount')}`, data.amount);
    await page.fill(`#${ids.otherCompensationField(index, 'other_compensation_description')}`, data.description);
  });

export const fillGrantTargetField = (page: Page, field: string, value: string) =>
  test.step(`Fill grant target field: ${field}`, async () => {
    await page.fill(`#${ids.grantTarget(field)}`, value);
  });

export const fillActiveParticipants = (page: Page, under20: string, local: string) =>
  test.step('Fill active participants', async () => {
    await page.fill(`#${ids.activeParticipantsWrapper('active_participants_under_20')}`, under20);
    await page.fill(`#${ids.activeParticipantsWrapper('active_participants_local')}`, local);
  });

export const fillScheduleDates = (page: Page, startDate: string, endDate: string) =>
  test.step('Fill schedule dates', async () => {
    await page.fill(`#${ids.scheduleWrapper('start_date')}`, startDate);
    await page.fill(`#${ids.scheduleWrapper('end_date')}`, endDate);
  });

export const addIncomeRow = (page: Page, description: string, amount: string) =>
  test.step('Add income row', async () => {
    await page.getByRole('button', { name: 'income_rows.add' }).click();
    await page.fill(`#${ids.incomeRow(0, 'income_row_description')}`, description);
    await page.fill(`#${ids.incomeRow(0, 'income_row_amount')}`, amount);
  });

export const addExpenditureRow = (page: Page, description: string, amount: string) =>
  test.step('Add expenditure row', async () => {
    await page.getByRole('button', { name: 'expenditure_rows.add' }).click();
    await page.fill(`#${ids.expenditureRow(0, 'expenditure_row_description')}`, description);
    await page.fill(`#${ids.expenditureRow(0, 'expenditure_row_amount')}`, amount);
  });

export const clickNext = (page: Page) =>
  test.step('Click Next', async () => {
    await page.getByRole('button', { name: /Next|Seuraava|Nästa/i }).click();
  });

export const clickOnStep = (i, page: Page) =>
  test.step('Click on step', async () => {
    const stepper = page.locator('.hdbt-form--stepper');
    const buttons = stepper.getByRole('button');
    await buttons.nth(i).click();
  });
