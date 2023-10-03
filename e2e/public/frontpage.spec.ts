import { expect, test } from '@playwright/test';


test.beforeEach(async ({ page }) => {
    await page.goto('/');
});


test('verify title', async ({ page }) => {
    await expect(page).toHaveTitle(/.*Avustusasiointi/);
});

test('verify hero', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Avustukset' })).toBeVisible()
    await expect(page.getByText('Helsingin kaupungin avustukset helsinkiläisille järjestöille, yhteisöille, asukasryhmille ja yksityisille henkilöille')).toBeVisible()
    await expect(page.locator('.hero').getByRole('link', { name: 'Tietoa avustuksista' })).toBeVisible()
});

test('verify info block', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Uuteen avustusasiointiin siirtyminen tapahtuu vaiheittain vuoden 2023 aikana' })).toBeVisible();

    const textArray = [
        'Avustuslomakkeita työstetään uuteen asiointiin kovaa vauhtia!',
        'Mikäli avustushakemuksesi löytyy listalta: jatka asiointia tällä sivustolla',
        'Kasvatus ja koulutus: Yleisavustushakemus',
        'Kasvatus ja koulutus: Iltapäivätoiminnan harkinnanvarainen lisäavustushakemus',
        'Kulttuuri: Taide- ja kulttuuriavustusten projektiavustukset',
        'Kulttuuri: Taiteen perusopetuksen avustukset',
        'Liikunta: Yleisavustushakemus',
        'HUOM! Mikäli et löydä hakemustasi listalta: Siirry vanhaan asiointiin.'
      ];
    
      for (const text of textArray) {
        await expect(page.getByText(text)).toBeVisible();
      }

    await expect(page.getByRole('link', { name: 'Siirry avustuksen vanhoille sivuille (Linkki avautuu uuteen välilehteen.) (Linkki johtaa ulkoiseen palveluun)' })).toBeVisible();
});

test('verify Näillä Sivuilla section', async ({ page }) => {
    await expect(page.locator(".component--list-of-links").getByRole('link', { name: 'Tietoa avustuksista' })).toBeVisible();
    await expect(page.getByText("Lue lisää kaupungin avustuksista ja niiden myöntämisperusteista.")).toBeVisible()

    await expect(page.locator(".component--list-of-links").getByRole('link', { name: 'Etsi avustusta' })).toBeVisible();
    await expect(page.getByText("Etsi sopivaa avustusta, lue lisätietoja ja siirry jättämään avustushakemus")).toBeVisible()
    
    await expect(page.locator(".component--list-of-links").getByRole('link', { name: 'Ohjeita hakijalle' })).toBeVisible();
    await expect(page.getByText("Apua hakemuksen tekemiseen ja vastauksia usein kysyttyihin kysymyksiin")).toBeVisible()
});

test('verify banner', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Pääset täyttämään hakemusta kirjautumalla omaan asiointiin ja luomalla hakijaprofiilin' })).toBeVisible();
    await expect(page.getByText("Palvelussa pääset hakemaan avustusta Helsingin kaupungilta sekä päivittämään avustushakemiseen liittyviä tietoja")).toBeVisible()
    await expect(page.getByRole('button', { name: 'Kirjaudu sisään' })).toBeVisible()
});

test('verify news section', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Ajankohtaista avustuksista' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Katso kaikki ajankohtaiset' })).toBeVisible()
});

test('verify help section', async ({ page }) => {
    await expect(page.locator(".liftup-with-image").getByRole('heading', { name: 'Tarvitsetko apua hakemuksen tekemiseen?' })).toBeVisible();
    await expect(page.locator(".liftup-with-image").getByText("Hakemuksen tekeminen voi olla haastavaa, joten kokosimme avuksesi kattavan infopaketin")).toBeVisible()
    await expect(page.locator(".liftup-with-image").getByRole('link', { name: 'Ohjeita hakijalle' })).toBeVisible()
    await expect(page.locator(".liftup-with-image").getByRole('link', { name: 'Tietoa avustuksista' })).toBeVisible()
});

test('has a login button', async ({ page }) => {
    const loginLink = page.getByRole('link', { name: 'Kirjaudu' })
    await expect(loginLink).toBeVisible()
});

test('contains news', async ({ page }) => {
    const newsBlockHeader = page.getByRole('heading', { name: 'Ajankohtaista avustuksista' })
    await expect(newsBlockHeader).toBeVisible()

    // A news article is visible
    expect(page.locator('.news-listing__item')).toBeTruthy()

    const linkToNewsPage = page.getByRole('link', { name: 'Katso kaikki ajankohtaiset' })
    await expect(linkToNewsPage).toBeVisible()
})

test('verify Sinua Voisi Kiinnostaa section', async ({ page }) => {
    await expect(page.getByRole('heading', { name: 'Sinua voisi kiinnostaa' })).toBeVisible()
    
    await expect(page.getByRole('link', { name: 'Yleiset avustusehdot' })).toBeVisible()
    await expect(page.getByText("Helsingin kaupungin myöntämissä avustuksissa noudatettavat yleiset periaatteet ja menettelytavat.")).toBeVisible()

    await expect(page.getByRole('link', { name: 'Tilavaraukset' })).toBeVisible()
    await expect(page.getByText("Voit varata kaupungin tiloja ja laitteita helposti käyttöösi.")).toBeVisible()
    
    await expect(page.getByRole('link', { name: 'Päätökset-palvelu (Linkki johtaa ulkoiseen palveluun)' })).toBeVisible()
    await expect(page.getByText("Päätökset-palvelusta löydät kaupungin päätöksentekoon liittyvät tiedot yhdestä paikasta.")).toBeVisible()
});
