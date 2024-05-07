import {PageCollection} from "./test_data";

const pageCollection: PageCollection = {
  "front_page": {
    url: "/fi/avustukset",
    validatePageTitle: true,
    components: [
      {
        className: ".hero",
        elements: [
          { selector: ".hero__title", count: 1 },
          { selector: "a", count: 1 },
        ],
      },
      {
        className: ".component--list-of-links",
        elements: [
          { selector: ".list-of-links__item", count: 3 }
        ],
        occurrences: 2,
      },
      {
        className: ".component--banner",
        elements: [
          { selector: ".banner__title", count: 1 },
          { selector: ".banner__desc", count: 1 },
          { selector: "#edit-openid-connect-client-tunnistamo-login", count: 1 },
        ],
      },
      {
        className: ".component--liftup-with-image",
        elements: [
          { selector: ".liftup-with-image__title", count: 1 },
          { selector: ".liftup-with-image__desc", count: 1 },
          { selector: "figure", count: 1 },
        ],
      },
      {
        className: ".component--news-list",
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
        className: ".hero",
        elements: [
          { selector: ".hero__title", count: 1 },
        ],
      },
      {
        className: ".component--list-of-links",
        elements: [],
        occurrences: 5,
      },
      {
        className: ".component--news-list",
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
        className: ".page-title",
        elements: [
          { selector: "h1", count: 1 },
        ],
      },
      {
        className: ".component--lead-in",
        elements: [
          { selector: ".component__content", count: 1 },
        ],
      },
      {
        className: ".component--list-of-links",
        elements: [
          { selector: ".list-of-links__item", count: 1 },
        ],
      },
      {
        className: ".component--content-liftup",
        elements: [
          { selector: ".content-liftup__image", count: 1 },
          { selector: ".content-liftup__text", count: 1 },
        ],
      },
    ]
  },
};

export {
  pageCollection,
}
