*** Settings ***
Resource    ../common.resource
Resource    ../forms.resource


*** Keywords ***
Submit "Nuorisotoiminnan loma-aikojen leiriavustus" application
    [Documentation]    WIP
    Go To Application Search
    Search For Application    Nuorisotoiminnan loma-aikojen leiriavustus
    Start New Application    Nuorisotoiminnan loma-aikojen leiriavustus
    Fill Step 1

Fill Step 1
    Scroll To Element    \#edit-email
    Type Text    \#edit-email    ${INPUT_EMAIL}
    Type Text    \#edit-contact-person    ${INPUT_CONTACT_PERSON}
    Type Text    \#edit-contact-person-phone-number    ${INPUT_CONTACT_PERSON_PHONE_NUMBER}
    Select Options By    \#edit-community-address-community-address-select    index    ${INPUT_COMMUNITY_ADDRESS_INDEX}
    Click    \#edit-actions-wizard-next
    Wait For Elements State    \#edit-bank-account-account-number-select    focused
    Select Options By    \#edit-bank-account-account-number-select    value    ${INPUT_BANK_ACCOUNT_NUMBER}
    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="2_avustustiedot"].is-active    visible
