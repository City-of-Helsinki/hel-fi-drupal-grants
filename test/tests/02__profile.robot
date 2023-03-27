*** Settings ***
Documentation       Robot test for testing user profile editing
Metadata            Examplemetadata          This is a simple browser test for ${baseurl}. Metadata is shown in the reports.
Library             Browser
Library             String
Suite Setup
Resource            ../resources/common.resource
Resource            ../resources/tunnistamo.resource

*** Test Cases ***

Login And Check Own Profile Info
    Open Browser To Home Page
    Accept Cookies Banner
    Do Login Process With Tunnistamo
    Check Own Profile Info
    [Teardown]    Close Browser

Update User Bank Account
    Open Browser To Home Page
    Accept Cookies Banner
    Do Login Process With Tunnistamo
    Open Edit Form For Company
    Add New Bank Account
    Open Edit Form For Company
    Remove New Bank Account
    [Teardown]    Close Browser

Update User Founding Year
    Open Browser To Home Page
    Accept Cookies Banner
    Do Login Process With Tunnistamo
    Open Edit Form For Company
    Change To Temporary Founding Year
    Open Edit Form For Company
    Change to Original Founding Year
    [Teardown]    Close Browser

*** Keywords ***

Open Edit Form For Company
    Click           a[data-drupal-selector="application-edit-link"]
    Wait Until Network Is Idle
    Get Title           ==    Muokkaa omaa profiilia | ${SITE_NAME}

Add New Bank Account
    Click           input[data-drupal-selector="edit-bankaccounts-add-more"]
    Sleep   2   # Have to manually wait for ajax load
    Scroll To Element   \#edit-bankaccountwrapper .draggable:last-of-type .js-form-item:first-of-type input[type="text"]
    Get Attribute    \#edit-bankaccountwrapper .draggable:last-of-type .js-form-item:first-of-type input[type="text"]      value   ==    ${Empty}
    Type Text        \#edit-bankaccountwrapper .draggable:last-of-type .js-form-item:first-of-type input[type="text"]     ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}
    Upload File By Selector    \#edit-bankaccountwrapper .draggable:last-of-type .js-form-type-managed-file input[type="file"]    ${CURDIR}/empty.pdf
    Sleep   3   # Have to manually wait for ajax upload
    Click           \#edit-submit
    Get Title           ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile--extrainfo    *=    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}

Remove New Bank Account
    ${bank_account_input} =     Get Attribute     \#edit-bankaccountwrapper input[type="text"][readonly="readonly"][value="${INPUT_TEMP_BANK_ACCOUNT_NUMBER}"]     id
    ${bank_account_input} =     Get Substring     ${bank_account_input}     0     -12
    Click             a[data-drupal-selector="${bank_account_input}-deletebutton"]
    Wait For Elements State    \#drupal-modal .grants-profile-bank-account-delete-confirm-form     visible
    Click    \#drupal-modal .grants-profile-bank-account-delete-confirm-form input[type="submit"]
    Sleep   2   # Have to manually wait for ajax load
    Wait Until Network Is Idle
    Wait For Elements State    \#edit-bankaccountwrapper     visible
    Get Title           ==    Muokkaa omaa profiilia | ${SITE_NAME}
    Get Element Count    \#edit-bankaccountwrapper input[type="text"][readonly="readonly"][value="${INPUT_TEMP_BANK_ACCOUNT_NUMBER}"]    ==      0

Check Own Profile Info
    Get Title           ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .hero--oma-asiointi .hero__title    *=    ${TUNNISTAMO_COMPANY_NAME}
    Get Text    .grants-profile-company-name    *=    ${TUNNISTAMO_COMPANY_NAME}
    Get Text    .grants-profile-business-id    *=    ${TUNNISTAMO_COMPANY_ID}

Change to Temporary Founding Year
    Type Text        \#edit-foundingyear    1999
    Click           \#edit-submit
    Get Title           ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    \#perustamisvuosi ~ dd:first-of-type    *=    1999

Change To Original Founding Year
    Type Text        \#edit-foundingyear    2023
    Click           \#edit-submit
    Get Title           ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    \#perustamisvuosi ~ dd:first-of-type    *=    2023
