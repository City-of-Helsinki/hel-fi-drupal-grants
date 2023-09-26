*** Settings ***
Resource    ../resources/common.resource


*** Keywords ***
Go To Profile Edit Page
    Click    a[data-drupal-selector="profile-edit-link"]
    Wait Until Network Is Idle
    Get Title    ==    Muokkaa omaa profiilia | ${SITE_NAME}

Add New Bank Account
    [Arguments]    ${IBAN}=${INPUT_BANK_ACCOUNT_NUMBER}
    ${iban_input_count} =    Get Element Count    input[id*="bank-bankaccount"]
    ${iban_count_incremented} =    Evaluate    ${iban_input_count} + 1

    Click    button[data-drupal-selector="edit-bankaccountwrapper-actions-add-bankaccount"]
    Wait For Response    response => response.request().method() === 'POST'

    Wait For Condition    Element Count    css=input[id*="bank-bankaccount"]    ==    ${iban_count_incremented}

    ${iban_inputs} =    Get Elements    input[id*="bank-bankaccount"]
    ${confirmation_file_upload_inputs} =    Get Elements    input[id*="bank-confirmationfile-upload"]

    Fill Text    ${iban_inputs[-1]}    ${IBAN}
    Upload Drupal Ajax Dummy File    ${confirmation_file_upload_inputs[-1]}

Remove Latest Bank Account
    ${delete_buttons} =    Get Elements    button[id*="bank-deletebutton"]
    Click    ${delete_buttons[-1]}
    Wait For Response    response => response.request().method() === 'POST'
    Wait Until Network Is Idle

Submit Contact Information
    Click    \#edit-actions-submit
    Wait Until Network Is Idle
    Wait For Condition    Title    contains    Näytä oma profiili
