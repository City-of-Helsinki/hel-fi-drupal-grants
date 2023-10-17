# Helsinki grants applications

![CI pipeline](https://github.com/City-of-Helsinki/hel-fi-drupal-grants/actions/workflows/test.yml/badge.svg)

This project offers citizens a way to apply for different city grants for their associations or themselves...

## Environments

Env | Branch    | Url
------ |-----------| -----
local   | *         | [https://hel-fi-drupal-grant-applications.docker.so/](https://hel-fi-drupal-grant-applications.docker.so/)
development   | *         | [https://avustukset.dev.hel.ninja](https://avustukset.dev.hel.ninja)
testing   | develop   | [https://avustukset.test.hel.ninja](https://avustukset.test.hel.ninja)
staging   | release/* | [https://avustukset.stage.hel.ninja](https://avustukset.stage.hel.ninja)
production   | main      | [https://avustukset.hel.fi](https://avustukset.hel.fi) (https://nginx-avustusasiointi-prod.apps.platta.hel.fi/ before release)


## Requirements

You need to have these applications installed to operate on all environments:

- [Docker](https://github.com/druidfi/guidelines/blob/master/docs/docker.md)
- [Stonehenge](https://github.com/druidfi/stonehenge)
- For the new person: Your SSH public key needs to be added to servers

## Create and start the environment

For the first time (new project):

``
$ make new
``

And following times to create and start the environment:

``
$ make fresh
``

NOTE: Change these according of the state of your project.

## Login to Drupal container

This will log you inside the app container:

```
$ make shell
```

## Importing configurations
In addition to normal cim/cex things, we have custom importers for Webform & their translations. This is because it's hard to control form imports with normal setup.

ALL webforms are ignored by default with config_ignore module.

To import forms & translations you can run following command:
```
$ drush gwi
```
This imports ALL webform and their translations apart from the specifically skipped production forms.

We have many forms in different states of production, some are in development which we want & can override every time. But then we have forms that are in production and CANNOT be overridden by config, and those can be ignored by their applicationID in grants_metadata.settings.

Like so:
```
config_import_ignore:
  - 53
  - 51
```
This ignores forms with ID's of 53 & 51.

We will ignore every production form like this, but those forms need to be be overridden sometimes. For that we have command to import only single form with it's applicationId.
```
$ drush gwi 53
```





## Enable debugging
To enable xdebug, run `export XDEBUG_ENABLE=true` before (re)starting your project. More info in [docker-composer.yml](./docker-compose.yml)


## Links & information
Works is done & issues tracked [on our Jira board](https://helsinkisolutionoffice.atlassian.net/browse/AU).

[Production monitoring dashboard](https://console-openshift-console.apps.platta.hel.fi/k8s/cluster/projects/hki-kanslia-aok-lomaketyokalu-prod).

## Tests for custom modules

Drupal uses `phpunit` library for tests and it is installed via `composer` as a development dependency. Tests are configured using `phpunit.xml` file in module root. Drupal documentation about tests an be found [here](https://www.drupal.org/docs/develop/automated-testing)

There are three kind of tests. Unit tests are for testing code without loading Drupal. Kernel tests are run always with Drupal core and during the test setup phase modules can be installed and configurations loaded. These can be used to test features that tied to Drupal fore features like services and events. Functional tests are run with whole Drupal and they can be used to test any Drupal functionality.

Each kind of test case has base class that are extended to create tests.

Run tests related to AtvSchema: `vendor/bin/phpunit -c public/modules/custom/grants_metadata` It generates coverage report in html format under `public/modules/custom/grants_metadata/html-coverage`. Use browser to open `dashboard.html` to see the report.

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

## Changelog
Can be found from [here](CHANGELOG.md).
