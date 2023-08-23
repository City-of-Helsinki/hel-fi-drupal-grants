*** Settings ***
Documentation       Robot test for testing user profile editing
Metadata            Examplemetadata    This is a simple browser test for ${baseurl}. Metadata is shown in the reports.

Library             Browser
Library             String
Library             ../env/lib/python3.11/site-packages/robot/libraries/Telnet.py
Resource            ../resources/common.resource
Resource            ../resources/tunnistamo.resource

Test Setup          Initialize Browser Session
Test Teardown       Run Common Teardown Process


*** Test Cases ***
#
# Company
#

Update Company Bank Account
    Do Company Login Process With Tunnistamo
    Go To Company Profile Page
    Ensure That Company Profile Has Required Info
    Open Edit Form
    Add New Bank Account
    Open Edit Form
    Remove New Bank Account

Update Company Website
    Do Company Login Process With Tunnistamo
    Go To Company Profile Page
    Ensure That Company Profile Has Required Info
    Open Edit Form
    Change Company Website To Temporary
    Open Edit Form
    Revert Company Website

#
# Unregistered Community
#

Update Unregistered Company Bank Account
    Do Unregistered Community Login Process With Tunnistamo
    Go To Unregistered Community Profile Page
    Open Edit Form
    Add New Bank Account For Unregistered Community
    Open Edit Form
    Remove New Bank Account

Update Unregistered Community Name
    Do Unregistered Community Login Process With Tunnistamo
    Go To Unregistered Community Profile Page
    Open Edit Form
    Change Company Name To Temporary
    Open Edit Form
    Revert Company Name

#
# Private Person
#

Update Private Person Bank Account
    Do Private Person Login Process With Tunnistamo
    Go To Private Person Profile Page
    Open Edit Form
    Add New Bank Account
    Open Edit Form
    Remove New Bank Account

Update Private Person Address
    Do Private Person Login Process With Tunnistamo
    Go To Private Person Profile Page
    Open Edit Form
    Change Address To Temporary
    Open Edit Form
    Revert Address

Update Private Person Phone
    Do Private Person Login Process With Tunnistamo
    Go To Private Person Profile Page
    Open Edit Form
    Change Phone To Temporary
    Open Edit Form
    Revert Phone


*** Keywords ***
Go To Company Profile Page
    Click    a[data-drupal-link-system-path="oma-asiointi/hakuprofiili"]
    Wait Until Network Is Idle
    ${title} =    Get Title
    IF    "${title}" == "Muokkaa omaa profiilia | ${SITE_NAME}"
        Fill Company Profile Required Info
    END
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}

Go To Unregistered Community Profile Page
    Click    a[data-drupal-link-system-path="oma-asiointi/hakuprofiili"]
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}

Go To Private Person Profile Page
    Click    a[data-drupal-link-system-path="oma-asiointi/hakuprofiili"]
    Wait Until Network Is Idle
    ${title} =    Get Title
    IF    "${title}" == "Muokkaa omaa profiilia | ${SITE_NAME}"
        Fill Private Person Profile Required Info
    END
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}

Open Edit Form
    Click    a[data-drupal-selector="profile-edit-link"]
    Wait Until Network Is Idle
    Get Title    ==    Muokkaa omaa profiilia | ${SITE_NAME}

Add New Bank Account
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
    ...    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}
    Upload Drupal Ajax Dummy File
    ...    [data-drupal-selector="edit-bankaccountwrapper"] fieldset:last-of-type .js-form-type-managed-file input[type="file"]
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile--extrainfo    *=    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}

Add New Bank Account For Unregistered Community
    Click    button[data-drupal-selector="edit-bankaccountwrapper-actions-add-bankaccount"]
    Wait For Response    response => response.request().method() === 'POST'
    Type Text
    ...    [data-drupal-selector="edit-bankaccountwrapper-1-bank-bankaccount"]
    ...    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}
    Upload Drupal Ajax Dummy File    input[type="file"]
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile--extrainfo    *=    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}

