import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
    await page.goto('/fi/ohjeita-hakijalle');
});


test('verify title', async ({ page }) => {
    await expect(page).toHaveTitle(/.*Ohjeita hakijalle/);
});

test('verify hero', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Ohjeita hakijalle' })).toBeVisible();
    await expect(page.getByText('Tältä sivulta löydät tietoa myönnettävistä avustuksista ja niiden hakemisesta')).toBeVisible();
});

test('table of contents', async ({ page }) => {
    const tableOfContents = page.locator('#helfi-toc-table-of-contents-list');

    await expect(tableOfContents.getByRole('link', { name: 'Tutustu myönnettäviin avustuksiin' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Yleistä tietoa avustuksista' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Miten avustushakemuksen tekeminen etenee?' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Avustushakemuksen täyttäminen' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Hakemuksen käsittely ja päätökset' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Avustusten maksaminen' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Avustusten käyttäminen ja käytön selvitys' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Lisää aiheesta' })).toBeEnabled();
    await expect(tableOfContents.getByRole('link', { name: 'Helsingin kaupungin kirjaamo' })).toBeEnabled();
});

test('Tutustu myönnettäviin avustuksiin section', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Tutustu myönnettäviin avustuksiin' })).toBeVisible();
    await expect(page.getByText('Etsi avustuksia -sivulla voit tutustua kaikkiin myönnettäviin avustuksiin. Sivuilla on kerrottu')).toBeVisible();

    await expect(page.getByText('kaupunginhallitus myöntää avustuksia hyvinvoinnin ja terveyden edistämiseen, asukasosallisuuden edistämiseen, työllistymiseen, kotouttamiseen sekä yleisavustuksiin')).toBeVisible()
    await expect(page.getByText('kasvatuksen ja koulutuksen toimiala myöntää avustuksia perusopetuslain mukaiseen koululaisten iltapäivätoimintaan')).toBeVisible()
    await expect(page.getByText('kaupunkiympäristön toimiala myöntää ympäristötoimen yleisavustusta')).toBeVisible()
    await expect(page.getByText('kulttuurin ja vapaa-ajan toimiala myöntää avustuksia nuorisotoimintaan, liikunnan sekä taiteen ja kulttuurin edistämiseen')).toBeVisible()
    await expect(page.getByText('ja sosiaali-, terveys- ja pelastustoimiala myöntää avustuksia lakisääteisiä tehtäviä täydentäville ja tukeville tahoille')).toBeVisible()
});

test('Miten avustushakemuksen tekeminen etenee', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Miten avustushakemuksen tekeminen etenee' })).toBeVisible();
    await expect(page.getByText('Valtuutus Tarkista, että yhteisösi on valtuuttanut sinut toimimaan yhteisösi edu')).toBeVisible();
    await expect(page.getByText('Luo avustusprofiili Luo hakijaprofiili tunnistautumalla vahvasti suomi.fi-palvel')).toBeVisible();
    await expect(page.getByText('Tutustu palveluun ja valitse sopiva avustus Tutustu eri avustusmuotoihin, myöntä')).toBeVisible();
    await expect(page.getByText('Valmistautuminen Valmistaudu hakemuksen täyttämiseen keräämällä tarvittavat tied')).toBeVisible();

});

test('Avustushakemuksen täyttäminen', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Avustushakemuksen täyttäminen' })).toBeVisible();
    await expect(page.getByText('Kuvatkaa hakemuksella mihin toimintaan avustusta haetaan ja mihin kulut kohdentu')).toBeVisible();
});

test('Hakemuksen käsittely ja päätökset', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Hakemuksen käsittely ja päätökset' })).toBeVisible();
    await expect(page.getByText('Hakuajan jälkeen saapuneita hakemuksia ei käsitellä.')).toBeVisible();
    await expect(page.getByText('Hakuajan puitteissa saapuneet hakemukset käsitellään hakuajan päätyttyä ja valmi')).toBeVisible();
});

test('Avustusten maksaminen', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Avustusten maksaminen' })).toBeVisible();
    await expect(page.getByText('Avustuspäätöksessä voidaan määrätä edellytyksiä avustuksen maksamiselle tai määr')).toBeVisible();
    await expect(page.getByText('Mikäli avustuspäätöksessä ei ole määrätty avustuksen maksamisesta maksetaan avus')).toBeVisible();
    await expect(page.getByText('alle 8 000 euron avustukset yhdessä erässä,')).toBeVisible();
    await expect(page.getByText('8 000–40 000 euron avustukset kahdessa erässä, ja')).toBeVisible();
    await expect(page.getByText('yli 40 000 euron avustukset neljässä erässä.')).toBeVisible();
    await expect(page.getByText('Käyttämättä jääneet avustukset on palautettava takaisin kaupungille.')).toBeVisible();
});

test('Avustusten käyttäminen ja käytön selvitys', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Avustusten käyttäminen ja käytön selvitys' })).toBeVisible();
    await expect(page.getByText('Avustusta saadaan käyttää vain avustuspäätöksessä mainittuun tarkoitukseen. Jos ')).toBeVisible();
    await expect(page.getByText('Avustuksen hakijan on uutta avustusta Helsingin kaupungilta hakiessaan toimitett')).toBeVisible();
});

test('Lisää aiheesta', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Lisää aiheesta' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Palvelun käyttöohjeet' })).toBeVisible();
});

test('Helsingin kaupungin kirjaamo', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Helsingin kaupungin kirjaamo' }).getByRole('link')).toBeVisible()
    await expect(page.getByText('Kirjaamon asiakaspalvelu palvelee arkisin klo 8.15 - 16.00')).toBeVisible();
    await expect(page.getByText('Kirjaamolle voi myös jättää postia kaupungintalon ala-aulassa sijaitsevaan postilaatikkoon')).toBeVisible();
    await expect(page.getByText('Jos lähetät sähköpostitse arkaluontoista tai salassa pidettävää aineistoa, käytäthän suojattua sähköpostia osoitteessa securemail.hel.fi')).toBeVisible();
});