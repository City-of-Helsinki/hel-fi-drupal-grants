import { Locator, Page, expect } from '@playwright/test';

type ConstructorValues = {
  deleteBankAccountButtonLocator?: Locator;
  inputData: Record<string, string>;
};

export abstract class ProfilePage {
  public page: Page;
  protected inputData;
  public deleteBankAccountButtonLocator: Locator;

  constructor(page: Page, values: ConstructorValues) {
    this.page = page;
    this.deleteBankAccountButtonLocator = values.deleteBankAccountButtonLocator ?? page.getByRole('button', { name: 'Poista' });
    this.inputData = values.inputData;
  }

  goto = async (path: string = '/fi/oma-asiointi/hakuprofiili') => await this.page.goto(path);

  bankAccountIsRequired = async () => {
    await this.goto('fi/oma-asiointi/hakuprofiili/muokkaa');

    const bankAccountSection = this.page.locator('#edit-bankaccountwrapper-0-bank');
    await expect(bankAccountSection).toBeVisible();

    await Promise.all([this.page.waitForResponse((response) => response.status() === 200), this.deleteBankAccountButtonLocator.click()]);

    await expect(bankAccountSection).not.toBeVisible();

    await this.page.getByRole('button', { name: 'Tallenna omat tiedot' }).click();
    const warningText = this.page.getByLabel('Notification').getByText('Sinun tulee lisätä vähintään yksi pankkitili').first();
    await expect(warningText).toBeVisible();
  };

  checkThatNewValuesAreVisible = async () => {
    const profileInfoText = this.page.locator('.grants-profile--extrainfo');
    const inputValues = Object.values(this.inputData);

    for (const val of inputValues) {
      await expect.soft(profileInfoText).toContainText(val);
    }
  };
}
