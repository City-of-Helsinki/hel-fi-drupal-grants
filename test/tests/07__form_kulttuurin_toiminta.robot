*** Settings ***
Documentation       Robot test for testing application form and handling
Metadata            Examplemetadata          This is a simple browser test for ${baseurl}. Metadata is shown in the reports.
Library             Browser
Library             String
Library             DateTime
Suite Setup
Resource            ../resources/common.resource
Resource            ../resources/tunnistamo.resource
Resource            ../resources/dev-env-variables.resource

*** Test Cases ***

Fill kuva_toiminta Form
    Initialize Browser Session
    Do Company Login Process With Tunnistamo
    Go To Application Search
    Start New Application
    Fill Step 1 Data
    Fill Step 2 Data
    Save Application As Draft
    Fill Step 3 Data
    Fill Step 4 Data
    Fill Step 5 Data
    Fill Step 6 Data
    Fill Step 7 Data
    Review Application Data
    Completion Page
    [Teardown]    Run Common Teardown Process

*** Keywords ***

Go To Application Search
    Click          \#block-mainnavigation a[data-drupal-link-system-path="etsi-avustusta"]
    Get Title           ==    Etsi avustusta | ${SITE_NAME}

Start New Application
    Click      .view-application-search \#kulttuurin-taide--ja-kulttuuriavustukset--toiminta-avustukset
    Click      \#block-servicepageauthblock .hds-button
    Get Title           ==    Taide- ja kulttuuriavustukset, toiminta-avustus | ${SITE_NAME}
    Wait For Elements State       li[data-webform-page="1_hakijan_tiedot"].is-active  visible

Fill Step 1 Data
    Scroll To Element     \#edit-email
    Type Text          \#edit-email     ${INPUT_EMAIL}
    Type Text          \#edit-contact-person     ${INPUT_CONTACT_PERSON}
    Type Text          \#edit-contact-person-phone-number     ${INPUT_CONTACT_PERSON_PHONE_NUMBER}
    Select Options By     \#edit-community-address-community-address-select   index     ${INPUT_COMMUNITY_ADDRESS_INDEX}
    Click       \#edit-actions-wizard-next
    Wait For Elements State       \#edit-bank-account-account-number-select       focused
    Select Options By       \#edit-bank-account-account-number-select    value    ${INPUT_BANK_ACCOUNT_NUMBER}
    Click       \#edit-actions-wizard-next
    Wait For Elements State      li[data-webform-page="2_avustustiedot"].is-active   visible

Fill Step 2 Data
    Scroll To Element     \#edit-acting-year
    ${today} = 	          Get Current Date     result_format=datetime
    ${current_year} =       Convert To String    ${today.year}
    Select Options By     \#edit-acting-year   value            ${current_year}
    Wait Until Network Is Idle
    Type Text             \#edit-subventions-items-0-amount     ${INPUT_SUBVENTION_AMOUNT}
    Sleep   1    # Have to manually wait for js formatter
    Get Text              \#edit-subventions-items-0-amount    ==     ${INPUT_SUBVENTION_AMOUNT_FORMATTED}
    Get Attribute         \#edit-kyseessa-on-monivuotinen-avustus-0    checked     ==    checked
    Select Options By     \#edit-ensisijainen-taiteen-ala    value    ${INPUT_PRIMARY_ART}
    Get Attribute         \#edit-kyseessa-on-festivaali-tai-tapahtuma-1    checked     !=    checked
    Click                 \#edit-kyseessa-on-festivaali-tai-tapahtuma-1 ~ label
    Type Text             \#edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti           ${INPUT_CULTURE_PROJECT_DESC}
    Get Text              \#edit-hankkeen-tai-toiminnan-lyhyt-esittelyteksti ~ .text-count-wrapper .text-count     !=    700
    Scroll To Element     \#edit-olemme-saaneet-muita-avustuksia-0
    Get Attribute         \#edit-olemme-saaneet-muita-avustuksia-0    checked     ==    checked
    Click       \#edit-actions-wizard-next
    Wait For Elements State      li[data-webform-page="3_yhteison_tiedot"].is-active   visible

Save Application as Draft
    Wait Until Network Is Idle
    Click                \#edit-actions-draft     # Can sometimes take up to 60s
    Wait For Elements State    .webform-submission__application_id .webform-submission__application_id--body    visible
    Scroll To Element    .webform-submission__application_id .webform-submission__application_id--body
    ${application_id} =   Get Text    .webform-submission__application_id .webform-submission__application_id--body
    Get Url   *=    ${application_id}     # Application id should be in the url
    # Go back to editing application
    Click                a[data-drupal-selector="application-edit-link"]
    Wait For Elements State      li[data-webform-page="3_yhteison_tiedot"].is-active   visible

Fill Step 3 Data
    Wait Until Network Is Idle
    Scroll To Element     \#edit-jasenmaara
    Hover                 \#edit-jasenmaara .grants-fieldset .webform-element-help
    Wait For Elements State    \#tippy-1    visible
    Get Attribute         \#edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p-0    checked     !=    checked
    Click                 \#edit-taiteellisen-toiminnan-tilaa-omistuksessa-tai-ymparivuotisesti-p-0 ~ label
    Click       \#edit-actions-wizard-next
    Wait For Elements State      li[data-webform-page="4_toiminta"].is-active   visible

