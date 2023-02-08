*** Settings ***
Documentation       Robot test for testing user profile editing
Metadata            Examplemetadata          This is a simple browser test for ${baseurl}. Metadata is shown in the reports.
Library             Browser
Library             String
Suite Setup
Resource            ../resources/common.resource
Resource            ../resources/tunnistamo.resource

*** Test Cases ***

Update User Bank Account
    Open Browser To Home Page
    Accept Cookies Banner
    Do Login Process With Tunnistamo
    Open Edit Form For Company
    Add New Bank Account
    Remove New Bank Account
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
    Get Title           ==    Muokkaa omaa profiilia | ${SITE_NAME}
    Wait For Elements State    \#edit-bankaccountwrapper input[type="text"][readonly="readonly"][value="${INPUT_TEMP_BANK_ACCOUNT_NUMBER}"]     visible

Remove New Bank Account
    ${bank_account_input} =     Get Attribute     \#edit-bankaccountwrapper input[type="text"][readonly="readonly"][value="${INPUT_TEMP_BANK_ACCOUNT_NUMBER}"]     id
    ${bank_account_input} =     Get Substring     ${bank_account_input}     0     -12
    Click             a[data-drupal-selector="${bank_account_input}-deletebutton"]
    Wait For Elements State    \#drupal-modal .grants-profile-bank-account-delete-confirm-form     visible
    Click    \#drupal-modal .grants-profile-bank-account-delete-confirm-form input[type="submit"]
    Wait Until Network Is Idle
    Get Title           ==    Muokkaa omaa profiilia | ${SITE_NAME}
    Get Element Count    \#edit-bankaccountwrapper input[type="text"][readonly="readonly"][value="${INPUT_TEMP_BANK_ACCOUNT_NUMBER}"]    ==      0
