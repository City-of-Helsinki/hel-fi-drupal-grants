import { type Page, expect } from '@playwright/test';
import { faker } from '@faker-js/faker';
import { Application } from './application';

const inputData = {
  additionalInformation: faker.lorem.words(),
  attachmentInfo: faker.lorem.words(),
  email: 'test@example.org',
  name: faker.person.fullName(),
  phoneNumber: faker.phone.number(),
  shortDescription: faker.lorem.words(),
};

export class ApplicationA extends Application {
  public userInputData: typeof inputData;

  constructor(page: Page) {
    super(page, {
      url: 'fi/uusi-hakemus/taide_ja_kulttuuriavustukset_tai',
      errorTexts: requiredFieldsErrorTexts,
      userInputData: inputData,
    });
    this.userInputData = inputData;
  }

  fillStep_1 = async () => {
    await this.page.getByRole('textbox', { name: 'Hakemusta koskeva sähköposti' }).fill(this.userInputData.email);
    await this.page.getByLabel('Yhteyshenkilö').fill(this.userInputData.name);
    await this.page.getByLabel('Puhelinnumero').fill(this.userInputData.phoneNumber);
    await this.page.locator('select#edit-community-address-community-address-select').selectOption({ index: 1 });
    await this.page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
    await this.clickContinueButton();
  };

  fillStep_2 = async () => {
    await this.page.locator('#edit-acting-year').selectOption({ index: 1 });
    await this.page.locator('#edit-subventions-items-0-amount').fill('123,00€');
    await this.page.locator('#edit-ensisijainen-taiteen-ala').selectOption('Sirkus');
    await this.page.getByRole('textbox', { name: 'Hankkeen tai toiminnan lyhyt esittelyteksti' }).fill(this.userInputData.shortDescription);
    await this.clickContinueButton();
  };

  fillStep_3 = async () => {
    await expect(this.page.getByLabel('Helsinkiläisiä henkilöjäseniä yhteensä')).toBeVisible();
    await this.page.getByLabel('Henkilöjäseniä yhteensä', { exact: true }).fill('12');
    await this.page.getByLabel('Helsinkiläisiä henkilöjäseniä yhteensä').fill('12');
    await this.page.getByLabel('Yhteisöjäseniä', { exact: true }).fill('23');
    await this.page.getByLabel('Helsinkiläisiä yhteisöjäseniä yhteensä').fill('34');
    await this.page.locator('#edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p').getByText('Kyllä').click();
    await this.page.getByLabel('Tilan nimi').fill('ewegwegw');
    await this.page.getByLabel('Tilan tyyppi').selectOption('Esitystila');
    await this.page.getByLabel('Postinumero').fill('00100');
    await this.page.locator('#edit-tila-items-0-item-isothersuse').getByText('Ei').click();
    await this.page.locator('#edit-tila-items-0-item-isownedbyapplicant').getByText('Ei').click();
    await this.page.locator('#edit-tila-items-0-item-isownedbycity').getByText('Kyllä').click();
    await this.clickContinueButton();
  };

