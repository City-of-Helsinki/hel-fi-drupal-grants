import { Page } from '@playwright/test';
import { Application } from './application';

export class ApplicationB extends Application {
  constructor(page: Page) {
    super(page, {
      url: 'fi/uusi-hakemus/yleisavustushakemus',
    });
  }

  fillStep_1 = async () => {
    await this.page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('test@example.org');
    await this.page.getByLabel('Yhteyshenkilö').fill('John Doe');
    await this.page.getByLabel('Puhelinnumero').fill('0440123123');
    await this.page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
    await this.page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
    await this.clickContinueButton();
  };

  fillStep_2 = async () => {
    await this.page.getByLabel('Vuosi, jolle haen avustusta').selectOption({ index: 1 });
    await this.page.locator('#edit-subventions-items-0-amount').fill('200,50€');
    await this.page.getByRole('textbox', { name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista' }).fill('foo');
    await this.clickContinueButton();
  };

  fillStep_3 = async () => {
    await this.page.getByRole('group', { name: 'Harjoittaako yhteisö liiketoimintaa' }).getByText('Ei', { exact: true }).click();
    await this.page.locator('#edit-fee-person').fill('200,00€');
    await this.page.locator('#edit-fee-community').fill('50,00€');
    await this.page.locator('#edit-members-applicant-person-global').fill('4');
    await this.page.locator('#edit-members-applicant-person-local').fill('5');
    await this.page.locator('#edit-members-applicant-community-global').fill('6');
    await this.page.locator('#edit-members-applicant-community-local').fill('7');
    await this.clickContinueButton();
  };

  fillStep_4 = async () => {
    await this.page.getByRole('group', { name: 'Yhteisön säännöt' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Vahvistettu tilinpäätös' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Vahvistettu toimintakertomus' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Vahvistettu tilin- tai toiminnanta' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Vuosikokouksen pöytäkirja' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Toimintasuunnitelma' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Talousarvio' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByLabel('Lisäselvitys liitteistä').fill('qwfwqfwfq');
    await this.clickGoToPreviewButton();
  };

  fillAllSteps = async () => {
    await this.fillStep_1();
    await this.fillStep_2();
    await this.fillStep_3();
    await this.fillStep_4();
  };
}
