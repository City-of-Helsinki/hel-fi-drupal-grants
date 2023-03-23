*** Settings ***
Library             Browser
Library             String
Resource            ./browser-test-variables.resource
Resource            ./dev-env-variables.resource
Resource            ./tunnistamo.resource

*** Keywords ***
Open Browser To Home Page
    Set Browser Timeout   60s
    New Browser           ${BROWSER}
    # Needed for local environment testing
    New Context           ignoreHTTPSErrors=True
    New Page              %{TEST_BASEURL}
    Get Title             ==    Avustukset | ${SITE_NAME_ALT}

Accept Cookies Banner
    Sleep               1
    Click        .eu-cookie-compliance-default-button
    Wait For Elements State    .eu-cookie-compliance-default-button     hidden

Do Login Process With Tunnistamo
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
    Logged In Company Page Should Be Open

Go To Login Page
    Click          .profile__login-link
    Get Title           ==    Kirjaudu sisään | ${SITE_NAME}

Go To Tunnistamo
    Click           \#edit-openid-connect-client-tunnistamo-login
    Wait Until Network Is Idle
    Get Title           ==    Suomi.fi-tunnistus

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
    Click Choose Role
    Choose Company Profile With Tunnistamo

Click Choose Role
    Click             \#grants-mandate-type .form-submit
    Wait For Condition    Title           ==    Suomi.fi-valtuudet

Logged In Company Page Should Be Open
    Get Title           ==    Näytä oma profiili | ${SITE_NAME}
    Get Text          .grants-profile-company-name      *=    ${TUNNISTAMO_COMPANY_NAME}
