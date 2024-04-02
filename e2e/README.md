# E2E tests with Playwright
This directory contains E2E test that are implemented with [Playwright](https://playwright.dev/).

End-to-End (E2E) testing is a methodology that tests the workflow of an application from start to finish.
It aims to replicate real user scenarios, ensuring that the system and its components function together as expected.

Playwright is a powerful E2E testing tool that enables developers and testers to automate browser-based tests with precision.
It's designed to test applications across all modern web browsers by running tests that simulate user interactions.

## Implemented tests

### Grant application tests
- Tests have been implemented for all grant applications.
- These tests are located in the `/e2e/tests/forms/` directory, and they follow the naming convention `{user_role}_{application_id}.ts`, e.g. `registered_community_48.ts`.
- The tests use data that is derived from the `/e2e/utilis/data/application/` directory.
- The application test data files follow the naming convention `application_data_{application_id}.ts`, e.g. `application_data_48.ts`.

#### The following functionality is tested:
- Filling in an application from start to finnish.
- Verifying the content of a submitted application.
- Filling an application with missing/wrong data and verifying the printed error messages.
- Deleting draft applications.

### User role tests
- Tests have been implemented for all user roles (registered community, unregistered community and private person).
- These tests are located in the `/e2e/tests/profiles/` directory, and they follow the naming convention `{user_role}.ts`, e.g. `registered_community.ts`.
- The tests use data that is derived from the `/e2e/utilis/data/` directory.
- The profile test data files follow the naming convention `profile_data_{user_role}.ts`, e.g. `profile_data_registered_community.ts`.

#### The following functionality is tested:
- The login flow, which consists of:
  - Logging in with a SSN.
  - Selecting a role.
  - Filling in a profile form with data that is specific for that role.
  - Verifying the data.
- Filling a profile form with missing/wrong data and verifying the printed error messages.

## Environment setup.

### The .env file
The following environment variables need to be set in a `.env` files for the tests to work.
The file should be located in the `/e2e` directory.

- **ATV_BASE_URL**: The base url for ATV.
- **TEST_ATV_URL**: The ATV test url (same as above).
- **ATV_API_KEY**: The ATV API key.
- **APP_ENV**: The local APP env.
- **APP_DEBUG**: Boolean indicating if messages should be printed to the terminal.
- **DISABLED_FORM_VARIANTS**: Can be used to disable form variants (types of form tests).
- **CREATE_PROFILE**: Boolean indicating if new profiles should be created on each test run.
- **TEST_USER_SSN**: A test users social security number.
- **TEST_USER_UUID** A test users UUID.

Example `.env` file:
```
# ATV SETUP.
ATV_BASE_URL="https://atv-api-hki-kanslia-atv-test.agw.arodevtest.hel.fi"
TEST_ATV_URL="https://atv-api-hki-kanslia-atv-test.agw.arodevtest.hel.fi"
ATV_API_KEY="{ENTER A ATV API KEY}"
APP_ENV="{ENTER A APP ENV}"

# Enable debug mode. Primarily used for logging messages.
APP_DEBUG="TRUE"

# Set the disabled form variants.
DISABLED_FORM_VARIANTS="success,draft"

# Force profile creation.
CREATE_PROFILE="FALSE"

# Testa user data (SSN (SOTU) and UUID).
TEST_USER_SSN="{TEST USER SOCAIL SECURITY NUMBER}"
TEST_USER_UUID="{TEST USER UUID}"
```

## Running E2E tests

### Running E2E tests in Docker (the recommended way)

To run all tests (this run all tests defined under the `projects` key in `playwright.config.ts`):
```
make test-pw
```

To run all tests in headed mode (displays a browser):
```
make test-pw-headed
```

To run a specific set of tests. Available sets (projects) can be found in `playwright.config.ts`:
```
make test-pw-p PROJECT={NAME_OF_PROJECT}

Example 1: make test-pw-p PROJECT=forms-48

Example 2: make test-pw-p PROJECT=forms-48-registered

Example 3: make test-pw-p PROJECT=forms-all
```

To run a specific set of tests in headed mode:
```
make test-pw-ph PROJECT={NAME_OF_PROJECT}

Example 1: make test-pw-ph PROJECT=forms-48

Example 2: make test-pw-ph PROJECT=forms-48-registered
```

To run all profile tests:
```
make test-pw-profiles
```


### Running tests on your local machine (in the /e2e directory)

To run all tests (this run all tests defined under the `projects` key in `playwright.config.ts`):
```
npx playwright test
```

To run all tests in headed mode (displays a browser):
```
npx playwright test --headed
```

To run a specific set of tests. Available sets (projects) can be found in `playwright.config.ts`:
```
npx playwright test --project {NAME_OF_PROJECT}

Example 1: npx playwright test --project forms-48

Example 2: npx playwright test --project forms-48-registered

Example 2: npx playwright test --project forms-all
```

To run a specific set of tests in headed mode:
```
npx playwright test --project {NAME_OF_PROJECT} --headed

Example 1: npx playwright test --project forms-48 --headed

Example 2: npx playwright test --project forms-48-registered --headed
```

Other command line options that can be utilized when running the test on your local machine can be found [here](https://playwright.dev/docs/test-cli).