  fillStep_4 = async () => {
    await this.page.getByRole('group', { name: 'Varhaisiän opinnot' }).getByLabel('Kaikki').fill('12');
    await this.page.getByRole('group', { name: 'Varhaisiän opinnot' }).getByLabel('Tytöt').fill('123');
    await this.page.getByRole('group', { name: 'Varhaisiän opinnot' }).getByLabel('Pojat').fill('1');
    await this.page.getByRole('group', { name: 'Varhaisiän opinnot' }).getByLabel('Pojat').fill('123');
    await this.page.getByRole('group', { name: 'Laaja oppimäärä perusopinnot' }).getByLabel('Kaikki').fill('123');
    await this.page.getByRole('group', { name: 'Laaja oppimäärä perusopinnot' }).getByLabel('Tytöt').fill('123');
    await this.page.getByRole('group', { name: 'Laaja oppimäärä perusopinnot' }).getByLabel('Pojat').fill('123');
    await this.page.getByRole('group', { name: 'Laaja oppimäärä syventävät opinnot' }).getByLabel('Kaikki').fill('12');
    await this.page.getByRole('group', { name: 'Laaja oppimäärä syventävät opinnot' }).getByLabel('Tytöt').fill('12');
    await this.page.getByRole('group', { name: 'Laaja oppimäärä syventävät opinnot' }).getByLabel('Pojat').fill('12');
    await this.page.getByRole('group', { name: 'Yleinen oppimäärä' }).getByLabel('Kaikki').fill('12');
    await this.page.getByRole('group', { name: 'Yleinen oppimäärä' }).getByLabel('Tytöt').fill('2');
    await this.page.getByRole('group', { name: 'Yleinen oppimäärä' }).getByLabel('Pojat').fill('22');
    await this.page.getByLabel('Koko opetushenkilöstön lukumäärä 20.9').fill('132');
    await this.page.getByLabel('Kuvaile oppilaaksi ottamisen tapaa').fill('sdgdgssdg');
    await this.page.getByLabel('Tehdäänkö oppilaitoksessanne tarvittaessa oppimäärän tai opetuksen yksilöllistämistä?').fill('ergergerg');
    await this.page.getByLabel('Onko vapaaoppilaspaikkoja? Jos on, niin kuinka monta?').fill('rgergerg');
    await this.page.getByLabel('Varhaisiän opinnot').fill('34');
    await this.page.getByLabel('Laaja oppimäärä perusopinnot').fill('34');
    await this.page.getByLabel('Laaja oppimäärä syventävät opinnot').fill('34');
    await this.page.getByLabel('Yleinen oppimäärä').fill('34');
    await this.page.getByLabel('Tilan nimi').fill('wetewtetw');
    await this.page.getByLabel('Postinumero').fill('00100');
    await this.page.getByText('Ei', { exact: true }).click();
    await this.page.getByText('Huonosti').click();
    await this.clickContinueButton();
  };

  fillStep_5 = async () => {
    await this.page.getByLabel('Miten monimuotoisuus ja tasa-arvo toteutuu ja näkyy toiminnan järjestäjissä').fill('wegewgewggew');
    await this.page.getByLabel('Miten toiminta tehdään kaupunkilaiselle sosiaalisesti, kulttuurisesti, kielellisesti').fill('ergregre');
    await this.page.getByLabel('Miten ekologisuus huomioidaan toiminnan järjestämisessä?').fill('ergreggre');
    await this.page.getByLabel('Mitkä olivat keskeisimmät edelliselle vuodelle asetetut tavoitteet ja saavutettiinko ne?').fill('ergerger');
    await this.page.getByLabel('Millaisia keinoja käytetään itsearviointiin ja toiminnan kehittämiseen?').fill('eerggerger');
    await this.page.getByLabel('Mitkä ovat tulevalle vuodelle suunnitellut keskeisimmät muutokset toiminnassa').fill('ergergerger');
    await this.clickContinueButton();
  };

  fillStep_6 = async () => {
    await this.page.locator('#edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-').getByText('Kyllä').click();
    await this.page.locator('#edit-budget-static-income-plannedstateoperativesubvention').fill('123');
    await this.page.locator('#edit-budget-static-income-plannedothercompensations').fill('123');
    await this.page.getByLabel('Yksityinen rahoitus (esim. sponsorointi, yritysyhteistyö,lahjoitukset) (€)').fill('123');
    await this.page.getByLabel('Pääsy- ja osallistumismaksut (€)').fill('123');
    await this.page.getByLabel('Muut oman toiminnan tulot (€)').fill('124');
    await this.page.getByLabel('Rahoitus- ja korkotulot (€)').fill('124');
    await this.page.locator('#edit-suunnitellut-menot-plannedtotalcosts').fill('123');
    await this.page.locator('#edit-organisaatio-kuului-valtionosuusjarjestelmaan-vos-').getByText('Kyllä').click();
    await this.page.getByRole('textbox', { name: 'Helsingin kaupungin kulttuuripalveluiden toiminta-avustus' }).fill('124');
    await this.page.locator('#edit-toteutuneet-tulot-data-stateoperativesubvention').fill('1234');
    await this.page.locator('#edit-toteutuneet-tulot-data-othercompensations').fill('5235');
    await this.page.getByRole('textbox', { name: 'Tulot yhteensä (€)' }).fill('235325');
    await this.page.locator('#edit-menot-yhteensa-totalcosts').fill('124124');
    await this.clickContinueButton();
  };

