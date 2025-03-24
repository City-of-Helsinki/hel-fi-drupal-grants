import {PageCollection} from "./test_data";

const pageCollection: PageCollection = {
  "front_page": {
    url: "/fi/avustukset",
    validatePageTitle: true,
    components: [
      {
        containerClass: ".hero",
        elements: [
          { selector: ".hero__title", countExact: 1 },
          { selector: "a", countExact: 1 },
        ],
      },
      {
        containerClass: ".component--list-of-links",
        elements: [
          { selector: ".list-of-links__item", countAtLeast: 3 }
        ],
        occurrences: 2,
      },
      {
        containerClass: ".component--banner",
        elements: [
          { selector: ".banner__title", countExact: 1 },
          { selector: ".banner__desc", countExact: 1 },
          { selector: "#edit-openid-connect-client-tunnistamo-login", countExact: 1 },
        ],
      },
      {
        containerClass: ".component--liftup-with-image",
        elements: [
          { selector: ".liftup-with-image__title", countExact: 1 },
          { selector: ".liftup-with-image__desc", countExact: 1 },
          { selector: "figure", countExact: 1 },
        ],
      },
      {
        containerClass: ".component--news-list",
        elements: [
          { selector: ".component__title", countExact: 1 },
          { selector: ".component__content", countExact: 1 },
        ],
      },
      {
        containerClass: "#header-branding",
        elements: [
          { selector: ".profile__login-link", countExact: 1, expectedText: ['Kirjaudu']},
        ],
      },
      {
        containerClass: "#block-mainnavigation",
        elements: [
          { selector: ".menu--level-0", countExact: 1},
          { selector: ".menu--level-0 > .menu__item", countAtLeast: 3},
        ],
      },
      {
        containerClass: ".footer-top",
        elements: [
          { selector: ".menu__item", countAtLeast: 5},
        ],
      },
      {
        containerClass: ".footer-bottom",
        elements: [
          { selector: ".menu__item",
            countAtLeast: 5,
            expectedText: ['Saavutettavuusseloste', 'Tietosuoja', 'Tietoa hel.fistä', 'Evästeasetukset'],
          },
        ],
      },
      {
        containerClass: ".hds-cc--banner",
        elements: [
          { selector: "h2.hds-cc__heading",
            countExact: 1,
            expectedText: ['Evästeet avustukset.hel.fi-sivustolla'],
          },
          { selector: ".hds-cc__description",
            countExact: 1,
            expectedText: ['Tämä sivusto käyttää välttämättömiä evästeitä sivun perustoimintojen ja suorituskyvyn varmistamiseksi. Lisäksi käytämme kohdennusevästeitä käyttäjäkokemuksen parantamiseksi, analytiikkaan ja yksilöidyn sisällön näyttämiseen.'],
          },
          { selector: ".hds-cc__accordion-button",
            countAtLeast: 1,
            expectedText: ['Näytä yksityiskohdat'],
          },
          { selector: ".hds-cc__all-cookies-button",
            countExact: 1,
            expectedText: ['Hyväksy kaikki evästeet'],
          },
          { selector: ".hds-cc__required-cookies-button",
            countExact: 1,
            expectedText: ['Hyväksy vain välttämättömät evästeet'],
          },
        ],
      },
    ]
  },
  "about_grants": {
    url: "/fi/tietoa-avustuksista",
    validatePageTitle: true,
    components: [
      {
        containerClass: ".hero",
        elements: [
          { selector: ".hero__title", countExact: 1 },
        ],
      },
      {
        containerClass: ".component--list-of-links",
        elements: [
          { selector: ".list-of-links__item", countAtLeast: 1 }
        ],
        occurrences: 5,
      },
      {
        containerClass: ".component--news-list",
        elements: [
          { selector: ".component__title", countExact: 1 },
          { selector: ".component__content", countExact: 1 },
        ],
      },
    ]
  },
  "applicant_instructions": {
    url: "/fi/ohjeita-hakijalle",
    validatePageTitle: true,
    components: [
      {
        containerClass: ".page-title",
        elements: [
          { selector: "h1", countExact: 1 },
        ],
      },
      {
        containerClass: ".component--lead-in",
        elements: [
          { selector: ".component__content", countExact: 1 },
        ],
      },
      {
        containerClass: ".component--list-of-links",
        elements: [
          { selector: ".list-of-links__item", countAtLeast: 1 },
        ],
      },
      {
        containerClass: ".table-of-contents",
        elements: [
          { selector: ".table-of-contents__item", countAtLeast: 1 },
        ],
      },
      {
        containerClass: ".component--phasing",
        elements: [
          { selector: ".phasing__item", countAtLeast: 4 },
        ],
      },
    ]
  },
  "my_services": {
    url: "/fi/oma-asiointi",
    validatePageTitle: true,
    components: [
      {
        containerClass: ".hero",
        elements: [
          { selector: ".hero__title", countExact: 1 },
        ],
      },
      {
        containerClass: ".oma-asiointi-infoboxes-container",
        elements: [
          { selector: "h2", countExact: 1 },
          { selector: ".oma-asiointi-infobox", countExact: 2 },
        ],
      },
      {
        containerClass: ".oma-asiointi-infobox",
        elements: [
          { selector: "h3", countExact: 1 },
          { selector: "p", countExact: 1 },
          { selector: "a", countExact: 1 },
        ],
        occurrences: 2,
      },
      {
        containerClass: "#oma-asiointi__drafts",
        elements: [
          { selector: "h2", countExact: 1, expectedText: ['Keskeneräiset hakemukset'] },
          { selector: ".application-list", countExact: 1 },
        ],
      },
      {
        containerClass: "#oma-asiointi__sent",
        elements: [
          { selector: "h2", countExact: 1, expectedText: ['Lähetetyt hakemukset'] },
          { selector: ".application-list", countExact: 1 },
          { selector: ".application-list-filter", countExact: 1, expectedText: ['Etsi hakemusta'] },
          { selector: "label[for='checkbox-processed']", countExact: 1, expectedText: ['Näytä vain käsittelyssä olevat hakemukset'] },
          { selector: ".application-list__search-row .hds-button", countExact: 1, expectedText: ['Etsi hakemusta'] },
        ],
      },
    ]
  },
  "search_page": {
    url: "/fi/etsi-avustusta",
    validatePageTitle: true,
    components: [
      {
        containerClass: ".hero",
        elements: [
          { selector: ".hero__title", countExact: 1 },
        ],
      },
      {
        containerClass: ".application_search--filters",
        elements: [
          { selector: ".form-item-target-group", countExact: 1, expectedText: ['Valitse kohderyhmä'] },
          { selector: ".form-item-activity", countExact: 1, expectedText: ['Millaiseen toimintaan haet avustusta?'] },
          { selector: ".form-item-applicant", countExact: 1, expectedText: ['Avustuksen hakija'] },
          { selector: ".form-item-search", countExact: 1, expectedText: ['Tai etsi hakusanalla'] },
          { selector: ".form-item-application-open", countExact: 1, expectedText: ['Näytä vain haettavissa olevat avustukset'] },
          { selector: ".form-submit", countExact: 1, expectedText: ['Etsi'] },
        ],
      },
      {
        containerClass: ".application_search--rows",
        elements: [
          { selector: ".application_search--row", countAtLeast: 3 },
        ],
      },
    ]
  },
  "service_instructions": {
    url: "/fi/ohjeita-hakijalle/palvelun-kayttoohjeet",
    validatePageTitle: true,
    components: [
      {
        containerClass: ".page-title",
        elements: [
          { selector: "h1", countExact: 1 },
        ],
      },
      {
        containerClass: ".component--list-of-links",
        elements: [
          { selector: ".list-of-links__item", countAtLeast: 4 }
        ],
      },
    ]
  },
};

export {
  pageCollection,
}
