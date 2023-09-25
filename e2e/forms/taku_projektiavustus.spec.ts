import { test, expect } from '@playwright/test';
import { loginWithCompanyRole, startNewApplication } from '../utils/helpers';

const APPLICATION_TITLE = "Taide- ja kulttuuriavustukset: projektiavustukset"

test(APPLICATION_TITLE, async ({ page }) => {
  // Login
  await loginWithCompanyRole(page)
  await startNewApplication(page, APPLICATION_TITLE)
  
  // Fill step 1
  await page.getByRole('textbox', { name: 'Sähköpostiosoite' }).fill('asadsdqwetest@example.org');
  await page.getByLabel('Yhteyshenkilö').fill('asddsa');
  await page.getByLabel('Puhelinnumero').fill('0234432243');
  await page.locator('#edit-community-address-community-address-select').selectOption('0b78909a-1d05-4c50-af97-9f03ef183a11');
  await page.locator('#edit-bank-account-account-number-select').selectOption('FI4069674615287672');
  await page.getByLabel('Select official').selectOption('0');
  await page.getByRole('button', { name: 'Seuraava' }).click();

  // Fill step 2
  await page.locator('#edit-acting-year').selectOption('2023');
  await page.locator('#edit-subventions-items-0-amount').fill('123');
  await page.getByText('Hae yhdellä hakemuksella aina vain yhtä avustuslajia kerrallaan.').click() // TODO: Focus issue?
  await page.locator('#edit-ensisijainen-taiteen-ala').selectOption('Museo');
  await page.getByRole('textbox', { name: 'Hankkeen nimi' }).fill('qweqweqew');
  await page.locator('#edit-kyseessa-on-festivaali-tai-tapahtuma').getByText('Ei').click();
  await page.getByRole('textbox', { name: 'Hankkeen tai toiminnan lyhyt esittelyteksti' }).fill('afdfdsd dsg sgd gsd');
  await page.getByRole('button', { name: 'Seuraava >' },).click();

  // Fill step 3
  await page.getByLabel('Henkilöjäseniä yhteensä', { exact: true }).fill('12');
  await page.getByLabel('Helsinkiläisiä henkilöjäseniä yhteensä').fill('12');
  await page.getByLabel('Yhteisöjäseniä', { exact: true }).fill('23');
  await page.getByLabel('Helsinkiläisiä yhteisöjäseniä yhteensä').fill('34');
  await page.getByLabel('Kokoaikaisia: Henkilöitä').fill('23');
  await page.getByLabel('Kokoaikaisia: Henkilötyövuosia').fill('34');
  await page.getByLabel('Osa-aikaisia: Henkilöitä').fill('23');
  await page.getByLabel('Osa-aikaisia: Henkilötyövuosia').fill('23');
  await page.getByLabel('Vapaaehtoisia: Henkilöitä').fill('12');
  await page.getByRole('button', { name: 'Seuraava >' }).click();

  // Fille step 4
  await page.getByLabel('Tapahtuma- tai esityspäivien määrä Helsingissä').fill('12');
  await page.getByRole('group', { name: 'Määrä Helsingissä' }).getByLabel('Esitykset').fill('2');
  await page.getByRole('group', { name: 'Määrä Helsingissä' }).getByLabel('Näyttelyt').fill('3');
  await page.getByRole('group', { name: 'Määrä Helsingissä' }).getByLabel('Työpaja tai muu osallistava toimintamuoto').fill('4');
  await page.getByRole('group', { name: 'Määrä kaikkiaan Määrä kaikkiaan' }).getByLabel('Esitykset').fill('3');
  await page.getByRole('group', { name: 'Määrä kaikkiaan Määrä kaikkiaan' }).getByLabel('Näyttelyt').fill('4');
  await page.getByRole('group', { name: 'Määrä kaikkiaan Määrä kaikkiaan' }).getByLabel('Työpaja tai muu osallistava toimintamuoto').fill('5');
  await page.getByRole('textbox', { name: 'Kävijämäärä Helsingissä Kävijämäärä Helsingissä' }).fill('12222');
  await page.getByRole('textbox', { name: 'Kävijämäärä kaikkiaan Kävijämäärä kaikkiaan' }).fill('343444');
  await page.getByRole('textbox', { name: 'Kantaesitysten määrä Kantaesitysten määrä' }).fill('12');
  await page.getByRole('textbox', { name: 'Ensi-iltojen määrä Helsingissä Ensi-iltojen määrä Helsingissä' }).fill('23');
  await page.getByLabel('Tilan nimi').fill('sdggdsgds');
  await page.getByLabel('Postinumero').fill('00100');
  await page.getByText('Ei', { exact: true }).click();
  await page.getByLabel('Ensimmäisen yleisölle avoimen tilaisuuden päivämäärä').fill('2024-12-12');
  await page.getByLabel('Hanke alkaa').fill('2030-01-01');
  await page.getByLabel('Hanke loppuu').fill('2030-02-02');
  await page.getByRole('textbox', { name: 'Laajempi hankekuvaus Laajempi hankekuvaus' }).fill('sdgdsgdgsgds');
  await page.getByRole('button', { name: 'Seuraava >' }).click();

  // Fill step 5
  await page.getByLabel('Keitä toiminnalla tavoitellaan? Miten kyseiset kohderyhmät aiotaan tavoittaa ja mitä osaamista näiden kanssa työskentelyyn on?').fill('sdgsgdsdg');
  await page.getByRole('textbox', { name: 'Nimeä keskeisimmät yhteistyökumppanit ja kuvaa yhteistyön muotoja ja ehtoja. Nimeä keskeisimmät yhteistyökumppanit ja kuvaa yhteistyön muotoja ja ehtoja.' }).fill('werwerewr');
  await page.getByRole('button', { name: 'Seuraava >' }).click();

  // Fill step 6
  await page.getByText('Ei', { exact: true }).click();
  await page.getByRole('textbox', { name: 'Muut avustukset (€) Muut avustukset (€)' }).fill('234');
  await page.getByLabel('Yksityinen rahoitus (esim. sponsorointi, yritysyhteistyö,lahjoitukset) (€)').fill('234');
  await page.getByLabel('Pääsy- ja osallistumismaksut (€)').fill('123');
  await page.getByLabel('Muut oman toiminnan tulot (€)').fill('123');
  await page.getByLabel('Yhteisön oma rahoitus (€)').fill('123');
  await page.getByLabel('Palkat ja palkkiot esiintyjille ja taiteilijoille (€)').fill('123');
  await page.getByLabel('Muut palkat ja palkkiot (tuotanto, tekniikka jne) (€)').fill('123');
  await page.getByLabel('Henkilöstösivukulut palkoista ja palkkioista (n. 30%) (€)').fill('123');
  await page.getByRole('textbox', { name: 'Esityskorvaukset (€) Esityskorvaukset (€)' }).fill('123');
  await page.getByLabel('Matkakulut (€)').fill('123');
  await page.getByLabel('Kuljetus (sis. autovuokrat) (€)').fill('123');
  await page.getByLabel('Tekniikka, laitevuokrat ja sähkö (€)').fill('123');
  await page.getByLabel('Kiinteistöjen käyttökulut ja vuokrat (€)').fill('123');
  await page.getByLabel('Tiedotus, markkinointi ja painatus (€)').fill('123');
  await page.getByLabel('Kuvaus menosta').fill('11wdgwgregre');
  await page.getByLabel('Määrä (€)').fill('234');
  await page.getByLabel('Sisältyykö toiminnan toteuttamiseen jotain muuta rahanarvoista panosta tai vaihtokauppaa, joka ei käy ilmi budjetista?').fill('erggergergegerger');
  await page.getByRole('button', { name: 'Seuraava >' }).click();

  // Fill step 7
  await page.getByRole('textbox', { name: 'Lisätiedot Lisätiedot' }).fill('fewqfwqfwqfqw');
  await page.getByLabel('Lisäselvitys liitteistä').fill('sdfdsfdsfdfs');
  await page.getByRole('button', { name: 'Esikatseluun >' }).click();

  // check data on confirmation page
  await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
  // await expect(page.getByText('Puuttuvat tai vajaat tiedot')).toBeHidden();
});