  fillStep_7 = async () => {
    await this.page.getByRole('textbox', { name: 'Lisätiedot' }).fill(this.userInputData.additionalInformation);
    await this.page.getByRole('group', { name: 'Yhteisön säännöt Yhteisön säännöt' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Vahvistettu tilinpäätös' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Vahvistettu toimintakertomus' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Vahvistettu tilin- tai toiminn' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Toimintasuunnitelma' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByRole('group', { name: 'Talousarvio' }).getByLabel('Liite toimitetaan myöhemmin').check();
    await this.page.getByLabel('Lisäselvitys liitteistä').fill(this.userInputData.attachmentInfo);
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
    await this.checkConfirmationPage();
  };
}

const requiredFieldsErrorTexts = [
  'Hakijan tiedot: Hakemusta koskeva sähköposti kenttä',
  'Hakijan tiedot: Yhteyshenkilö kenttä',
  'Hakijan tiedot: Puhelinnumero kenttä',
  'Hakijan tiedot: Valitse tilinumero kenttä',
  'Hakijan tiedot: Yhteisön osoite kenttä',
  'Hakijan tiedot: Valitse osoite kenttä',
  'Avustustiedot: Vuosi, jolle haen avustusta kenttä',
  'Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa',
  'Avustustiedot: Ensisijainen taiteenala kenttä',
  'Avustustiedot: Hankkeen tai toiminnan lyhyt esittelyteksti kenttä',
  'Yhteisön tiedot: Taiteellisen toiminnan tilaa omistuksessa tai ympärivuotisesti päävuokralaisena kenttä',
  'Toteutunut toiminta: Tilan nimi kenttä',
  'Toteutunut toiminta: Postinumero kenttä',
  'Toteutunut toiminta: Kyseessä on kaupungin omistama tila',
  'Talous: Organisaatio kuuluu valtionosuusjärjestelmään (VOS) kenttä',
  'Talous: Valtion toiminta-avustus (€) kenttä',
  'Talous: Muut avustukset (€) kenttä',
  'Talous: Yksityinen rahoitus (esim. sponsorointi, yritysyhteistyö,lahjoitukset) (€) kenttä',
  'Talous: Pääsy- ja osallistumismaksut (€) kenttä',
  'Talous: Muut oman toiminnan tulot (€) kenttä',
  'Talous: Rahoitus- ja korkotulot (€) kenttä',
  'Talous: Menot yhteensä (€) kenttä',
  'Talous: Organisaatio kuului valtionosuusjärjestelmään (VOS) kenttä',
  'Talous: Helsingin kaupungin kulttuuripalveluiden toiminta-avustus (€) kenttä',
  'Talous: Valtion toiminta-avustus (€) kenttä',
  'Talous: Muut avustukset (€) kenttä',
  'Talous: Tulot yhteensä (€) kenttä',
  'Talous: Menot yhteensä (€) kenttä',
  'Lisätiedot ja liitteet: Yhteisön säännöt ei sisällä liitettyä tiedostoa',
  'Lisätiedot ja liitteet: Vahvistettu tilinpäätös (edelliseltä päättyneeltä tilivuodelta) ei sisällä liitettyä tiedostoa',
  'Lisätiedot ja liitteet: Vahvistettu toimintakertomus (edelliseltä päättyneeltä tilivuodelta) ei sisällä liitettyä tiedostoa',
  'Lisätiedot ja liitteet: Vahvistettu tilin- tai toiminnantarkastuskertomus (edelliseltä päättyneeltä tilivuodelta) ei sisällä liitettyä tiedostoa',
  'Lisätiedot ja liitteet: Toimintasuunnitelma (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa',
  'Lisätiedot ja liitteet: Talousarvio (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa',
];
