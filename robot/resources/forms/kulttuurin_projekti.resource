*** Settings ***
Resource    ../common.resource
Resource    ../forms.resource


*** Keywords ***
Submit "Kulttuurin taide- ja kulttuuriavustukset: Projektiavustukset" application
    Go To Application Search
    Search For Application    Kulttuurin taide- ja kulttuuriavustukset: Projektiavustukset
    Start New Application    Kulttuurin taide- ja kulttuuriavustukset: Projektiavustukset
    Fill Step 1 Data
    Fill Step 2 Data
    Save Application As Draft
    Fill Step 3 Data
    Fill Step 4 Data
    Fill Step 5 Data
    Fill Step 6 Data
    Fill Step 7 Data
    Review Application Data
    Submit Application
    Completion Page

Fill Step 1 Data
    Sleep    1
    ${editable_email_field_exists} =    Get Element States    \#edit-email    then    bool(value & visible)
    IF    ${editable_email_field_exists} == True
        Type Text    \#edit-email    ${INPUT_EMAIL}
    END
    ${contact_exists} =    Get Element States    \#edit-contact-person    then    bool(value & visible)
    IF    ${contact_exists} == True
        Type Text    \#edit-contact-person    ${INPUT_CONTACT_PERSON}
        Type Text    \#edit-contact-person-phone-number    ${INPUT_CONTACT_PERSON_PHONE_NUMBER}
    END
    ${community_address_exists} =    Get Element States
    ...    \#edit-community-address-community-address-select
    ...    then
    ...    bool(value & visible)
    IF    ${community_address_exists} == True
        Select Options By
        ...    \#edit-community-address-community-address-select
        ...    index
        ...    ${INPUT_COMMUNITY_ADDRESS_INDEX}
    END
    Click    \#edit-actions-wizard-next
    Wait For Elements State    \#edit-bank-account-account-number-select    focused
    Select Options By    \#edit-bank-account-account-number-select    value    ${INPUT_BANK_ACCOUNT_NUMBER}
    ${community_officials_exist} =    Get Element States
    ...    [data-drupal-selector="edit-community-officials-add-submit"]
    ...    then
    ...    bool(value & visible)
    IF    ${community_officials_exist} == True
        Click    [data-drupal-selector="edit-community-officials-add-submit"]
        Wait Until Network Is Idle
        Sleep    1    # Have to manually wait for js formatter
        Select Options By
        ...    [data-drupal-selector="edit-community-officials-items-0-item-community-officials-select"]
        ...    index
        ...    1
    END
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
    Select Options By    \#edit-ensisijainen-taiteen-ala    value    ${INPUT_PRIMARY_ART}
    Type Text    \#edit-hankkeen-nimi    ${INPUT_CULTURE_PROJECT_NAME}
    Get Attribute    \#edit-kyseessa-on-festivaali-tai-tapahtuma-1    checked    !=    checked
    Click    \#edit-kyseessa-on-festivaali-tai-tapahtuma-1 ~ label
    Type Text    \#edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti    ${INPUT_CULTURE_PROJECT_DESC}
    Get Text    \#edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti ~ .text-count-wrapper .text-count    !=    500
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
    Wait Until Network Is Idle
    Scroll To Element    \#edit-jasenmaara
    Hover    \#edit-jasenmaara .grants-fieldset .webform-element-help
    Wait For Elements State    \#tippy-1    visible
    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="4_suunniteltu_toiminta"].is-active    visible

Fill Step 4 Data
    Wait Until Network Is Idle
    Type Text
    ...    [data-drupal-selector="edit-tapahtuma-tai-esityspaivien-maara-helsingissa"]
    ...    ${INPUT_CULTURE_DAYS_IN_HKI}
    Type Text
    ...    \#edit-ensimmainen-yleisolle-avoimen-tilaisuuden-paikka-helsingissa
    ...    ${INPUT_CULTURE_PROJECT_PREMISE_NAME}
    Type Text
    ...    \#edit-postinumero
    ...    ${INPUT_CULTURE_PROJECT_PREMISE_POSTCODE}
    Click With Options    \#edit-kyseessa-on-kaupungin-omistama-tila-0    force=true
    Type Text
    ...    [data-drupal-selector="edit-ensimmaisen-yleisolle-avoimen-tilaisuuden-paivamaara"]
    ...    ${INPUT_CULTURE_PROJECT_START}
    Type Text    [data-drupal-selector="edit-hanke-alkaa"]    ${INPUT_CULTURE_PROJECT_START}
    Type Text    [data-drupal-selector="edit-hanke-loppuu"]    ${INPUT_CULTURE_PROJECT_END}
    Type Text    \#edit-laajempi-hankekuvaus    ${INPUT_CULTURE_PROJECT_DESC}
    Get Text    \#edit-laajempi-hankekuvaus ~ .text-count-wrapper .text-count    !=    2500
    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="5_toiminnan_lahtokohdat"].is-active    visible

Fill Step 5 Data
    Wait Until Network Is Idle
    Type Text    \#edit-toiminta-kohderyhmat    ${INPUT_CULTURE_PROJECT_DESC}
    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="6_talous"].is-active    visible

Fill Step 6 Data
    Wait Until Network Is Idle
    Click    \#edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-0 ~ label
    Type Text
    ...    \#edit-budget-static-income-sponsorships
    ...    ${INPUT_CULTURE_PROJECT_SPONSOR}
    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="lisatiedot_ja_liitteet"].is-active    visible

Fill Step 7 Data
    Upload Drupal Ajax Dummy File    .js-form-type-managed-file:last-of-type input[type="file"]
    Click    \#edit-actions-preview-next
    Wait For Elements State    li[data-webform-page="webform_preview"].is-active    visible

Review Application Data
    Get Text    \#kuva_projekti--bank_account    *=    ${INPUT_BANK_ACCOUNT_NUMBER}
    Get Text    \#kuva_projekti--subventions    *=    ${INPUT_SUBVENTION_AMOUNT}
