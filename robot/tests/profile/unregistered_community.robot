*** Settings ***
Documentation       Tests for editing the profile of an unregistered community

Resource            ../../resources/common.resource
Resource            ../../resources/profile.resource
Resource            ../../resources/profile.resource

Suite Setup         Login To Service As    Unregistered Community
Suite Teardown      Close Browser
Test Setup          Go To Profile Page


*** Test Cases ***
Check Unregistered Community Profile Page
    Get Text    .grants-profile    *=    Perustiedot
    Get Text    .grants-profile    *=    Etunimi
    Get Text    .grants-profile    *=    Sukunimi
    Get Text    .grants-profile    *=    Henkilötunnus
    Get Text    .grants-profile    *=    Sähköposti

    Get Element    //a[contains(text(),'Siirry Helsinki-profiiliin päivittääksesi tietoja')]

    Get Text    .grants-profile    *=    Yhteisön tiedot avustusasioinnissa
    Get Text    .grants-profile    *=    Yhteisön nimi
    Get Text    .grants-profile    *=    Osoitteet
    Get Text    .grants-profile    *=    Tilinumerot
    Get Text    .grants-profile    *=    Toiminnasta vastaavat henkilöt

Add And Remove Bank Account To Unregistered Community
    Go To Profile Edit Page
    Add New Bank Account    IBAN=${INPUT_TEMP_BANK_ACCOUNT_NUMBER}
    Submit Contact Information
    Get Text    .grants-profile--extrainfo    contains    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}
    Go To Profile Edit Page
    Remove Latest Bank Account
    Submit Contact Information
    Get Text    .grants-profile--extrainfo    not contains    ${INPUT_TEMP_BANK_ACCOUNT_NUMBER}

Update Unregistered Community Name
    Go To Profile Edit Page
    Change Company Name To Temporary
    Submit Contact Information
    Get Text    .grants-profile--extrainfo    *=    ${INPUT_TEMP_COMPANY_NAME}
    Go To Profile Edit Page
    Type Text    input[data-drupal-selector="edit-companynamewrapper-companyname"]    ${old_company_name_input}
    Submit Contact Information
    Get Text    .grants-profile-company-name    not contains    ${INPUT_TEMP_COMPANY_NAME}


*** Keywords ***
Change Company Name To Temporary
    ${input} =    Get Text    input[data-drupal-selector="edit-companynamewrapper-companyname"]
    Set Test Variable    ${old_company_name_input}    ${input}
    Type Text    input[data-drupal-selector="edit-companynamewrapper-companyname"]    ${INPUT_TEMP_COMPANY_NAME}