Fill Step 4 Data
    Wait Until Network Is Idle
    Type Text     [data-drupal-selector="edit-tapahtuma-tai-esityspaivien-maara-helsingissa"]    ${INPUT_CULTURE_DAYS_IN_HKI}
    Type Text     [data-drupal-selector="edit-muut-keskeiset-toimintamuodot"]           ${INPUT_CULTURE_PROJECT_DESC}
    Get Attribute         \#edit-oliko-kyseessa-festivaali-tai-tapahtuma-1    checked     !=    checked
    Click                 \#edit-oliko-kyseessa-festivaali-tai-tapahtuma-1 ~ label
    Click       \#edit-actions-wizard-next
    Wait For Elements State      li[data-webform-page="5_toiminnan_lahtokohdat"].is-active   visible

Fill Step 5 Data
    Wait Until Network Is Idle
    Type Text     \#edit-toiminta-kohderyhmat           ${INPUT_CULTURE_PROJECT_DESC}
    Get Text      \#edit-toiminta-kohderyhmat ~ .text-count-wrapper .text-count     !=    1000
    Type Text     \#edit-toiminta-yhteistyokumppanit           ${INPUT_CULTURE_PROJECT_DESC}
    Get Text      \#edit-toiminta-yhteistyokumppanit ~ .text-count-wrapper .text-count     !=    1000
    Click       \#edit-actions-wizard-next
    Wait For Elements State      li[data-webform-page="6_talous"].is-active   visible

Fill Step 6 Data
    Wait Until Network Is Idle
    Click         \#edit-organisaatio-kuuluu-valtionosuusjarjestelmaan-vos-0 ~ label
    Type Text     [data-drupal-selector="edit-budget-static-income-items-0-item-sponsorships"]    ${INPUT_CULTURE_PROJECT_SPONSOR}
    Click         \#edit-organisaatio-kuului-valtionosuusjarjestelmaan-vos-0 ~ label
    Click       \#edit-actions-wizard-next
    Wait For Elements State      li[data-webform-page="lisatiedot_ja_liitteet"].is-active   visible

Fill Step 7 Data
    Scroll To Element     \#edit-yhteison-saannot-attachment
    Upload File By Selector    \#edit-yhteison-saannot-attachment-upload    ${CURDIR}/empty.pdf
    Sleep   3   # Have to manually wait for ajax upload
    Scroll To Element     \#edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment
    Upload File By Selector    \#edit-vahvistettu-tilinpaatos-edelliselta-paattyneelta-tilikaudelta-attachment-upload    ${CURDIR}/empty.pdf
    Sleep   3   # Have to manually wait for ajax upload
    Scroll To Element     \#edit-vahvistettu-toimintakertomus-edelliselta-paattyneelta-tilikaudel-attachment
    Upload File By Selector    \#edit-vahvistettu-toimintakertomus-edelliselta-paattyneelta-tilikaudel-attachment-upload    ${CURDIR}/empty.pdf
    Sleep   3   # Have to manually wait for ajax upload
    Scroll To Element     \#edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-edelliselta-paa-attachment
    Upload File By Selector    \#edit-vahvistettu-tilin-tai-toiminnantarkastuskertomus-edelliselta-paa-attachment-upload    ${CURDIR}/empty.pdf
    Sleep   3   # Have to manually wait for ajax upload
    Scroll To Element     \#edit-toimintasuunnitelma-sille-vuodelle-jolle-haet-avustusta-monivuot-attachment
    Upload File By Selector    \#edit-toimintasuunnitelma-sille-vuodelle-jolle-haet-avustusta-monivuot-attachment-upload    ${CURDIR}/empty.pdf
    Sleep   3   # Have to manually wait for ajax upload
    Scroll To Element     \#edit-talousarvio-sille-vuodelle-jolle-haet-avustusta-monivuotisissa-k-attachment
    Upload File By Selector    \#edit-talousarvio-sille-vuodelle-jolle-haet-avustusta-monivuotisissa-k-attachment-upload    ${CURDIR}/empty.pdf
    Sleep   3   # Have to manually wait for ajax upload
    Click       \#edit-actions-preview-next
    Wait For Elements State      li[data-webform-page="webform_preview"].is-active   visible

Review Application Data
    Get Text    \#yleisavustushakemus--contact_person   *=    ${INPUT_CONTACT_PERSON}
    Get Text    \#yleisavustushakemus--contact_person_phone_number   *=    ${INPUT_CONTACT_PERSON_PHONE_NUMBER}
    Get Text    \#yleisavustushakemus--bank_account   *=    ${INPUT_BANK_ACCOUNT_NUMBER}
    Get Text    \#yleisavustushakemus--subventions   *=    ${INPUT_SUBVENTION_AMOUNT_FORMATTED_ALT}
    Get Text    \#yleisavustushakemus--compensation_purpose   *=    ${INPUT_COMPENSATION_PURPOSE}
    Get Text    \#yleisavustushakemus--compensation_explanation   *=    ${INPUT_COMPENSATION_EXPLANATION}
    Get Text    \#yleisavustushakemus--fee_person   *=    ${INPUT_FEE_PERSON_FORMATTED}
    Wait Until Network Is Idle
    Click       \#accept_terms_1 ~ label
    # Submitting form can take a long time
    Set Browser Timeout     120s
    Click       \#edit-actions-submit

Completion Page
    # Revert timeout to default
    Set Browser Timeout     30s
    Get Text    \#avustushakemus-lahetetty-onnistuneesti    ==    Avustushakemus lähetetty onnistuneesti
    ${application_id} =   Get Text    .breadcrumb a:last-of-type
    Get Url   *=    ${application_id}     # Application id should be in the url
