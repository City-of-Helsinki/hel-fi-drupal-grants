# E2E tests with Playwright
This directory contains E2E test that are implemented with [Playwright](https://playwright.dev/).

End-to-End (E2E) testing is a methodology that tests the workflow of an application from start to finish.
It aims to replicate real user scenarios, ensuring that the system and its components function together as expected.

Playwright is a powerful E2E testing tool that enables developers and testers to automate browser-based tests with precision.
It's designed to test applications across all modern web browsers by running tests that simulate user interactions.

## Implemented tests

### React grant application tests
- React forms are tested with a shared engine. One `form.spec.ts` per form drives the whole flow.
- These tests are located in `/e2e/tests/forms/{application_type_id}_{form_identifier}/`, e.g. `/e2e/tests/forms/48_kuva_projekti/`.
- All React forms run in the `forms-react` project. Each form also has its own project, e.g. `forms-48-kuva-projekti`.
- See the [React form tests README](utils/react/README.md) for the tested flow, adding a form, per-form customization and debugging.

### Legacy grant application tests
- Tests have been implemented for all legacy webform grant applications.
- These tests are located in the `/e2e/tests/forms/` directory, and they follow the naming convention `{user_role}_{application_id}.ts`, e.g. `registered_community_48.ts`.
- The tests use data that is derived from the `/e2e/utils/data/application/` directory.
- The application test data files follow the naming convention `application_data_{application_id}.ts`, e.g. `application_data_48.ts`.

#### The following functionality is tested:
- Filling in an application from start to finish.
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
- The tests use data that is derived from the `/e2e/utils/data/` directory.
- The profile test data files follow the naming convention `profile_data_{user_role}.ts`, e.g. `profile_data_registered_community.ts`.

#### The following functionality is tested:
- The login flow, which consists of:
  - Logging in with a SSN.
  - Selecting a role.
  - Filling in a profile form with data that is specific for that role.
  - Verifying the data.
- Filling a profile form with missing/wrong data and verifying the printed error messages.
- Editing an existing profile, which consists of:
  - Logging in and selecting a role.
  - Changing profile fields, saving the form and verifying the changed data.
  - Reverting the changes, saving the form and verifying the reverted data.
- The profile edit tests follow the naming convention `{user_role}_edit.ts`, and their data is located in `/e2e/utils/data/profile_edit_data.ts`.

### Smoke tests
- Smoke test have been implemented to lightly test the general site. These tests mainly check that certain components exist on various pages.
- These tests are located in the `/e2e/tests/public/` directory. The test files are named after the page the tests are executed on.
- The tests use data that is derived from the `/e2e/utils/data/public_page_data.ts`.

## Environment setup

### The .env file
Copy `/e2e/.env.example` to `/e2e/.env` and set the variables below.

#### Mandatory:
- **APP_ENV**: The local APP env (usually LOCAL{X}, where {X} is exchanged for the first letter in your last name, like LOCALT).
- **TEST_USER_SSN**: A test users social security number (can be found from a ATV document in Postman).
- **TEST_USER_UUID** A test users UUID (can be found from a ATV document in Postman).

#### Optional:
- **APP_DEBUG**: Set to "TRUE" to print debug messages to the terminal when running tests.
- **ENABLED_FORM_VARIANTS**: Can be used to explicitly run specific form variants. Others are skipped. Legacy form tests only.
- **DISABLED_FORM_VARIANTS**: Can be used to disable form variants (types of form tests). Legacy form tests only.
- **CREATE_PROFILE**: Set to "TRUE" to create new profiles on each test run. Otherwise a profile is created once every hour.
- **RESET_PROFILE**: Set to "TRUE" to restore edited profile data instead of running the profile edit tests. Use this when a failed edit test has left a profile with edited data: `RESET_PROFILE=TRUE npx playwright test --project profile-edits --no-deps`. The `--no-deps` flag is needed, since the profile tests fail while the profile holds edited data.
- **WAIT_FOR_TEXT_TIMEOUT**: The time to wait for text in MS (defaults to 60000, 1 minute). Used by the waitForTextWithInterval() function.
- **WAIT_FOR_TEXT_INTERVAL**: How often to query text in MS (defaults to 5000, 5 seconds). Used by the waitForTextWithInterval() function.
- **SKIP_SUBMIT**: Set to "true" or "1" to skip submitting a React form. The form is filled and saved as a draft, the draft is then removed, and the submit and post submit checks are skipped.
- **TEST_BASEURL**: The site URL (defaults to `https://hel-fi-drupal-grant-applications.docker.so`).
- **SLOWMO**: Set to any value to pause for one second before each browser action.

Example `.env` file:
```
# =======================
# ENVIRONMENT VARIABLES
#
# Copy this file to create a .env file and make necessary changes.
# NOTE! Copy the relevant values from local.settings.php to these environment variables.
# NOTE! The following keys have to be set for the tests to work:
# APP_ENV, TEST_USER_SSN, TEST_USER_UUID.
# =======================

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

# Skip submitting a React form. The form is filled and saved as a draft,
# the draft is removed, and the submit and post submit checks are skipped.
SKIP_SUBMIT="true"

# Test user SSN (sotu) and UUID.
# Both of these can be fetched from a submitted ATV document using Postman.
# These can and should be changed for your own testing credentials.
TEST_USER_SSN="090797-999P"
TEST_USER_UUID="13cb60ae-269a-46da-9a43-da94b980c067"
```

## Running E2E tests

### TL;DR

Start the project with the `testing` compose profile, so the `e2e` container is running:
```
COMPOSE_PROFILES=testing make up
```

Run a single React form:
```
make test-pw-p PROJECT=forms-70-iakkaiden
```

Run all React forms:
```
make test-pw-p PROJECT=forms-react
```

Run everything in the `forms` directory, legacy and React forms, with profile tests as dependencies. This takes hours:
```
make test-pw
```

All projects are listed under the `projects` key in `playwright.config.ts`.
Every project runs its `dependencies` first, so running a form also runs the profile tests for the roles it uses.

### Running E2E tests in Docker (the recommended way)

The `e2e` container only exists when the project is started with `COMPOSE_PROFILES=testing make up`.

To run the `forms-all` project:
```
make test-pw
```

To run the `forms-all` project in headed mode (displays a browser):
```
make test-pw-headed
```

To run a specific project:
```
make test-pw-p PROJECT={NAME_OF_PROJECT}

Example 1: make test-pw-p PROJECT=forms-48-kuva-projekti

Example 2: make test-pw-p PROJECT=forms-48-registered
```

To run a specific project in headed mode:
```
make test-pw-ph PROJECT={NAME_OF_PROJECT}

Example: make test-pw-ph PROJECT=forms-48-kuva-projekti
```

To run all profile tests:
```
make test-pw-profiles
```

Headed mode needs an X server on the host, since the container uses the host `DISPLAY`.

### Running tests on your local machine (in the /e2e directory)
Running tests on your local machine is discouraged and should only be done if
running them in a docker container is not an option. This might be needed in a situation
where a certain feature (such as headed mode) doesn't work on your operating systems when
executing tests via a container.

Install the dependencies and browsers first:
```
npm install
npx playwright install
```

Always pass a project. `npx playwright test` without `--project` runs every project, and most tests run several times.

To run a specific project:
```
npx playwright test --project {NAME_OF_PROJECT}

Example 1: npx playwright test --project forms-70-iakkaiden

Example 2: npx playwright test --project forms-48-registered

Example 3: npx playwright test --project forms-react
```

To run a specific project in headed mode:
```
npx playwright test --project {NAME_OF_PROJECT} --headed
```

To run a specific project in headed mode with a one second pause before each action:
```
SLOWMO=true npx playwright test --project {NAME_OF_PROJECT} --headed
```

To exit on first error:
```
npx playwright test --project {NAME_OF_PROJECT} -x
```

To skip the profile tests a project depends on:
```
npx playwright test --project {NAME_OF_PROJECT} --no-deps
```

To view the report of the last run:
```
npx playwright show-report
```

Other command line options can be found [here](https://playwright.dev/docs/test-cli).

### Deleting applications from your local env
After you've been running tests for a while, you might end up in a situation where
you're left with a lot of applications in your local environment, which makes it slow.
The `clean-env.sh` script deletes every ATV document of your `APP_ENV`.
It reads `APP_ENV` from `/e2e/.env` and the ATV credentials from `public/sites/default/local.settings.php`, and needs `curl` and `jq`.

Delete all documents:
```
make delete-docs-all
```

Delete drafts only:
```
make delete-docs-draft
```

## Known problems

- Sometimes the test fail because of a problem with uploading attachment to applications or profiles. This can usually be fixed by setting 777 permission on everything inside the /private directory.
