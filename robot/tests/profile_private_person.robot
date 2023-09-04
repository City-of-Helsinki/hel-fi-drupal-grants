*** Settings ***
Documentation       Tests for editing the profile of a private person

Resource            ../resources/common.resource

Suite Setup         Login To Service As Private Person
Suite Teardown      Close Browser


*** Test Cases ***
Update Private Person Bank Account
    [Tags]    robot:skip
    Go To Private Person Profile Page
    Open Edit Form
    Add New Bank Account
    Open Edit Form
    Remove New Bank Account

Update Private Person Address
    [Tags]    robot:skip
    Go To Private Person Profile Page
    Open Edit Form
    Change Address To Temporary
    Open Edit Form
    Revert Address

Update Private Person Phone
    [Tags]    robot:skip
    Go To Private Person Profile Page
    Open Edit Form
    Change Phone To Temporary
    Open Edit Form
    Revert Phone


*** Keywords ***
Go To Private Person Profile Page
    Click    .asiointirooli--rooli > a
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
