*** Settings ***
Documentation       Tests for editing the profile of a private person

Resource            ../../resources/profile.resource
Resource            ../../resources/common.resource
Library             String

Suite Setup         Login To Service As    Private Person
Suite Teardown      Close Browser
Test Setup          Go To Profile Page
Test Teardown       Run Keyword If Test Failed    Log Error Notifications To Console


*** Variables ***
${street}           ${EMPTY}
${post_code}        ${EMPTY}
${city}             ${EMPTY}
${phone_number}     ${EMPTY}

@{PROFILE_TEXTS}
...                 Perustiedot
...                 Etunimi
...                 Sukunimi
...                 Henkilötunnus
...                 Sähköposti
...                 Omat yhteystiedot
...                 Osoite
...                 Puhelinnumero
...                 Tilinumerot


*** Test Cases ***
Check Private Person Profile Page
    Ensure Correct Title And Fill Info If Necessary
    Check Texts Present In Body

Submit New Contact Information
    ${street}    Generate Random String    length=12
    ${post_code}    Generate Random String    length=5    chars=[NUMBERS]
    ${city}    Generate Random String    length=8
    ${phone_number}    Generate Random String    length=10    chars=[NUMBERS]

    Go To Profile Edit Page
    Fill Text    \#edit-addresswrapper-street    ${street}
    Fill Text    \#edit-addresswrapper-postcode    ${post_code}
    Fill Text    \#edit-addresswrapper-city    ${city}
    Fill Text    \#edit-phonewrapper-phone-number    ${phone_number}
    Fill Text    \#edit-emailwrapper-email    tama.on.robotin.vakioarvo@hel.fi

    ${bank_accounts}    Get Element Count    css=[id*="bank-deletebutton"]
    IF    ${bank_accounts}==0    Add New Bank Account

    Submit Contact Information
    Page Contains New Contact Information

Update Private Person Bank Account
    Go To Profile Edit Page
    Add New Bank Account    IBAN=${INPUT_TEMP_BANK_ACCOUNT_NUMBER}
    Submit Contact Information
    Get Text    .grants-profile--extrainfo    contains    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}
    Go To Profile Edit Page
    Remove Latest Bank Account
    Submit Contact Information
    Get Text    .grants-profile--extrainfo    not contains    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}


*** Keywords ***
Ensure Correct Title And Fill Info If Necessary
    ${title}    Get Title
    IF    "${title}" == "Muokkaa omaa profiilia | ${SITE_NAME}"
        Fill Private Person Profile Required Info
    END

Check Texts Present In Body
    FOR    ${text}    IN    @{PROFILE_TEXTS}
        Get Text    body    *=    ${text}
    END
    Get Element    //a[contains(text(),'Siirry Helsinki-profiiliin päivittääksesi tietoja')]

Fill Private Person Profile Required Info
    Type Text    \#edit-addresswrapper-street    Vakiokatu 1
    Type Text    \#edit-addresswrapper-postcode    00100
    Type Text    \#edit-addresswrapper-city    Helsinki
    Type Text    \#edit-phonewrapper-phone-number    040 123 123
    Type Text    \#edit-emailwrapper-email    tama.on.robotin.vakioarvo@hel.fi

    Click    button[data-drupal-selector="edit-bankaccountwrapper-actions-add-bankaccount"]
    Wait For Response    response => response.request().method() === 'POST'

    Type Text    input #bank-bankaccount    ${INPUT_BANK_ACCOUNT_NUMBER}
    Upload Drupal Ajax Dummy File
    ...    [data-drupal-selector="edit-bankaccountwrapper"] fieldset:last-of-type .js-form-type-managed-file input[type="file"]
    Submit Contact Information

Page Contains New Contact Information
    Get Text    body    *=    ${city}
    Get Text    body    *=    ${street}
    Get Text    body    *=    ${post_code}
    Get Text    body    *=    ${phone_number}
