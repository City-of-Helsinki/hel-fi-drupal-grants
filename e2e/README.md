## E2E tests

E2E (End-to-End) tests ensure the whole application works as intended from a user's perspective. We use [Playwright](https://playwright.dev/) for our E2E testing.

### Prerequisites

- [Node.js 16+](https://nodejs.org/) or Docker


### Running E2E tests in Docker

You can run E2E tests in a Docker container. To do this:

    make test-pw

To run a specific test file in the container, use:

    make test-pw path/to/test/file


### Running E2E tests locally

Go to the tests directory

    cd e2e

Install the necessary dependencies:

    npm install

To run the tests:

    npx playwright test

For an interactive UI mode:

    npx playwright test --ui

To execute tests in a specific file:

    npx playwright test path/to/test/file

To view the test report:

    npx playwright show-report

