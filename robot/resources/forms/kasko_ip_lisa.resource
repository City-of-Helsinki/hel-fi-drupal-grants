*** Settings ***
Resource    ../common.resource
Resource    ../forms.resource


*** Keywords ***
Submit "Iltapäivätoiminnan harkinnanvarainen lisäavustushakemus" application
    [Documentation]    WIP
    Go To Application Search
    Search For Application    Iltapäivätoiminnan harkinnanvarainen lisäavustushakemus
    Start New Application    Iltapäivätoiminnan harkinnanvarainen lisäavustushakemus
    Fill Step 1 Data

Fill Step 1 Data
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
