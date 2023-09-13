*** Settings ***
Documentation       Tests for editing company profile

Resource            ../../resources/common.resource
Resource            ../../resources/profile.resource

Suite Setup         Login To Service As    Company
Suite Teardown      Close Browser
Test Setup          Go To Profile Page
Test Teardown       Run Keyword If Test Failed    Log Error Notifications To Console


*** Variables ***
@{PROFILE_TEXTS}
...                 Yhteisön viralliset tiedot
...                 Yhteisön nimi
...                 Y-tunnus
...                 Kotipaikka
...                 Yhteisön tiedot avustusasioinnissa
...                 Perustamisvuosi
...                 Yhteisön lyhenne
...                 Verkkosivujen osoite
...                 Toiminnan tarkoitus
...                 Osoitteet
...                 Toiminnasta vastaavat henkilöt
...                 Tilinumerot


*** Test Cases ***
Check Company Profile Page Content
    Ensure Correct Title And Fill Info If Necessary
    Check Texts Present In Body
    Ensure Tarkoitus Field Is Filled

Update Company Bank Account
    Go To Profile Edit Page
    Add New Bank Account    IBAN=${INPUT_TEMP_BANK_ACCOUNT_NUMBER}
    Submit Contact Information
    Get Text    .grants-profile--extrainfo    contains    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}
    Go To Profile Edit Page
    Remove Latest Bank Account
    Submit Contact Information
    Get Text    .grants-profile--extrainfo    not contains    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}

Update Company Website
    Go To Profile Edit Page
    Change Company Website To Temporary
    Submit Contact Information
    Get Text    .grants-profile--extrainfo    *=    ${INPUT_TEMP_WEBSITE}
    Go To Profile Edit Page
    Revert Company Website
    Submit Contact Information
    Get Text    .grants-profile--extrainfo    not contains    ${INPUT_TEMP_WEBSITE}


*** Keywords ***
Ensure Correct Title And Fill Info If Necessary
    ${title} =    Get Title
    IF    "${title}" == "Muokkaa omaa profiilia | ${SITE_NAME}"
        Fill Company Profile Required Info
    END

Check Texts Present In Body
    FOR    ${text}    IN    @{PROFILE_TEXTS}
        Get Text    body    *=    ${text}
    END

Ensure Tarkoitus Field Is Filled
    ${tarkoitus} =    Get Text    \#toiminna-tarkoitus + dd
    IF    "${tarkoitus}" == "${EMPTY}"
        Go To Profile Edit Page
        Fill Company Profile Required Info
        Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    END

Change Company Website To Temporary
    ${input} =    Get Text    input[data-drupal-selector="edit-companyhomepagewrapper-companyhomepage"]
    Set Test Variable    ${old_website_input}    ${input}
    Type Text    input[data-drupal-selector="edit-companyhomepagewrapper-companyhomepage"]    ${INPUT_TEMP_WEBSITE}

Revert Company Website
    Type Text    input[data-drupal-selector="edit-companyhomepagewrapper-companyhomepage"]    ${old_website_input}

Fill Company Profile Required Info
    Type Text    [data-drupal-selector="edit-businesspurposewrapper-businesspurpose"]    ${INPUT_COMPENSATION_PURPOSE}
    # Addresses
    Click    button[data-drupal-selector="edit-addresswrapper-actions-add-address"]
    Wait For Response    response => response.request().method() === 'POST'
    Scroll To Element
    ...    [data-drupal-selector="edit-addresswrapper"] fieldset:last-of-type .js-form-item:first-of-type input[type="text"]
    Type Text
    ...    [data-drupal-selector="edit-addresswrapper"] fieldset:last-of-type .js-form-item:first-of-type input[type="text"]
    ...    Vakiokatu 1
    Type Text
    ...    [data-drupal-selector="edit-addresswrapper"] fieldset:last-of-type .js-form-item:nth-of-type(2) input[type="text"]
    ...    00100
    Type Text
    ...    [data-drupal-selector="edit-addresswrapper"] fieldset:last-of-type .js-form-item:nth-of-type(3) input[type="text"]
    ...    Helsinki
    # Officials
    Click    button[data-drupal-selector="edit-officialwrapper-actions-add-official"]
    Wait For Response    response => response.request().method() === 'POST'
    Scroll To Element
    ...    [data-drupal-selector="edit-officialwrapper"] fieldset:last-of-type .js-form-item:first-of-type input[type="text"]
    Type Text
    ...    [data-drupal-selector="edit-officialwrapper"] fieldset:last-of-type .js-form-item:first-of-type input[type="text"]
    ...    Robotti Testi
    Select Options By
    ...    [data-drupal-selector="edit-officialwrapper"] fieldset:last-of-type .js-form-item:nth-of-type(2) select
    ...    value
    ...    1
    Type Text
    ...    [data-drupal-selector="edit-officialwrapper"] fieldset:last-of-type .js-form-item:nth-of-type(3) input[type="text"]
    ...    tama.on.robotin.vakioarvo@hel.fi
    Type Text
    ...    [data-drupal-selector="edit-officialwrapper"] fieldset:last-of-type .js-form-item:nth-of-type(4) input[type="text"]
    ...    040 123 123
    # Bank accounts
    Click    button[data-drupal-selector="edit-bankaccountwrapper-actions-add-bankaccount"]
    Wait For Response    response => response.request().method() === 'POST'
    Scroll To Element
    ...    [data-drupal-selector="edit-bankaccountwrapper"] fieldset:last-of-type .js-form-item:first-of-type input[type="text"]
    Get Attribute
    ...    [data-drupal-selector="edit-bankaccountwrapper"] fieldset:last-of-type .js-form-item:first-of-type input[type="text"]
    ...    value
    ...    ==
    ...    ${Empty}
    Type Text
    ...    [data-drupal-selector="edit-bankaccountwrapper"] fieldset:last-of-type .js-form-item:first-of-type input[type="text"]
    ...    ${INPUT_BANK_ACCOUNT_NUMBER}
    Upload Drupal Ajax Dummy File
    ...    [data-drupal-selector="edit-bankaccountwrapper"] fieldset:last-of-type .js-form-type-managed-file input[type="file"]
    # Submit
    Submit Contact Information
