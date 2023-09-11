*** Settings ***
Documentation       Tests for editing company profile

Resource            ../resources/common.resource

Suite Setup         Login To Service As Company User
Suite Teardown      Close Browser


*** Test Cases ***
Update Company Bank Account
    [Tags]    robot:skip
    Go To Company Profile Page
    Ensure That Company Profile Has Required Info
    Open Edit Form
    Add New Bank Account
    Open Edit Form
    Remove New Bank Account

Update Company Website
    [Tags]    robot:skip
    Go To Company Profile Page
    Ensure That Company Profile Has Required Info
    Open Edit Form
    Change Company Website To Temporary
    Open Edit Form
    Revert Company Website


*** Keywords ***
Go To Company Profile Page
    Click    .asiointirooli--rooli > a
    Wait Until Network Is Idle
    ${title} =    Get Title
    IF    "${title}" == "Muokkaa omaa profiilia | ${SITE_NAME}"
        Fill Company Profile Required Info
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
    Wait For Response    response => response.request().method() === 'POST'
    Sleep    3
    Wait For Condition    Title    contains    Näytä oma profiili | ${SITE_NAME}
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
    Wait For Response    response => response.request().method() === 'POST'
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Get Text    .grants-profile--extrainfo    not contains    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}

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
