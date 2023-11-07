import { faker } from '@faker-js/faker';
import { Page, expect, test } from '@playwright/test';
import { clickContinueButton, clickGoToPreviewButton, saveAsDraft, selectRole } from '../../utils/helpers';

type UserInputData = Record<string, string>

const formInputData = {
  additionalInformation: faker.lorem.words(),
  attachmentInfo: faker.lorem.words(),
  email: faker.internet.email(),
  fullName: faker.person.fullName(),
  phoneNumber: faker.phone.number(),
  shortDescription: faker.lorem.words(),
}

const messageContent = faker.lorem.words();

test.describe('Taiteen perusopetuksen avustukset', () => {
  test.beforeEach(async ({ page }) => {
    await selectRole(page, 'REGISTERED_COMMUNITY');
    await page.goto('fi/uusi-hakemus/taide_ja_kulttuuriavustukset_tai')
  });

  test('Submit application and send message', async ({ page }) => {
    await fillStepOne(page);
    await fillStepTwo(page)
    await fillStepThree(page)
    await fillStepFour(page)
    await fillStepFive(page)
    await fillStepSix(page)
    await fillStepSeven(page)

    await checkConfirmationPage(page, formInputData);
    await submitApplication(page);
    await checkSentApplication(page, formInputData);
    await sendMessageToApplication(page, messageContent);
  });

  test('Application can be saved as a draft', async ({ page }) => {
    await fillStepOne(page);
    await saveAsDraft(page);

    // Check application draft page
    await expect(page.getByText('Luonnos')).toBeVisible()
    await expect(page.getByRole('link', { name: 'Muokkaa hakemusta' })).toBeEnabled();
    const applicationId = await page.locator(".webform-submission__application_id--body").innerText()
    const pageText = await page.getByLabel('1. Hakijan tiedot').innerText();

    const textsToCheck = [formInputData.email, formInputData.fullName, formInputData.phoneNumber];
    textsToCheck.forEach(t => expect(pageText).toContain(t))

    // Check if application is shown in "Keskeneräiset hakemukset"
    await page.goto("fi/oma-asiointi")
    const drafts = await page.locator("#oma-asiointi__drafts").innerText()
    expect(drafts).toContain(applicationId)
  });

  test('Draft can be removed', async ({ page }) => {
    await fillStepOne(page);
    await page.getByRole('button', { name: 'Tallenna keskeneräisenä' }).click();
    await page.getByRole('link', { name: 'Muokkaa hakemusta' }).click();
    await page.getByRole('link', { name: 'Poista luonnos' }).click();
    await expect(page.getByText('Luonnos poistettu.')).toBeVisible({ timeout: 10 * 1000 })
  });

  test('Check errors for required fields', async ({ page }) => {
    await page.getByLabel('2. Avustustiedot').click();
    await page.getByLabel('3. Yhteisön tiedot').click();
    await page.getByLabel('4. Toiminta').click();
    await page.getByLabel('5. Toiminnan lähtökohdat').click();
    await page.getByLabel('6. Talous').click();
    await page.getByLabel('7. Lisätiedot ja liitteet').click();
    await page.getByLabel('8. Vahvista, esikatsele ja lähetä').click();

    const errorNotificationText = await page.locator(".container").locator(".hds-notification--error").innerText();

    const textsToCheck = [
      "Hakijan tiedot: Hakemusta koskeva sähköposti kenttä",
      "Hakijan tiedot: Yhteyshenkilö kenttä",
      "Hakijan tiedot: Puhelinnumero kenttä",
      "Hakijan tiedot: Valitse tilinumero kenttä",
      "Hakijan tiedot: Yhteisön osoite kenttä",
      "Hakijan tiedot: Valitse osoite kenttä",
      "Avustustiedot: Vuosi, jolle haen avustusta kenttä",
      "Avustustiedot: Sinun on syötettävä vähintään yhdelle avustuslajille summa",
      "Avustustiedot: Ensisijainen taiteenala kenttä",
      "Avustustiedot: Hankkeen tai toiminnan lyhyt esittelyteksti kenttä",
      "Yhteisön tiedot: Taiteellisen toiminnan tilaa omistuksessa tai ympärivuotisesti päävuokralaisena kenttä",
      "Toiminta: Tilan nimi kenttä",
      "Toiminta: Kaupunki omistaa tilan kenttä",
      "Talous: Organisaatio kuuluu valtionosuusjärjestelmään (VOS) kenttä",
      "Talous: Valtion toiminta-avustus (€) kenttä",
      "Talous: Muut avustukset (€) kenttä",
      "Talous: Yksityinen rahoitus (esim. sponsorointi, yritysyhteistyö,lahjoitukset) (€) kenttä",
      "Talous: Pääsy- ja osallistumismaksut (€) kenttä",
      "Talous: Muut oman toiminnan tulot (€) kenttä",
      "Talous: Rahoitus- ja korkotulot (€) kenttä",
      "Talous: Menot yhteensä (€) kenttä",
      "Talous: Organisaatio kuului valtionosuusjärjestelmään (VOS) kenttä",
      "Talous: Helsingin kaupungin kulttuuripalveluiden toiminta-avustus (€) kenttä",
      "Talous: Valtion toiminta-avustus (€) kenttä",
      "Talous: Muut avustukset (€) kenttä",
      "Talous: Tulot yhteensä (€) kenttä",
      "Talous: Menot yhteensä (€) kenttä",
      "Lisätiedot ja liitteet: Yhteisön säännöt ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.",
      "Lisätiedot ja liitteet: Vahvistettu tilinpäätös (edelliseltä päättyneeltä tilivuodelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.",
      "Lisätiedot ja liitteet: Vahvistettu toimintakertomus (edelliseltä päättyneeltä tilivuodelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.",
      "Lisätiedot ja liitteet: Vahvistettu tilin- tai toiminnantarkastuskertomus (edelliseltä päättyneeltä tilivuodelta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.",
      "Lisätiedot ja liitteet: Toimintasuunnitelma (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu.",
      "Lisätiedot ja liitteet: Talousarvio (sille vuodelle jolle haet avustusta) ei sisällä liitettyä tiedostoa, se täytyy toimittaa joko myöhemmin tai olla jo toimitettu."
    ];

    textsToCheck.forEach(t => expect(errorNotificationText).toContain(t))
  });
})


