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
};

export {
  pageCollection,
}
