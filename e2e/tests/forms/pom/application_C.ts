import { Page, expect } from '@playwright/test';
import { Application } from './application';

export class ApplicationC extends Application {
  constructor(page: Page) {
    super(page, {
      url: 'fi/uusi-hakemus/kasko_ip_lisa',
    });
  }

  fillStep_1 = async () => {
    await this.page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('asadsdqwetest@example.org');
    await this.page.getByLabel('Yhteyshenkilö').fill('asddsa');
    await this.page.getByLabel('Puhelinnumero').fill('0234432243');
    await this.page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
    await this.page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
    await this.page.getByLabel('Valitse vastaava henkilö').selectOption({ index: 1 });
    await this.clickContinueButton();
  };

  fillStep_2 = async () => {
    await this.page.getByLabel('Vuosi, jolle haen avustusta').selectOption({ index: 1 });
    await this.page.locator('#edit-subventions-items-0-amount').fill('123,00€');
    await this.page
      .getByRole('textbox', { name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista' })
      .fill('lyhyt kuvasu');
    await this.page.getByLabel('Alkaen').fill('2024-09-23');
    await this.page.getByLabel('Päättyy').fill('2024-11-30');
    await this.clickContinueButton();
  };

  fillStep_3 = async () => {
    await expect(this.page.getByRole('textbox', { name: 'Lisätiedot' })).toBeVisible();
    await this.page.getByRole('textbox', { name: 'Lisätiedot' }).fill('asffsafsasfa');
    await this.page.getByLabel('Lisäselvitys liitteistä').fill('wefewffwfew');
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
