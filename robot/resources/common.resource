*** Settings ***
Library     Browser
Library     String
Library     DateTime
Resource    ./dev-env-variables.resource
Resource    ./tunnistamo.resource


*** Variables ***
${BROWSER}      chromium
${ENV}          ${EMPTY}
${BASE_URL}     ${EMPTY}


*** Keywords ***
Initialize Browser Session
    IF    "${ENV}" == "dev"
        Set Global Variable    ${BASE_URL}    https://avustukset.dev.hel.ninja/
    ELSE IF    "${ENV}" == "test"
        Set Global Variable    ${BASE_URL}    https://avustukset.test.hel.ninja/
    ELSE IF    "${ENV}" == "stage"
        Set Global Variable    ${BASE_URL}    https://avustukset.stage.hel.ninja/
    ELSE
        Set Global Variable    ${BASE_URL}    https://hel-fi-drupal-grant-applications.docker.so/
    END

    Open Browser To Home Page
    Accept Cookies Banner

Login To Service As
    [Arguments]    ${user_type}
    Initialize Browser Session
    Do Login Process With Tunnistamo    ${user_type}

Go To Application Search
    Click    \#block-mainnavigation a[data-drupal-link-system-path="etsi-avustusta"]
    Get Title    ==    Etsi avustusta | ${SITE_NAME}
    Wait For Elements State    .application_search--rows    message="No results were found on the search page"

Go To Front Page
    Go To    ${BASE_URL}
    Wait Until Network Is Idle

Run Common Teardown Process
    Run Keyword If Test Failed    Take Screenshot    fullPage=True
    Close Browser

Log Error Notifications To Console
    ${error_notification_exists} =    Run Keyword And Return Status    Get Element    .hds-notification--error
    IF    '${error_notification_exists}'=='True'
        ${notification} =    Get Element    .hds-notification--error
        ${notification_message} =    Get Text    ${notification}
        Log    ${notification_message}    level=ERROR
    END

Open Browser To Home Page
    New Persistent Context
    ...    browser=${BROWSER}
    ...    ignoreHTTPSErrors=True
    ...    locale=fi
    ...    url=${BASE_URL}
    ...    headless=True
    Get Title    ==    Avustukset | ${SITE_NAME_ALT}

Accept Cookies Banner
    Click    .eu-cookie-compliance-default-button
    Wait For Elements State    .eu-cookie-compliance-default-button    hidden

Do Login Process With Tunnistamo
    [Arguments]    ${user_type}
    Log To Console    Logging in with Tunnistamo...
    Go To Login Page
    Go To Tunnistamo
    Login With Tunnistamo
    Logged In Role Selector Should Be Open
    Go To Oma Asiointi
    Handle User Role    ${user_type}
    Logged In Oma Asiointi Page Should Be Open

Handle User Role
    [Arguments]    ${user_type}
    ${title} =    Get Title
    IF    '${title}' == 'Valitse asiointiroolin tyyppi | ${SITE_NAME}'
        Handle Role Selection    ${user_type}
    ELSE
        Go To Omat tiedot
    END

Handle Role Selection
    [Arguments]    ${user_type}
    IF    '${user_type}' == 'Company'    Do Company Selection
    IF    '${user_type}' == 'Unregistered Community'
        Handle Unregistered Community
    END
    IF    '${user_type}' == 'Private Person'    Do Private Person Selection

Handle Unregistered Community
    [Documentation]    Check if an unregistered community exists, if not, create one
    ${option_count} =    Get Element Count    \#edit-unregistered-community-selection option
    IF    ${option_count} <= 2    Create New Unregistered Community
    Do Unregistered Community Selection

Go To Login Page
    Click    .profile__login-link
    Get Title    ==    Kirjaudu sisään | ${SITE_NAME}

Go To Tunnistamo
    Click    \#edit-openid-connect-client-tunnistamo-login
    Get Element    \#fakevetuma2

Logged In Role Selector Should Be Open
    Wait For Elements State    \#grants-mandate-type
    Get Title    ==    Valitse asiointiroolin tyyppi | ${SITE_NAME}
    Wait For Elements State    .nav-toggle--profile    visible

Logout In Drupal
    Click    .nav-toggle--profile
    Click    .profile__logout-link
    Wait Until Network Is Idle
    Get Title    ==    Avustukset | ${SITE_NAME_ALT}

Go To Oma Asiointi
    Click    \#block-mainnavigation >> "Oma asiointi"
    Wait Until Network Is Idle

Go To Omat tiedot
    Click    a > "Omat tiedot"

Go To Profile Page
    Click    .asiointirooli--rooli > a
    Wait For Condition    Title    contains    Näytä oma profiili | ${SITE_NAME}

Do Company Selection
    Choose Company Role
    Choose Company Profile With Tunnistamo

Do Unregistered Community Selection
    Select Options By    [data-drupal-selector="edit-unregistered-community-selection"]    index    2
    Click    \#edit-unregistered-community .form-submit

Do Private Person Selection
    Click    "Valitse rooli Yksityishenkilö"

Choose Company Role
    Click    \#edit-registered-community .form-submit
    Wait For Condition    Title    ==    Suomi.fi-valtuudet
    Wait For Elements State    table    timeout=60s

Logged In Oma Asiointi Page Should Be Open
    Get Text    \#keskeneraiset-hakemukset    *=    Keskeneräiset hakemukset

Upload Drupal Ajax Dummy File
    [Arguments]    ${file_element}
    Upload File By Selector    ${file_element}    ${CURDIR}/empty.pdf
    Wait For Response    response => response.request().method() === 'POST'
    Wait For Elements State    ${file_element}    hidden
    Sleep    2

Create New Unregistered Community
    Select Options By    [data-drupal-selector="edit-unregistered-community-selection"]    value    new
    Click    \#edit-unregistered-community .form-submit

    Type Text    \#edit-companynamewrapper-companyname    Robottiryhmä
    Type Text    \#edit-officialwrapper-0-official-phone    ${INPUT_CONTACT_PERSON_PHONE_NUMBER}
    Type Text    \#edit-bankaccountwrapper-0-bank-bankaccount    ${INPUT_BANK_ACCOUNT_NUMBER}
    Upload Drupal Ajax Dummy File    \#edit-bankaccountwrapper-0-bank-confirmationfile-upload

    Click    \#edit-actions-submit
    Get Title    ==    Näytä oma profiili | ${SITE_NAME}
    Click    .link--switch-role
