import {PageCollection} from "./test_data";

const pageCollection: PageCollection = {
  "front_page": {
    url: "/fi/avustukset",
    validatePageTitle: true,
    components: [
      {
        containerClass: ".hero",
        elements: [
          { selector: ".hero__title", count: 1 },
          { selector: "a", count: 1 },
        ],
      },
      {
        containerClass: ".component--list-of-links",
        elements: [
          { selector: ".list-of-links__item", count: 3 }
        ],
        occurrences: 2,
      },
      {
        containerClass: ".component--banner",
        elements: [
          { selector: ".banner__title", count: 1 },
          { selector: ".banner__desc", count: 1 },
          { selector: "#edit-openid-connect-client-tunnistamo-login", count: 1 },
        ],
      },
      {
        containerClass: ".component--liftup-with-image",
        elements: [
          { selector: ".liftup-with-image__title", count: 1 },
          { selector: ".liftup-with-image__desc", count: 1 },
          { selector: "figure", count: 1 },
        ],
      },
      {
        containerClass: ".component--news-list",
        elements: [
          { selector: ".component__title", count: 1 },
          { selector: ".component__content", count: 1 },
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
          { selector: ".hero__title", count: 1 },
        ],
      },
      {
        containerClass: ".component--list-of-links",
        elements: [],
        occurrences: 5,
      },
      {
        containerClass: ".component--news-list",
        elements: [
          { selector: ".component__title", count: 1 },
          { selector: ".component__content", count: 1 },
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
          { selector: "h1", count: 1 },
        ],
      },
      {
        containerClass: ".component--lead-in",
        elements: [
          { selector: ".component__content", count: 1 },
        ],
      },
      {
        containerClass: ".component--list-of-links",
        elements: [
          { selector: ".list-of-links__item", count: 1 },
        ],
      },
      {
        containerClass: ".component--content-liftup",
        elements: [
          { selector: ".content-liftup__image", count: 1 },
          { selector: ".content-liftup__text", count: 1 },
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
          { selector: ".hero__title", count: 1 },
        ],
      },
      {
        containerClass: ".oma-asiointi-infoboxes-container",
        elements: [
          { selector: "h2", count: 1 },
          { selector: ".oma-asiointi-infobox", count: 2 },
        ],
      },
      {
        containerClass: ".oma-asiointi-infobox",
        elements: [
          { selector: "h3", count: 1 },
          { selector: "p", count: 1 },
          { selector: "a", count: 1 },
        ],
        occurrences: 2,
      },
      {
        containerClass: "#oma-asiointi__drafts",
        elements: [
          { selector: "h2", count: 1 },
          { selector: ".application-list", count: 1 },
        ],
      },
      {
        containerClass: "#oma-asiointi__sent",
        elements: [
          { selector: "h2", count: 1 },
          { selector: ".application-list", count: 1 },
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
          { selector: ".hero__title", count: 1 },
        ],
      },
      {
        containerClass: ".application_search--filters",
        elements: [
          { selector: ".form-item-target-group", count: 1 },
          { selector: ".form-item-activity", count: 1 },
          { selector: ".form-item-applicant", count: 1 },
          { selector: ".form-item-search", count: 1 },
          { selector: ".form-item-application-open", count: 1 },
          { selector: ".form-submit", count: 1 },
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
          { selector: "h1", count: 1 },
        ],
      },
      {
        containerClass: ".component--list-of-links",
        elements: [
          { selector: ".list-of-links__item", count: 4 }
        ],
      },
    ]
  },
};

export {
  pageCollection,
}
