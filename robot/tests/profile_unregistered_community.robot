*** Settings ***
Documentation       Tests for editing unregistered community profile

Resource            ../resources/common.resource

Suite Setup         Login To Service As Unregistered Community
Suite Teardown      Close Browser


*** Test Cases ***
Update Unregistered Company Bank Account
    [Tags]    robot:skip
    Go To Unregistered Community Profile Page
    Open Edit Form
    Add New Bank Account For Unregistered Community
    Open Edit Form
    Remove New Bank Account

Update Unregistered Community Name
    [Tags]    robot:skip
    Go To Unregistered Community Profile Page
    Open Edit Form
    Change Company Name To Temporary
    Open Edit Form
    Revert Company Name


*** Keywords ***
Go To Unregistered Community Profile Page
    Click    .asiointirooli--rooli > a
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}

Open Edit Form
    Click    a[data-drupal-selector="profile-edit-link"]
    Wait Until Network Is Idle
    Get Title    ==    Muokkaa omaa profiilia | ${SITE_NAME}

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
