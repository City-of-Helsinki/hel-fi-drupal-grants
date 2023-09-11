*** Settings ***
Resource    ../common.resource
Resource    ../forms.resource


*** Keywords ***
Submit "Kaupunginhallitus, yleisavustus" application
    Go To Application Search
    Start New Application    Kaupunginhallitus, yleisavustus
    Fill Step 1 Data
    Fill Step 2 Data
    Save Application As Draft
    Fill Step 3 Data
    Fill Step 4 Data
    Review Application Data
    Submit Application
    Completion Page

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

Fill Step 2 Data
    Scroll To Element    \#edit-acting-year
    ${today} =    Get Current Date    result_format=datetime
    ${current_year} =    Convert To String    ${today.year}
    Select Options By    \#edit-acting-year    value    ${current_year}
    Wait Until Network Is Idle
    Type Text    \#edit-subventions-items-0-amount    ${INPUT_SUBVENTION_AMOUNT}
    Sleep    1    # Have to manually wait for js formatter
    Get Text    \#edit-subventions-items-0-amount    ==    ${INPUT_SUBVENTION_AMOUNT_FORMATTED}
    Type Text    \#edit-compensation-purpose    ${INPUT_COMPENSATION_PURPOSE}
    Get Text    \#edit-compensation-purpose ~ .text-count-wrapper .text-count    !=    5000
    Scroll To Element    \#edit-olemme-saaneet-muita-avustuksia-0
    Get Attribute    \#edit-olemme-saaneet-muita-avustuksia-0    checked    ==    checked
    Scroll To Element    \#edit-compensation-boolean-0 ~ label
    Get Attribute    \#edit-compensation-boolean-0    checked    ==    checked
    Get Attribute    \#edit-compensation-boolean-1    checked    !=    checked
    Click    \#edit-compensation-boolean-1 ~ label
    Wait For Elements State    \#edit-compensation-explanation    visible
    Type Text    \#edit-compensation-explanation    ${INPUT_COMPENSATION_EXPLANATION}
    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="3_yhteison_tiedot"].is-active    visible

Save Application as Draft
    Wait Until Network Is Idle
    Click    \#edit-actions-draft    # Can sometimes take up to 60s
    Wait For Elements State    .webform-submission__application_id .webform-submission__application_id--body    visible
    Scroll To Element    .webform-submission__application_id .webform-submission__application_id--body
    ${application_id} =    Get Text    .webform-submission__application_id .webform-submission__application_id--body
    Get Url    *=    ${application_id}    # Application id should be in the url
    # Go back to editing application
    Click    a[data-drupal-selector="application-edit-link"]
    Wait For Elements State    li[data-webform-page="3_yhteison_tiedot"].is-active    visible

Fill Step 3 Data
    Scroll To Element    \#edit-business-info
    Get Text    \#edit-business-purpose    !=    ${EMPTY}
    Get Text    \#edit-community-practices-business-0 ~ label    ==    Ei
    Click    \#edit-community-practices-business-0 ~ label
    Scroll To Element    \#edit-fee-person
    Type Text    \#edit-fee-person    ${INPUT_FEE_PERSON}
    Sleep    1    # Have to manually wait for js formatter
    Get Text    \#edit-fee-person    ==    ${INPUT_FEE_PERSON_FORMATTED}
    Scroll To Element    \#edit-jasenmaara
    Hover    \#edit-jasenmaara .form-item-members-applicant-person-global .webform-element-help
    Wait For Elements State    \#tippy-3    visible
    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="lisatiedot_ja_liitteet"].is-active    visible

Fill Step 4 Data
    Sleep    3
    Upload Drupal Ajax Dummy File    \#edit-yhteison-saannot-attachment-upload
    Upload Drupal Ajax Dummy File    \#edit-vahvistettu-tilinpaatos-attachment-upload
    Upload Drupal Ajax Dummy File    \#edit-vahvistettu-toimintakertomus-attachment-upload
    Upload Drupal Ajax Dummy File    \#edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-attachment-upload
    Upload Drupal Ajax Dummy File    \#edit-vuosikokouksen-poytakirja-attachment-upload
    Upload Drupal Ajax Dummy File    \#edit-toimintasuunnitelma-attachment-upload
    Upload Drupal Ajax Dummy File    \#edit-talousarvio-attachment-upload
    Click    \#edit-actions-preview-next
    Wait For Elements State    li[data-webform-page="webform_preview"].is-active    visible

Review Application Data
    Get Text    \#yleisavustushakemus--contact_person    *=    ${INPUT_CONTACT_PERSON}
    Get Text    \#yleisavustushakemus--contact_person_phone_number    *=    ${INPUT_CONTACT_PERSON_PHONE_NUMBER}
    Get Text    \#yleisavustushakemus--bank_account    *=    ${INPUT_BANK_ACCOUNT_NUMBER}
    Get Text    \#yleisavustushakemus--subventions    *=    ${INPUT_SUBVENTION_AMOUNT}
    Get Text    \#yleisavustushakemus--compensation_purpose    *=    ${INPUT_COMPENSATION_PURPOSE}
    Get Text    \#yleisavustushakemus--compensation_explanation    *=    ${INPUT_COMPENSATION_EXPLANATION}
    Get Text    \#yleisavustushakemus--fee_person    *=    ${INPUT_FEE_PERSON_FORMATTED}
