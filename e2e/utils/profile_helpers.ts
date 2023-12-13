import { faker } from '@faker-js/faker';
import { Locator, Page, expect, test } from '@playwright/test';

const checkContactInfoPrivatePerson = async (page:Page) => {
  await expect(page.getByRole('heading', { name: 'Omat tiedot' })).toBeVisible()

  // Perustiedot
  await expect(page.getByRole('heading', { name: 'Perustiedot' })).toBeVisible()
  await expect(page.getByText('Etunimi')).toBeVisible()
  await expect(page.getByText('Sukunimi')).toBeVisible()
  await expect(page.getByText('Henkilötunnus')).toBeVisible()
  await expect(page.getByRole('link', { name: 'Siirry Helsinki-profiiliin päivittääksesi sähköpostiosoitetta' })).toBeVisible()

  // Omat yhteystiedot
  await expect(page.getByRole('heading', { name: 'Omat yhteystiedot' })).toBeVisible()
  await expect(page.locator("#addresses").getByText('Osoite')).toBeVisible()
  await expect(page.locator("#phone-number").getByText('Puhelinnumero')).toBeVisible()
  await expect(page.locator("#officials-3").getByText('Tilinumerot')).toBeVisible()
  await expect(page.getByRole('link', { name: 'Muokkaa omia tietoja' })).toBeVisible()
}


export {
  checkContactInfoPrivatePerson
}
