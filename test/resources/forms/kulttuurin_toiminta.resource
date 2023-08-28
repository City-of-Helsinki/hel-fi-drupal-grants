*** Settings ***
Resource    ../common.resource
Resource    ../forms.resource


*** Keywords ***
Submit "Kulttuurin taide- ja kulttuuriavustukset: toiminta-avustukset" application
    Go To Application Search
    Search For Application    Kulttuurin taide- ja kulttuuriavustukset: toiminta-avustukset
    Start New Application    Kulttuurin taide- ja kulttuuriavustukset: toiminta-avustukset
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
    Scroll To Element    \#edit-email
    Type Text    \#edit-email    ${INPUT_EMAIL}
    Type Text    \#edit-contact-person    ${INPUT_CONTACT_PERSON}
    Type Text    \#edit-contact-person-phone-number    ${INPUT_CONTACT_PERSON_PHONE_NUMBER}
    Select Options By    \#edit-community-address-community-address-select    index    ${INPUT_COMMUNITY_ADDRESS_INDEX}
    Click    \#edit-actions-wizard-next
    Wait For Elements State    \#edit-bank-account-account-number-select    focused
    Select Options By    \#edit-bank-account-account-number-select    value    ${INPUT_BANK_ACCOUNT_NUMBER}

    Click    \#edit-community-officials-add-submit
    Wait For Response    response => response.request().method() === 'POST'
    Select Options By
    ...    [data-drupal-selector="edit-community-officials-items-0-item-community-officials-select"]
    ...    value
    ...    0

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
    Get Attribute    \#edit-kyseessa-on-monivuotinen-avustus-0    checked    ==    checked
    Select Options By    \#edit-ensisijainen-taiteen-ala    value    ${INPUT_PRIMARY_ART}
    Get Attribute    \#edit-kyseessa-on-festivaali-tai-tapahtuma-1    checked    !=    checked
    Click    \#edit-kyseessa-on-festivaali-tai-tapahtuma-1 ~ label
    Type Text    \#edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti    ${INPUT_CULTURE_PROJECT_DESC}
    Get Text    \#edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti ~ .text-count-wrapper .text-count    !=    700
    Scroll To Element    \#edit-olemme-saaneet-muita-avustuksia-0
    Get Attribute    \#edit-olemme-saaneet-muita-avustuksia-0    checked    ==    checked
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
    Get Attribute
    ...    \#edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p-0
    ...    checked
    ...    !=
    ...    checked
    Click    \#edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p-0 ~ label
    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="4_toiminta"].is-active    visible

Fill Step 4 Data
    Wait Until Network Is Idle
    Type Text
    ...    [data-drupal-selector="edit-tapahtuma-tai-esityspaivien-maara-helsingissa"]
    ...    ${INPUT_CULTURE_DAYS_IN_HKI}
    Type Text    [data-drupal-selector="edit-muut-keskeiset-toimintamuodot"]    ${INPUT_CULTURE_PROJECT_DESC}
    Get Attribute    \#edit-oliko-kyseessa-festivaali-tai-tapahtuma-1    checked    !=    checked

    Click    \#edit-oliko-kyseessa-festivaali-tai-tapahtuma-1 ~ label

    Type Text
    ...    [data-drupal-selector="edit-tapahtuma-tai-esityspaivien-maara-helsingissa-toteutuneet"]
    ...    ${INPUT_CULTURE_DAYS_IN_HKI}

    Type Text    \#edit-toteutuneet-tila-items-0-item-premisename    Testtest
    Type Text    \#edit-toteutuneet-tila-items-0-item-postcode    00100
    Click With Options    [data-drupal-selector="edit-toteutuneet-tila-items-0-item-isownedbycity-0"]    force=True

    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="5_toiminnan_lahtokohdat"].is-active    visible

Fill Step 5 Data
    Wait Until Network Is Idle
    Type Text    \#edit-toiminta-kohderyhmat    ${INPUT_CULTURE_PROJECT_DESC}
    Get Text    \#edit-toiminta-kohderyhmat ~ .text-count-wrapper .text-count    !=    1000
    Type Text    \#edit-toiminta-yhteistyokumppanit    ${INPUT_CULTURE_PROJECT_DESC}
    Get Text    \#edit-toiminta-yhteistyokumppanit ~ .text-count-wrapper .text-count    !=    1000
    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="6_talous"].is-active    visible

Fill Step 6 Data
    Wait Until Network Is Idle
    Click    \#edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-0 ~ label
    Type Text    \#edit-budget-static-income-plannedstateoperativesubvention    123
    Type Text    \#edit-budget-static-income-plannedothercompensations    123
    Type Text    \#edit-budget-static-income-sponsorships    123
    Type Text    \#edit-budget-static-income-entryfees    123
    Type Text    \#edit-budget-static-income-sales    123
    Type Text    \#edit-budget-static-income-financialfundingandinterests    123

    Type Text    id=edit-suunnitellut-menot-plannedtotalcosts    123
    Click    \#edit-organisaatio-kuului-valtionosuusjarjestelmaan-vos-0 ~ label

    Type Text    id=edit-toteutuneet-tulot-data-othercompensationfromcity    123
    Type Text    id=edit-toteutuneet-tulot-data-stateoperativesubvention    123
    Type Text    id=edit-toteutuneet-tulot-data-othercompensations    123
    Type Text    id=edit-toteutuneet-tulot-data-totalincome    123
    Type Text    id=edit-menot-yhteensa-totalcosts    123

    Click    \#edit-actions-wizard-next
    Wait For Elements State    li[data-webform-page="lisatiedot_ja_liitteet"].is-active    visible

Fill Step 7 Data
    Sleep    3
    Upload Drupal Ajax Dummy File    \#edit-yhteison-saannot-attachment-upload
    Upload Drupal Ajax Dummy File
    ...    \#edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload
    Upload Drupal Ajax Dummy File
    ...    \#edit-vahvistettu-toimintakertomus-edelliselta-paattyneelta-tilikaudel-attachment-upload
    Upload Drupal Ajax Dummy File
    ...    \#edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-edelliselta-paa-attachment-upload
    Upload Drupal Ajax Dummy File
    ...    \#edit-toimintasuunnitelma-sille-vuodelle-jolle-haet-avustusta-monivuot-attachment-upload
    Upload Drupal Ajax Dummy File
    ...    \#edit-talousarvio-sille-vuodelle-jolle-haet-avustusta-monivuotisissa-k-attachment-upload
    Click    \#edit-actions-preview-next
    Wait For Elements State    li[data-webform-page="webform_preview"].is-active    visible

Review Application Data
    Get Text    \#kuva_toiminta--contact_person    *=    ${INPUT_CONTACT_PERSON}
    Get Text    \#kuva_toiminta--contact_person_phone_number    *=    ${INPUT_CONTACT_PERSON_PHONE_NUMBER}
    Get Text    \#kuva_toiminta--bank_account    *=    ${INPUT_BANK_ACCOUNT_NUMBER}
    Get Text    \#kuva_toiminta--subventions    *=    ${INPUT_SUBVENTION_AMOUNT}