Remove New Bank Account
    ${bank_account_input} =    Get Attribute
    ...    [data-drupal-selector="edit-bankaccountwrapper"] input[type="text"][readonly="readonly"][value="${INPUT_TEMP_BANK_ACCOUNT_NUMBER}"]
    ...    id
    ${bank_account_input} =    Get Substring    ${bank_account_input}    0    -12
    Click    button[data-drupal-selector="${bank_account_input}-deletebutton"]
    Wait For Response    response => response.request().method() === 'POST'
    Wait Until Network Is Idle
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile--extrainfo    not contains    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}

Change Address To Temporary
    ${input} =    Get Text    input[data-drupal-selector="edit-addresswrapper-street"]
    Set Test Variable    ${old_address_input}    ${input}
    Type Text    input[data-drupal-selector="edit-addresswrapper-street"]    ${INPUT_TEMP_ADDRESS}
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile--extrainfo    *=    ${INPUT_TEMP_ADDRESS}

Revert Address
    Type Text    input[data-drupal-selector="edit-addresswrapper-street"]    ${old_address_input}
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile--extrainfo    not contains    ${INPUT_TEMP_ADDRESS}

Change Phone To Temporary
    ${input} =    Get Text    input[data-drupal-selector="edit-phonewrapper-phone-number"]
    Set Test Variable    ${old_phone_input}    ${input}
    Type Text    input[data-drupal-selector="edit-phonewrapper-phone-number"]    ${INPUT_TEMP_PHONE}
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}

Revert Phone
    Type Text    input[data-drupal-selector="edit-phonewrapper-phone-number"]    ${old_phone_input}
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}

Change Company Website To Temporary
    ${input} =    Get Text    input[data-drupal-selector="edit-companyhomepagewrapper-companyhomepage"]
    Set Test Variable    ${old_website_input}    ${input}
    Type Text    input[data-drupal-selector="edit-companyhomepagewrapper-companyhomepage"]    ${INPUT_TEMP_WEBSITE}
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile--extrainfo    *=    ${INPUT_TEMP_WEBSITE}

Revert Company Website
    Type Text    input[data-drupal-selector="edit-companyhomepagewrapper-companyhomepage"]    ${old_website_input}
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile--extrainfo    not contains    ${INPUT_TEMP_WEBSITE}

Change Company Name To Temporary
    ${input} =    Get Text    input[data-drupal-selector="edit-companynamewrapper-companyname"]
    Set Test Variable    ${old_company_name_input}    ${input}
    Type Text    input[data-drupal-selector="edit-companynamewrapper-companyname"]    ${INPUT_TEMP_COMPANY_NAME}
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile--extrainfo    *=    ${INPUT_TEMP_COMPANY_NAME}

Revert Company Name
    Type Text    input[data-drupal-selector="edit-companynamewrapper-companyname"]    ${old_company_name_input}
    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile-company-name    not contains    ${INPUT_TEMP_COMPANY_NAME}

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
    Click    \#edit-actions-submit

Ensure That Company Profile Has Required Info
    ${tarkoitus} =    Get Text    \#toiminna-tarkoitus + dd
    IF    "${tarkoitus}" == "${EMPTY}"
        Open Edit Form
        Fill Company Profile Required Info
        Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    END

Fill Private Person Profile Required Info
    Type Text    input[data-drupal-selector="edit-addresswrapper-street"]    Vakiokatu 1
    Type Text    input[data-drupal-selector="edit-addresswrapper-postcode"]    00100
    Type Text    input[data-drupal-selector="edit-addresswrapper-city"]    Helsinki
    Type Text    input[data-drupal-selector="edit-phonewrapper-phone-number"]    040 123 123
    Type Text    input[data-drupal-selector="edit-emailwrapper-email"]    tama.on.robotin.vakioarvo@hel.fi
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
    Click    \#edit-actions-submit
