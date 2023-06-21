*** Settings ***
Library             Browser
Library             String
Resource            ./dev-env-variables.resource
Resource            ./tunnistamo.resource

*** Keywords ***

Initialize Browser Session
    Open Browser To Home Page
    Accept Cookies Banner

Run Common Teardown Process
    Run Keyword If Test Failed    Take Screenshot    fullPage=True
    Close Browser

Open Browser To Home Page
    Run Keyword If    "${browser}" == "chrome"           New Browser    chromium
    ...    ELSE IF    "${browser}" == "firefox"          New Browser    firefox
    ...    ELSE IF    "${browser}" == "safari"           New Browser    webkit
    ...    ELSE                                          New Browser    chromium
    # Needed for local environment testing
    New Context           ignoreHTTPSErrors=True
    Run Keyword If    "${environment}" == "dev"          New Page    https://avustukset.dev.hel.ninja/
    ...    ELSE IF    "${environment}" == "test"         New Page    https://avustukset.test.hel.ninja/
    ...    ELSE IF    "${environment}" == "stage"        New Page    https://avustukset.stage.hel.ninja/
    ...    ELSE IF    "${environment}" == "local"        New Page    https://hel-fi-drupal-grant-applications.docker.so/
    ...    ELSE                                          New Page    https://hel-fi-drupal-grant-applications.docker.so/
    Get Title    ==    Avustukset | ${SITE_NAME_ALT}

Accept Cookies Banner
    Sleep               1
    Click        .eu-cookie-compliance-default-button
    Wait For Elements State    .eu-cookie-compliance-default-button     hidden

Do Company Login Process With Tunnistamo
    Go To Login Page
    Go To Tunnistamo
    Login With Tunnistamo
    Logged In Role Selector Should Be Open
    Go To Oma Asiointi
    ${title} =    Get Title
    IF    '${title}' == 'Valitse asiointiroolin tyyppi | ${SITE_NAME}'
      Do Company Selection
    ELSE
      Go To Omat tiedot
    END
    Logged In Oma Asiointi Page Should Be Open

Do Unregistered Community Login Process With Tunnistamo
    Go To Login Page
    Go To Tunnistamo
    Login With Tunnistamo
    Logged In Role Selector Should Be Open
    Go To Oma Asiointi
    ${title} =    Get Title
    IF    '${title}' == 'Valitse asiointiroolin tyyppi | ${SITE_NAME}'
      Do Unregistered Community Selection
    ELSE
      Go To Omat tiedot
    END
    Logged In Oma Asiointi Page Should Be Open

Do Private Person Login Process With Tunnistamo
    Go To Login Page
    Go To Tunnistamo
    Login With Tunnistamo
    Logged In Role Selector Should Be Open
    Go To Oma Asiointi
    ${title} =    Get Title
    IF    '${title}' == 'Valitse asiointiroolin tyyppi | ${SITE_NAME}'
      Do Private Person Selection
    ELSE
      Go To Omat tiedot
    END
    Logged In Oma Asiointi Page Should Be Open

Go To Login Page
    Click          .profile__login-link
    Get Title           ==    Kirjaudu sisään | ${SITE_NAME}

Go To Tunnistamo
    Click           \#edit-openid-connect-client-tunnistamo-login
    Wait Until Network Is Idle
    Get Title           *=    Suomi.fi-tunnistus

Logged In Role Selector Should Be Open
    Wait Until Network Is Idle
    Get Title           ==     Valitse asiointiroolin tyyppi | ${SITE_NAME}
    Wait For Elements State          .nav-toggle--profile    visible

Logout In Drupal
    Click          .nav-toggle--profile
    Click          .profile__logout-link
    Wait Until Network Is Idle
    Get Title           ==    Avustukset | ${SITE_NAME_ALT}

Go To Oma Asiointi
    Click             \#block-mainnavigation a[data-drupal-link-system-path="oma-asiointi"]

Go To Omat tiedot
    Click             \#block-hdbt-subtheme-local-tasks a[data-drupal-link-system-path="oma-asiointi/hakuprofiili"]

Do Company Selection
    Choose Company Role
    Choose Company Profile With Tunnistamo

Do Unregistered Community Selection
    Select Options By    [data-drupal-selector="edit-unregistered-community-selection"]    index    2
    Click             \#edit-unregistered-community .form-submit

Do Private Person Selection
    Click             \#edit-private-person .form-submit

Choose Company Role
    Click             \#edit-registered-community .form-submit
    Wait For Condition    Title           ==    Suomi.fi-valtuudet

Logged In Oma Asiointi Page Should Be Open
    Get Text          \#keskeneraiset-hakemukset      *=    Keskeneräiset hakemukset

Upload Drupal Ajax Dummy File
   [Arguments]       ${file_element}
   Upload File By Selector    ${file_element}    ${CURDIR}/empty.pdf
   Wait For Elements State    ${file_element}    hidden
   # It's not enough to wait for upload element to be hidden, have to wait extra bit for JS to parse
   Sleep     1
