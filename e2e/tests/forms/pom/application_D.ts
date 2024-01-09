import { Page, expect } from '@playwright/test';
import { Application } from './application';

export class ApplicationD extends Application {
  constructor(page: Page) {
    super(page, {
      url: 'fi/uusi-hakemus/kasvatus_ja_koulutus_yleisavustu',
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
    await this.page.locator('#edit-subventions-items-0-amount').fill('128,00€');
    await this.page
      .getByRole('textbox', { name: 'Lyhyt kuvaus haettavan / haettavien avustusten käyttötarkoituksista' })
      .fill('lyhyt kuvasu');
    await this.page.getByLabel('Kuvaus lainoista ja takauksista').fill('asdadsdadaas');
    await this.page.getByLabel('Kuvaus tiloihin liittyvästä tuesta').fill('sdfdfsfdsdsf');
    await this.clickContinueButton();
  };

  fillStep_3 = async () => {
    await this.page.getByRole('textbox', { name: 'Toiminnan kuvaus' }).fill('asffsafsasfa');
    await this.page.getByText('Ei', { exact: true }).click();
    await this.page.locator('#edit-fee-person').fill('64');
    await this.page.locator('#edit-fee-community').fill('64');
    await this.page.getByRole('textbox', { name: 'Henkilöjäseniä yhteensä Henkilöjäseniä yhteensä' }).fill('123');
    await this.page.getByRole('textbox', { name: 'Helsinkiläisiä henkilöjäseniä yhteensä' }).fill('22');
    await this.page.getByRole('textbox', { name: 'Yhteisöjäseniä Yhteisöjäseniä' }).fill('44');
    await this.page.getByRole('textbox', { name: 'Helsinkiläisiä yhteisöjäseniä yhteensä' }).fill('55');
    await this.clickContinueButton();
  };

  fillStep_4 = async () => {
    await this.page.getByRole('textbox', { name: 'Lisätiedot' }).fill('qwfqwfqwfwfqfwq');
    await this.page.getByRole('group', { name: 'Yhteisön säännöt Yhteisön säännöt' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Vahvistettu tilinpäätös' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Vahvistettu toimintakertomus' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Vahvistettu tilin- tai toiminna' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.locator('#edit-vuosikokouksen-poytakirja--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    await this.page.locator('#edit-toimintasuunnitelma--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    await this.page.locator('#edit-talousarvio--wrapper').getByText('Liite toimitetaan myöhemmin').click();
    await this.page.getByLabel('Lisäselvitys liitteistä').fill('sdfdfsdfsdfsdfsdfsdfs');
    await this.clickGoToPreviewButton();
  };

  fillAllSteps = async () => {
    await this.fillStep_1();
    await this.fillStep_2();
    await this.fillStep_3();
    await this.fillStep_4();
  };
}
