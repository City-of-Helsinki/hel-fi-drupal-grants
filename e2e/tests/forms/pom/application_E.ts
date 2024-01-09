import { Page } from '@playwright/test';
import { Application } from './application';

export class ApplicationE extends Application {
  constructor(page: Page) {
    super(page, {
      url: 'fi/uusi-hakemus/kuva_projekti',
    });
  }

  fillStep_1 = async () => {
    await this.page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('asadsdqwetest@example.org');
    await this.page.getByLabel('Yhteyshenkilö').fill('asddsa');
    await this.page.getByLabel('Puhelinnumero').fill('0234432243');
    await this.page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
    await this.page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
    await this.page.getByLabel('Valitse vastaava henkilö').selectOption('0');
    await this.clickContinueButton();
  };

  fillStep_2 = async () => {
    await this.page.locator('#edit-acting-year').selectOption({ index: 1 });
    await this.page.locator('#edit-subventions-items-0-amount').fill('123,00€');
    await this.page.locator('#edit-ensisijainen-taiteen-ala').selectOption('Museo');
    await this.page.getByRole('textbox', { name: 'Hankkeen nimi' }).fill('qweqweqew');
    await this.page.locator('#edit-kyseessa-on-festivaali-tai-tapahtuma').getByText('Ei').click();
    await this.page.getByRole('textbox', { name: 'Hankkeen tai toiminnan lyhyt esittelyteksti' }).fill('afdfdsd dsg sgd gsd');
    await this.clickContinueButton();
  };

  fillStep_3 = async () => {
    await this.page.getByLabel('Henkilöjäseniä yhteensä', { exact: true }).fill('12');
    await this.page.getByLabel('Helsinkiläisiä henkilöjäseniä yhteensä').fill('12');
    await this.page.getByLabel('Yhteisöjäseniä', { exact: true }).fill('23');
    await this.page.getByLabel('Helsinkiläisiä yhteisöjäseniä yhteensä').fill('34');
    await this.page.getByLabel('Kokoaikaisia: Henkilöitä').fill('23');
    await this.page.getByLabel('Kokoaikaisia: Henkilötyövuosia').fill('34');
    await this.page.getByLabel('Osa-aikaisia: Henkilöitä').fill('23');
    await this.page.getByLabel('Osa-aikaisia: Henkilötyövuosia').fill('23');
    await this.page.getByLabel('Vapaaehtoisia: Henkilöitä').fill('12');
    await this.clickContinueButton();
  };

  fillStep_4 = async () => {
    await this.page.getByLabel('Tapahtuma- tai esityspäivien määrä Helsingissä').fill('12');
    await this.page.getByRole('group', { name: 'Määrä Helsingissä' }).getByLabel('Esitykset').fill('2');
    await this.page.getByRole('group', { name: 'Määrä Helsingissä' }).getByLabel('Näyttelyt').fill('3');
    await this.page.getByRole('group', { name: 'Määrä Helsingissä' }).getByLabel('Työpaja tai muu osallistava toimintamuoto').fill('4');
    await this.page.getByRole('group', { name: 'Määrä kaikkiaan' }).getByLabel('Esitykset').fill('3');
    await this.page.getByRole('group', { name: 'Määrä kaikkiaan' }).getByLabel('Näyttelyt').fill('4');
    await this.page.getByRole('group', { name: 'Määrä kaikkiaan' }).getByLabel('Työpaja tai muu osallistava toimintamuoto').fill('5');
    await this.page.getByRole('textbox', { name: 'Kävijämäärä Helsingissä' }).fill('12222');
    await this.page.getByRole('textbox', { name: 'Kävijämäärä kaikkiaan' }).fill('343444');
    await this.page.getByRole('textbox', { name: 'Kantaesitysten määrä' }).fill('12');
    await this.page.getByRole('textbox', { name: 'Ensi-iltojen määrä Helsingissä' }).fill('23');
    await this.page.getByLabel('Tilan nimi').fill('sdggdsgds');
    await this.page.getByLabel('Postinumero').fill('00100');
    await this.page.getByText('Ei', { exact: true }).click();
    await this.page.getByLabel('Ensimmäisen yleisölle avoimen tilaisuuden päivämäärä').fill('2024-12-12');
    await this.page.getByLabel('Hanke alkaa').fill('2030-01-01');
    await this.page.getByLabel('Hanke loppuu').fill('2030-02-02');
    await this.page.getByRole('textbox', { name: 'Laajempi hankekuvaus Laajempi hankekuvaus' }).fill('sdgdsgdgsgds');
    await this.clickContinueButton();
  };

  fillStep_5 = async () => {
    await this.page.getByLabel('Keitä toiminnalla tavoitellaan? Miten kyseiset kohderyhmät aiotaan tavoittaa').fill('sdgsgdsdg');
    await this.page.getByRole('textbox', { name: 'Nimeä keskeisimmät yhteistyökumppanit ja kuvaa yhteistyön' }).fill('werwerewr');
    await this.clickContinueButton();
  };

  fillStep_6 = async () => {
    await this.page.getByText('Ei', { exact: true }).click();
    await this.page.getByRole('textbox', { name: 'Muut avustukset (€) Muut avustukset (€)' }).fill('234');
    await this.page.getByLabel('Yksityinen rahoitus (esim. sponsorointi, yritysyhteistyö, lahjoitukset) (€)').fill('234');
    await this.page.getByLabel('Pääsy- tai osallistumismaksut (€)').fill('123');
    await this.page.getByLabel('Muut oman toiminnan tulot (€)').fill('123');
    await this.page.getByLabel('Yhteisön oma rahoitus (€)').fill('123');
    await this.page.getByLabel('Palkat ja palkkiot esiintyjille ja taiteilijoille (€)').fill('123');
    await this.page.getByLabel('Muut palkat ja palkkiot (tuotanto, tekniikka jne) (€)').fill('123');
    await this.page.getByLabel('Henkilöstösivukulut palkoista ja palkkioista (n. 30%) (€)').fill('123');
    await this.page.getByRole('textbox', { name: 'Esityskorvaukset (€) Esityskorvaukset (€)' }).fill('123');
    await this.page.getByLabel('Matkakulut (€)').fill('123');
    await this.page.getByLabel('Kuljetus (sis. autovuokrat) (€)').fill('123');
    await this.page.getByLabel('Tekniikka, laitevuokrat ja sähkö (€)').fill('123');
    await this.page.getByLabel('Kiinteistöjen käyttökulut, vuokrat (€)').fill('123');
    await this.page.getByLabel('Tiedotus, markkinointi ja painatus (€)').fill('123');
    await this.page.getByLabel('Kuvaus menosta').fill('11wdgwgregre');
    await this.page.getByLabel('Määrä (€)').fill('234');
    await this.page.getByLabel('Sisältyykö toiminnan toteuttamiseen jotain muuta rahanarvoista panosta').fill('erggergergegerger');
    await this.clickContinueButton();
  };
  fillStep_7 = async () => {
    await this.page.getByRole('textbox', { name: 'Lisätiedot' }).fill('fewqfwqfwqfqw');
    await this.page.getByLabel('Lisäselvitys liitteistä').fill('sdfdsfdsfdfs');
    await this.clickGoToPreviewButton();
  };

  fillAllSteps = async () => {
    await this.fillStep_1();
    await this.fillStep_2();
    await this.fillStep_3();
    await this.fillStep_4();
    await this.fillStep_5();
    await this.fillStep_6();
    await this.fillStep_7();
  };
}
