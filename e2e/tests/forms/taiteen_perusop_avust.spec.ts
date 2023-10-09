import { test, expect } from '@playwright/test';
import { acceptCookies, clickContinueButton, selectRole, startNewApplication } from '../../utils/helpers';

const APPLICATION_TITLE = "Taiteen perusopetuksen avustukset"

test(APPLICATION_TITLE, async ({ page }) => {
  await selectRole(page, 'REGISTERED_COMMUNITY');
  await startNewApplication(page, APPLICATION_TITLE)
  await acceptCookies(page)
  
  // Fill step 1
  await page.getByRole('textbox', { name: 'Hakemusta koskeva sähköposti' }).fill('asadsdqwetest@example.org');
  await page.getByLabel('Yhteyshenkilö').fill('asddsa');
  await page.getByLabel('Puhelinnumero').fill('0234432243');
  await page.locator('#edit-community-address-community-address-select').selectOption({ index: 1 });
  await page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
  await page.getByLabel('Valitse vastaava henkilö').selectOption('0');
  await clickContinueButton(page);


  //Fill step 2
  await page.locator('#edit-acting-year').selectOption('2024');
  await page.locator('#edit-subventions-items-0-amount').fill('123');
  await page.getByText('Hae yhdellä hakemuksella aina vain yhtä avustuslajia kerrallaan.').click() // TODO: Focus issue?
  await page.locator('#edit-ensisijainen-taiteen-ala').selectOption('Sirkus');
  await page.getByRole('textbox', { name: 'Hankkeen tai toiminnan lyhyt esittelyteksti' }).fill('qweqweqew');
  await clickContinueButton(page);

  // Fill step 3
  await expect(page.getByLabel('Henkilöjäseniä yhteensä', { exact: true })).toBeVisible()
  await page.getByLabel('Henkilöjäseniä yhteensä', { exact: true }).fill('12');
  await page.getByLabel('Helsinkiläisiä henkilöjäseniä yhteensä').fill('12');
  await page.getByLabel('Yhteisöjäseniä', { exact: true }).fill('23');
  await page.getByLabel('Helsinkiläisiä yhteisöjäseniä yhteensä').fill('34');
  await page.locator('#edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p').getByText('Kyllä').click();
  await page.getByLabel('Tilan nimi').fill('ewegwegw');
  await page.getByLabel('Tilan tyyppi').selectOption('Esitystila');
  await page.getByLabel('Postinumero').fill('00100');
  await page.locator('#edit-tila-items-0-item-isothersuse').getByText('Ei').click();
  await page.locator('#edit-tila-items-0-item-isownedbyapplicant').getByText('Ei').click();
  await page.locator('#edit-tila-items-0-item-isownedbycity').getByText('Kyllä').click();
  await clickContinueButton(page);

  // Fill step 4
  await page.getByRole('group', { name: 'Varhaisiän opinnot' }).getByLabel('Kaikki').fill('12');
  await page.getByRole('group', { name: 'Varhaisiän opinnot' }).getByLabel('Tytöt').fill('123');
  await page.getByRole('group', { name: 'Varhaisiän opinnot' }).getByLabel('Pojat').fill('1');
  await page.getByRole('group', { name: 'Varhaisiän opinnot' }).getByLabel('Pojat').fill('123');
  await page.getByRole('group', { name: 'Laaja oppimäärä perusopinnot' }).getByLabel('Kaikki').fill('123');
  await page.getByRole('group', { name: 'Laaja oppimäärä perusopinnot' }).getByLabel('Tytöt').fill('123');
  await page.getByRole('group', { name: 'Laaja oppimäärä perusopinnot' }).getByLabel('Pojat').fill('123');
  await page.getByRole('group', { name: 'Laaja oppimäärä syventävät opinnot' }).getByLabel('Kaikki').fill('12');
  await page.getByRole('group', { name: 'Laaja oppimäärä syventävät opinnot' }).getByLabel('Tytöt').fill('12');
  await page.getByRole('group', { name: 'Laaja oppimäärä syventävät opinnot' }).getByLabel('Pojat').fill('12');
  await page.getByRole('group', { name: 'Yleinen oppimäärä' }).getByLabel('Kaikki').fill('12');
  await page.getByRole('group', { name: 'Yleinen oppimäärä' }).getByLabel('Tytöt').fill('2');
  await page.getByRole('group', { name: 'Yleinen oppimäärä' }).getByLabel('Pojat').fill('22');
  await page.getByLabel('Koko opetushenkilöstön lukumäärä 20.9').fill('132');
  await page.getByLabel('Kuvaile oppilaaksi ottamisen tapaa').fill('sdgdgssdg');
  await page.getByLabel('Tehdäänkö oppilaitoksessanne tarvittaessa oppimäärän tai opetuksen yksilöllistämistä?').fill('ergergerg');
  await page.getByLabel('Onko vapaaoppilaspaikkoja? Jos on, niin kuinka monta?').fill('rgergerg');
  await page.getByLabel('Varhaisiän opinnot').fill('34');
  await page.getByLabel('Laaja oppimäärä perusopinnot').fill('34');
  await page.getByLabel('Laaja oppimäärä syventävät opinnot').fill('34');
  await page.getByLabel('Yleinen oppimäärä').fill('34');
  await page.getByLabel('Tilan nimi').fill('wetewtetw');
  await page.getByLabel('Postinumero').fill('00100');
  await page.getByText('Ei', { exact: true }).click();
  await page.getByText('Huonosti').click();
  await clickContinueButton(page);

  // Fill step 5
  await page.getByLabel('Miten monimuotoisuus ja tasa-arvo toteutuu ja näkyy toiminnan järjestäjissä ja organisaatioissa sekä toiminnan sisällöissä? Minkälaisia toimenpiteitä, resursseja ja osaamista on asian edistämiseksi?').fill('wegewgewggew');
  await page.getByLabel('Miten toiminta tehdään kaupunkilaiselle sosiaalisesti, kulttuurisesti, kielellisesti, taloudellisesti, fyysisesti, alueellisesti tai muutoin mahdollisimman saavutettavaksi? Minkälaisia toimenpiteitä, resursseja ja osaamista on asian edistämiseksi?').fill('ergregre');
  await page.getByLabel('Miten ekologisuus huomioidaan toiminnan järjestämisessä? Minkälaisia toimenpiteitä, resursseja ja osaamista on asian edistämiseksi?').fill('ergreggre');
  await page.getByLabel('Mitkä olivat keskeisimmät edelliselle vuodelle asetetut tavoitteet ja saavutettiinko ne?').fill('ergerger');
  await page.getByLabel('Millaisia keinoja käytetään itsearviointiin ja toiminnan kehittämiseen?').fill('eerggerger');
  await page.getByLabel('Mitkä ovat tulevalle vuodelle suunnitellut keskeisimmät muutokset toiminnassa ja sen järjestämisessä suhteessa aikaisempaan?').fill('ergergerger');
  await clickContinueButton(page);

  // Fill step6
  await page.locator('#edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-').getByText('Kyllä').click();
  await page.locator('#edit-budget-static-income-plannedstateoperativesubvention').fill('123');
  await page.locator('#edit-budget-static-income-plannedothercompensations').fill('123');
  await page.getByLabel('Yksityinen rahoitus (esim. sponsorointi, yritysyhteistyö,lahjoitukset) (€)').fill('123');
  await page.getByLabel('Pääsy- ja osallistumismaksut (€)').fill('123');
  await page.getByLabel('Muut oman toiminnan tulot (€)').fill('124');
  await page.getByLabel('Rahoitus- ja korkotulot (€)').fill('124');
  await page.locator('#edit-suunnitellut-menot-plannedtotalcosts').fill('123');
  await page.locator('#edit-organisaatio-kuului-valtionosuusjarjestelmaan-vos-').getByText('Kyllä').click();
  await page.getByRole('textbox', { name: 'Helsingin kaupungin kulttuuripalveluiden toiminta-avustus' }).fill('124');
  await page.locator('#edit-toteutuneet-tulot-data-stateoperativesubvention').fill('1234');
  await page.locator('#edit-toteutuneet-tulot-data-othercompensations').fill('5235');
  await page.getByRole('textbox', { name: 'Tulot yhteensä (€)' }).fill('235325');
  await page.locator('#edit-menot-yhteensa-totalcosts').fill('124124');
  await clickContinueButton(page);

  // Fill step7  
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill('ewegwgweewg');
  await page.getByRole('group', { name: 'Yhteisön säännöt Yhteisön säännöt' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu tilinpäätös' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu toimintakertomus' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu tilin- tai toiminnantarkastuskertomus' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Toimintasuunnitelma' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Talousarvio (sille vuodelle jolle haet avustusta)' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByLabel('Lisäselvitys liitteistä').fill('dfgfdgdfhg');
  await page.getByRole('button', { name: 'Esikatseluun >' }).click();

  // check data on confirmation page
  await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();

  // Submit application
  await page.getByRole('button', { name: 'Lähetä' }).click();
  await expect(page.getByRole('heading', { name: 'Avustushakemus lähetetty onnistuneesti' })).toBeVisible()
});

