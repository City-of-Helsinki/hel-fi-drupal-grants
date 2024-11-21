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
- Verifying the "save as draft" button is present on an active application.
- Filling an application with missing/wrong data and verifying the printed error messages (both inline form errors and general error messages printed at the top of the page can be tested).
- Swapping field values on an application and verifying that the values get swapped.
- Copying an application and verifying that its content gets copied.
- Printing an application and verifying that the printed content is correct.
- Verifying that the tooltips on the application fields work.
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

### Smoke tests
- Smoke test have been implemented to lightly test the general site. These tests mainly check that certain components exist on various pages.
- These tests are located in the `/e2e/tests/public/` directory. The test files are named after the page the tests are executed on.
- The tests use data that is derived from the `/e2e/utilis/data/public_page_data.ts`.

## Environment setup.

### The .env file
The following environment variables need to be set in a `.env` files for the tests to work.
The file should be located in the `/e2e` directory.

#### Mandatory:
- **ATV_BASE_URL**: The base url for ATV.
- **TEST_ATV_URL**: The ATV test url (same as above).
- **ATV_API_KEY**: The ATV API key.
- **APP_ENV**: The local APP env (usually LOCAL{X}, where {X} is exchanged for the first letter in your last name, like LOCALT).
- **TEST_USER_SSN**: A test users social security number (can be found from a ATV document in Postman).
- **TEST_USER_UUID** A test users UUID (can be found from a ATV document in Postman).

#### Optional:
- **APP_DEBUG**: Boolean indicating if messages should be printed to the terminal when running tests.
- **ENABLED_FORM_VARIANTS**: Can be used to explicitly run specific form variants. Others are skipped.
- **DISABLED_FORM_VARIANTS**: Can be used to disable form variants (types of form tests).
- **CREATE_PROFILE**: Boolean indicating if new profiles should be created on each test run.
- **WAIT_FOR_TEXT_TIMEOUT**: The time to wait for text in MS (defaults to 60000, 1 minute). Used by the waitForTextWithInterval() function.
- **WAIT_FOR_TEXT_INTERVAL**: How often to query text in MS (defaults to 5000, 5 seconds). Used by the waitForTextWithInterval() function.

Example `.env` file:
```
# =======================
# ENVIRONEMNT VARIABLES
#
# Copy this file to create a .env file and make necessary changes.
# NOTE! Copy the relevant values from local.settings.php to these environment variables.
# NOTE! The following keys have to be set for the tests to work:
# ATV_BASE_URL, TEST_ATV_URL, ATV_API_KEY, APP_ENV, TEST_USER_SSN, TEST_USER_UUID.
# =======================

ATV_BASE_URL="https://atv-api-hki-kanslia-atv-test.agw.arodevtest.hel.fi"
TEST_ATV_URL="https://atv-api-hki-kanslia-atv-test.agw.arodevtest.hel.fi"
ATV_API_KEY="{ENTER A ATV API KEY}"
APP_ENV="{ENTER A APP ENV}"

# Set the disabled form variants.
# A "form variant" is a type of form test. If you want to disable
# everything except the "draft" test (saves a form as a draft), you can use this:
# DISABLED_FORM_VARIANTS="success,copy,missing_values,swap_fields,wrong_values,wrong_email,wrong_email_2,wrong_email_3,under5000"
DISABLED_FORM_VARIANTS="success"

# Set the enabled form variants. If this is set, only variants specified here will be run.
ENABLED_FORM_VARIANTS="copy"

# A flag indicating if profile creation should be forced during test runs.
# If you set this to "FALSE", a new profile will only be created once every hour,
# leading to faster tests.
CREATE_PROFILE="FALSE"

# Wait for text timeout (how long to wait) in MS (defaults to 60000, 1 minute).
# Used by the waitForTextWithInterval() function. This is mainly used when verifying applications sent to Avus2.
WAIT_FOR_TEXT_TIMEOUT="10000"

# Wait for text interval (how often to query) in MS (defaults to 5000, 5 second).
# Used by the waitForTextWithInterval() function. This is mainly used when verifying applications sent to Avus2.
WAIT_FOR_TEXT_INTERVAL="5000"

# A flag indicating if the "debugging mode" should be turned on.
# Messages will be printed during test runs if set to "TRUE".
APP_DEBUG="TRUE"

# Test user SSN (sotu) and UUID.
# Both of these can be fetched from a submitted ATV document using Postman.
# These can and should be changed for your own testing credentials.
TEST_USER_SSN="090797-999P"
TEST_USER_UUID="13cb60ae-269a-46da-9a43-da94b980c067"

# Environemnt cleanup: clean-env.sh searches for these
# IDs from ATV and removes any found documents.
USER_IDS=("uuid1" "uuid2" "uuid3")
BUSINESS_IDS=("business1" "business2" "business3")
```

## Running E2E tests

### TL;DR

RUN ALL FORM TESTS (not that this will run every single E2E test, and it is going to take around 2 hours):
```
make test-pw
```

RUN A SPECIFIC SET OF TESTS (more options under the `projects` key in `playwright.config.ts`):
```
make test-pw-p PROJECT=forms-48-registered
```

### Running E2E tests in Docker (the recommended way)

To run all form and user tests (this runs the `forms-all` defined under the `projects` key in `playwright.config.ts`):
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

```

To run a specific set of tests in headed mode:
```
make test-pw-ph PROJECT={NAME_OF_PROJECT}

Example 1: make test-pw-ph PROJECT=forms-48

```

To run all profile tests:
```
make test-pw-profiles
```


### Running tests on your local machine (in the /e2e directory)
Running tests on your local machine is discouraged and should only be done if
running them in a docker container is not an option. This might be needed in a situation
where a certain feature (such as headed mode) doesn't work on your operating systems when
executing tests via a container.

To run all tests (this runs all tests defined under the `projects` key in `playwright.config.ts`):
```
npx playwright test
```

To run all tests in headed mode (displays a browser):
```
npx playwright test --headed
```

To exit on first error:
```
npx playwright test -x
```

To run a specific set of tests. Available sets (projects) can be found in `playwright.config.ts`:
```
npx playwright test --project {NAME_OF_PROJECT}

Example 1: npx playwright test --project forms-48

Example 2: npx playwright test --project forms-48-registered

Example 3: npx playwright test --project forms-all
```

To run a specific set of tests in headed mode:
```
npx playwright test --project {NAME_OF_PROJECT} --headed

Example 1: npx playwright test --project forms-48 --headed

Example 2: npx playwright test --project forms-48-registered --headed

Example 3: npx playwright test --project forms-all --headed
```

To run a specific set of tests in headed mode with a slow-motion feature that pauses for one second before entering data:
```
SLOWMO=true npx playwright test --project {NAME_OF_PROJECT} --headed
```

Other command line options that can be utilized when running the test on your local machine can be found [here](https://playwright.dev/docs/test-cli).

### Deleting applications from your local env
After you've been running tests for a while, you might end up in a situation where
you're left with a lot of applications in your local environment, which makes it slow.
To fix this issue, you can set the keys `USER_IDS` and `BUSINESS_IDS` in the .env file,
and run the script `clean-env.sh`.

```
Set in the .env file (example values):
USER_IDS="d2694883-9ef0-4c59-9720-1c2c05ad1rt9"
BUSINESS_IDS="7009192-5"
```

```
Execute the script
make shell > e2e/clean-env.sh
```

## Known problems

- Sometimes the test fail because of a problem with uploading attachment to applications or profiles. This can usually be fixed by setting 777 permission on everything inside the /private directory.