const fillStepOne = async (page: Page) => {
  await page.getByRole('textbox', { name: 'Hakemusta koskeva sähköposti' }).fill(formInputData.email);
  await page.getByLabel('Yhteyshenkilö').fill(formInputData.fullName);
  await page.getByLabel('Puhelinnumero').fill(formInputData.phoneNumber);
  await page.locator('select#edit-community-address-community-address-select').selectOption({ index: 1 });
  await page.locator('#edit-bank-account-account-number-select').selectOption({ index: 1 });
  
  const correspondingPersonSelect = page.getByLabel('Valitse vastaava henkilö');
  const optionsCount = await correspondingPersonSelect.locator("option").count()

  if (optionsCount > 1) {
    await correspondingPersonSelect.selectOption('0');
  }

  await clickContinueButton(page);
};

const fillStepTwo = async (page: Page) => {
  await page.locator('#edit-acting-year').selectOption('2024');
  await page.locator('#edit-subventions-items-0-amount').fill('123,00€');
  await page.locator('#edit-ensisijainen-taiteen-ala').selectOption('Sirkus');
  await page.getByRole('textbox', { name: 'Hankkeen tai toiminnan lyhyt esittelyteksti' }).fill(formInputData.shortDescription);
  await clickContinueButton(page);
};

const fillStepThree = async (page: Page) => {
  await expect(page.getByLabel('Helsinkiläisiä henkilöjäseniä yhteensä')).toBeVisible()
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
};

const fillStepFour = async (page: Page) => {
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
};

