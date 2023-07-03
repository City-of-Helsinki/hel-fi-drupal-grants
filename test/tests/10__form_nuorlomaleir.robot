*** Settings ***
Documentation       Robot test for testing application form and handling
Metadata            Examplemetadata          This is a simple browser test for ${baseurl}. Metadata is shown in the reports.
Library             Browser
Library             String
Library             DateTime
Suite Setup
Resource            ../resources/common.resource
Resource            ../resources/tunnistamo.resource
Resource            ../resources/dev-env-variables.resource

*** Test Cases ***

Fill nuorlomaleir Form
    Initialize Browser Session
    Do Company Login Process With Tunnistamo
    Go To Application Search
    Start New Application
    Fill Step 1 Data
    [Teardown]    Run Common Teardown Process

*** Keywords ***

Go To Application Search
    Click          \#block-mainnavigation a[data-drupal-link-system-path="etsi-avustusta"]
    Get Title           ==    Etsi avustusta | ${SITE_NAME}

Start New Application
    Click      .view-application-search \#nuorisotoiminnan-loma-aikojen-leiriavustus
    Click      \#block-servicepageauthblock .hds-button
    Get Title           ==    Loma-aikojen leiriavustushakemus | ${SITE_NAME}
    Wait For Elements State       li[data-webform-page="1_hakijan_tiedot"].is-active  visible

Fill Step 1 Data
    Scroll To Element     \#edit-email
    Type Text          \#edit-email     ${INPUT_EMAIL}
    Type Text          \#edit-contact-person     ${INPUT_CONTACT_PERSON}
    Type Text          \#edit-contact-person-phone-number     ${INPUT_CONTACT_PERSON_PHONE_NUMBER}
    Select Options By     \#edit-community-address-community-address-select   index     ${INPUT_COMMUNITY_ADDRESS_INDEX}
    Click       \#edit-actions-wizard-next
    Wait For Elements State       \#edit-bank-account-account-number-select       focused
    Select Options By       \#edit-bank-account-account-number-select    value    ${INPUT_BANK_ACCOUNT_NUMBER}
    Click       \#edit-actions-wizard-next
    Wait For Elements State      li[data-webform-page="2_avustustiedot"].is-active   visible
