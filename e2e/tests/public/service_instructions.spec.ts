import { expect, test } from '@playwright/test';

test.beforeEach(async ({ page }) => {
    await page.goto('/fi/ohjeita-hakijalle/palvelun-kayttoohjeet');
});


test('title', async ({ page }) => {
    await expect(page).toHaveTitle(/.*Palvelun käyttöohjeet/);
});

test('heading', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Palvelun käyttöohjeet' })).toBeVisible();
});

test('Avustusasiointi', async ({ page }) => {
    await page.getByRole('button', { name: 'Avustusasiointi' }).click();
    await expect(page.getByText('Käy tutustumassa erilaisiin avustuksiin, se onnistuu ilman vahvaa tunnistautumis')).toBeVisible();
});

test('Tietoa avustuksista', async ({ page }) => {
    await page.getByRole('heading', { name: 'Tietoa avustuksista' }).getByRole('button').click();
    await page.getByText('Tietoa avustuksista -sivuilla on koottu näkymä Helsingin kaupungin myöntämistä e').click();
    await page.getByText('Käy tutustumassa hakemiseen ja hakemukseen liittyvään tietoon ennen hakuprosessi').click();
});

test.fixme('Etsi avustusta', async ({ page }) => {
    await page.getByRole('button', { name: 'Etsi avustusta' });
    await page.getByText('Etsi avustusta välilehdeltä pääset palvelusivuille, josta löytyvät kuvaukset kun').click();
    await page.getByText('Tutustu avustushakemuksen palvelusivuilla avustusehtoihin ja -vaatimuksiin huole').click();
});

test.fixme('Ohjeita hakijalle', async ({ page }) => {
    await page.getByRole('heading', { name: 'Ohjeita hakijalle' }).getByRole('button').click();
    await page.getByText('Ohjeita hakijalle välilehdeltä pääset tutustumaan kaupungin yleisiin avustusohje').click();
});

test('Kirjautuminen ja tunnistautuminen', async ({ page }) => {
    await page.getByRole('button', { name: 'Kirjautuminen ja tunnistautuminen' }).click();
    await page.getByText('Aloita kirjautuminen palveluun kirjautumislinkistä tai oikean yläkulman kuvakkee').click();
    await page.getByText('Tunnistaudu vahvasti suomi.fi -tunnistautumisella ja anna Helsinki profiilille s').click();
    await page.getByText('Olet tehnyt kirjautumisen yksityishenkilönä ja olet siirtynyt vahvasti tunnistet').click();
    await page.getByText('Yrityksen tai yhteisön puolesta asioidessasi vaihda asiointirooli kuvakkeesta:').click();
    await page.getByText('Rekisteröityneen yhteisön valtuuttama (valtuustarkistus suomi.fi-valtuudet - puo').click();
    await page.getByText('Rekisteröitymättömän ryhmän puolesta asioiva (vahvasti tunnistettu henkilö allek').click();
    await page.getByText('Yksityishenkilö (vahvasti tunnistettu henkilö allekirjoittajana)').click();
    await page.getByText('Suomi.fi-palvelu näyttää yhteisöt, joiden puolesta sinulla on valtuutus asioida.').click();
    await page.getByText('Valitse yhteisö ja siirry asiointipalveluun').click();
    await page.getByText('Lisätietoja Suomi.fi valtuuksista').click();
    await page.getByText('Avustushakemuksen tekemiseen tarvittava valtuuskoodi Suomi.fi-valtuuspalvelussa ').click();
    await page.getByText('Avustushakemuksen tekeminen valtuudella valtuutettu voi valtuuttajan puolesta va').click();
    await page.getByText('Lisätietoja Suomi.fi-palvelusta:').click();
    await page.getByText('Kun lopetat asioimisen. Kirjaudu ulos sivun oikeassa yläkulmassa olevasta kirjau').click();
    await page.getByText('Selaimen välimuisti pitää tyhjentää, kirjautumisen jälkeen. Yhteiskäyttöisillä l').click();
});

test('Omat tiedot', async ({ page }) => {
    await page.getByRole('button', { name: 'Omat tiedot' }).click();
    await page.getByText('Omat tiedot luodaan ja ylläpidetään Oma asiointi -kohdan Omat tiedot').click();
    await page.getByText('Osa tiedoista tulee vahvan tunnistuksen kautta omiin tietoihin valmiina, eikä ni').click();
    await page.getByText('Ylläpidä omia tietoja painamalla Muokkaa omia tietoja -painiketta sivun vasemmas').click();
});

test.fixme('Hakemus ja liitteet', async ({ page }) => {
    await page.getByRole('button', { name: 'Hakemus ja liitteet' }).click();
    await page.getByText('Tietoja avustuksista tai Etsi avustusta -välilehdiltä pääset kunkin avustustyypi').click();
    await page.getByText('Liitetiedostoja voidaan liittää hakemukseen ja hakemusta koskevaan viestiin. Lii').click();
    await page.getByText('Hakemuksen yhteyteen lisättävien liitteiden koko ja tiedostotyypit:').click();
    await page.getByText('Rajoitus: 32 MB. Sallitut tiedostotyypit: doc, docx, gif, jpg, jpeg, pdf, png, p').click();
});

test('Oma asiointi ja viestitoiminnallisuus', async ({ page }) => {
    await page.getByRole('button', { name: 'Oma asiointi ja viestitoiminnallisuus' }).click();
    await page.getByText('Omassa asioinnissa voit päivittää lähettämääsi hakemusta ja seurata hakemuksen k').click();
    await page.getByText('Avustusasioinnin viestit liittyvät aina tiettyyn lähetettyyn hakemukseen. Lähete').click();
    await page.getByText('Viestin mukana voit lähettää liitteen. Lataa tiedosto painamalla lisää tiedosto ').click();
});