const fillStepFive = async (page: Page) => {
  await page.getByLabel('Miten monimuotoisuus ja tasa-arvo toteutuu ja näkyy toiminnan järjestäjissä ja organisaatioissa sekä toiminnan sisällöissä? Minkälaisia toimenpiteitä, resursseja ja osaamista on asian edistämiseksi?').fill('wegewgewggew');
  await page.getByLabel('Miten toiminta tehdään kaupunkilaiselle sosiaalisesti, kulttuurisesti, kielellisesti, taloudellisesti, fyysisesti, alueellisesti tai muutoin mahdollisimman saavutettavaksi? Minkälaisia toimenpiteitä, resursseja ja osaamista on asian edistämiseksi?').fill('ergregre');
  await page.getByLabel('Miten ekologisuus huomioidaan toiminnan järjestämisessä? Minkälaisia toimenpiteitä, resursseja ja osaamista on asian edistämiseksi?').fill('ergreggre');
  await page.getByLabel('Mitkä olivat keskeisimmät edelliselle vuodelle asetetut tavoitteet ja saavutettiinko ne?').fill('ergerger');
  await page.getByLabel('Millaisia keinoja käytetään itsearviointiin ja toiminnan kehittämiseen?').fill('eerggerger');
  await page.getByLabel('Mitkä ovat tulevalle vuodelle suunnitellut keskeisimmät muutokset toiminnassa ja sen järjestämisessä suhteessa aikaisempaan?').fill('ergergerger');
  await clickContinueButton(page);
};

const fillStepSix = async (page: Page) => {
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
};

const fillStepSeven = async (page: Page) => {
  await page.getByRole('textbox', { name: 'Lisätiedot' }).fill(formInputData.additionalInformation);
  await page.getByRole('group', { name: 'Yhteisön säännöt Yhteisön säännöt' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu tilinpäätös' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu toimintakertomus' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Vahvistettu tilin- tai toiminnantarkastuskertomus' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Toimintasuunnitelma' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByRole('group', { name: 'Talousarvio (sille vuodelle jolle haet avustusta)' }).getByLabel('Liite toimitetaan myöhemmin').check();
  await page.getByLabel('Lisäselvitys liitteistä').fill(formInputData.attachmentInfo);

  await clickGoToPreviewButton(page);
};

const checkConfirmationPage = async (page: Page, userInputData: UserInputData) => {
  const previewText = await page.locator("table").innerText()
  Object.values(userInputData).forEach(value => expect(previewText).toContain(value))

  await page.getByLabel('Vakuutamme, että hakemuksessa ja sen liitteissä antamamme tiedot ovat oikeita, ja hyväksymme avustusehdot').check();
}

const submitApplication = async (page: Page) => {
  await page.getByRole('button', { name: 'Lähetä' }).click();
  await expect(page.getByRole('heading', { name: 'Avustushakemus lähetetty onnistuneesti' })).toBeVisible();
  await expect(page.getByText('Lähetetty - odotetaan vahvistusta').first()).toBeVisible()
  await expect(page.getByText('Vastaanotettu', { exact: true })).toBeVisible({ timeout: 30 * 1000 })
}

const checkSentApplication = async (page: Page, userInputData: UserInputData) => {
  await page.getByRole('link', { name: 'Katsele hakemusta' }).click();

  await expect(page.getByRole('heading', { name: 'Hakemuksen tiedot' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Tulosta hakemus' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Kopioi hakemus' })).toBeVisible();

  const applicationData = await page.locator(".webform-submission").innerText()

  Object.values(userInputData).forEach(value => expect(applicationData).toContain(value))
}

const sendMessageToApplication = async (page: Page, message: string) => {
  await page.getByLabel('Viesti').fill(message);
  await page.getByRole('button', { name: 'Lähetä' }).click();
  await expect(page.getByLabel('Notification').getByText('Viestisi on lähetetty.')).toBeVisible();
  const submissionMessages = await page.locator(".webform-submission-messages").innerText()
  expect(submissionMessages).toContain(message)
}
