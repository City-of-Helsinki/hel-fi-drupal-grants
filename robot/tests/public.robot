*** Settings ***
Documentation       Tests for avustusasiointi front page

Resource            ../resources/common.resource

Suite Setup         Initialize Browser Session
Suite Teardown      Close Browser
Test Setup          Go To Front Page


*** Test Cases ***
Test General UI Functionality
    Open Main Menu Dropdown
    Check Navigation Bar Links
    Check Footer Links
    Change Language
    Login Page Is Accessible

Visit Home Page
    Check Home Page Links

Visit Information About Grants
    Go To Information About Grants
    Check Information Links

Visit News Page
    Go To News Page
    Check News Block

Visit Instructions Page
    Go To Instructions Page
    Test Instructions Page Accordion

Visit Application Search
    Go To Application Search
    Search Grants
    Go To First Application


*** Keywords ***
Search Grants
    ${search_input}=    Get Element    input[name="search"]
    Type Text    ${search_input}    avustus
    Click    \#edit-submit-application-search

Go To First Application
    [Documentation]    Application start button should not exist since we are not logged in
    Click    .view-application-search .views-row:nth-child(1) a.application_search--link
    Get Element Count    \#block-servicepageauthblock .hds-button    ==    0

Open Main Menu Dropdown
    Get Attribute
    ...    \#block-mainnavigation .menu__item--children:first-of-type .menu__toggle-button
    ...    aria-expanded
    ...    ==
    ...    false
    Get Element States    \#block-mainnavigation .menu__item--children:first-of-type ul    contains    hidden
    Click    \#block-mainnavigation .menu__item--children:first-of-type .menu__toggle-button
    Get Attribute
    ...    \#block-mainnavigation .menu__item--children:first-of-type .menu__toggle-button
    ...    aria-expanded
    ...    ==
    ...    true
    Get Element States    \#block-mainnavigation .menu__item--children:first-of-type ul    contains    visible

Check Navigation Bar Links
    Get Text    header    contains    Tietoa avustuksista
    Get Text    header    contains    Etsi avustusta
    Get Text    header    contains    Ohjeita hakijalle

Change Language
    Change Language To Swedish
    Change Language To English
    Change Language To Finnish

Change Language To Swedish
    Click    .language-switcher a[lang="sv"]
    Get Text    .hero    contains    Understöd
    Get Title    contains    Bidragstjänsten

Change Language To Finnish
    Click    .language-switcher a[lang="fi"]
    Get Text    .hero    contains    Avustukset
    Get Title    contains    Avustusasiointi

Change Language To English
    Click    .language-switcher a[lang="en"]
    Get Text    .hero    contains    Grants
    Get Title    contains    Grants service

Check Footer Links
    Get Text    footer    contains    Avoimet työpaikat
    Get Text    footer    contains    Sosiaalinen media
    Get Text    footer    contains    Medialle
    Get Text    footer    contains    Ota yhteyttä kaupunkiin
    Get Text    footer    contains    Saavutettavuusseloste
    Get Text    footer    contains    Takaisin ylös
    Get Text    footer    contains    Tietopyynnöt
    Get Text    footer    contains    Digituki
    Get Text    footer    contains    Anna palautetta

Login Page Is Accessible
    ${login_link}=    Get Element By Role    link    name=Kirjaudu
    Click    ${login_link}
    Get Text    main    *=    Kirjaudu sisään pankkitunnuksilla, mobiilivarmenteella tai varmennekortilla
    ${login_button}=    Get Element By Role    button    name=Kirjaudu sisään

Check News Block
    Get Element Count    .views--frontpage-news .news-listing__item    >=    1
    Get Element Count    .views--frontpage-news .news-listing__item:first-of-type h3 a    ==    1

Check Home Page Links
    ${promise}=    Promise To    Wait For Response    **/tietoa-avustuksista
    Click    \#tietoa-avustuksista a
    ${res}=    Wait For    ${promise}
    Click    .site-name__link
    ${promise}=    Promise To    Wait For Response    **/ohjeita-hakijalle
    Click    \#ohjeita-hakijalle a
    Wait For    ${promise}
    Click    .site-name__link
    Get Element Count    \#edit-openid-connect-client-tunnistamo-login    ==    1

Go To Information About Grants
    Click    \#block-mainnavigation a[data-drupal-link-system-path="node/47"]
    Get Title    ==    Tietoa avustuksista | ${SITE_NAME_ALT}

Check Information Links
    Get Element Count    .component--list-of-links    >=    1
    Get Element Count    .component--list-of-links h2.component__title    >=    1
    Get Element Count    .component--list-of-links a.list-of-links__item__link    >=    1

Go To News Page
    Click    \#block-mainnavigation a[data-drupal-link-system-path="node/47"] ~ button
    Click    \#block-mainnavigation a[data-drupal-link-system-path="news"]
    Get Title    ==    Ajankohtaista avustuksista | ${SITE_NAME}

Go To Instructions Page
    Click    \#block-mainnavigation a[data-drupal-link-system-path="node/20"]
    Get Title    ==    Ohjeita hakijalle | ${SITE_NAME_ALT}

Test Instructions Page Accordion
    Get Attribute    \#avustusten-hakuajat.accordion-item__header button    aria-expanded    ==    false
    Get Element States    \#avustusten-hakuajat.accordion-item__header ~ .accordion-item__content    contains    hidden
    Click    \#avustusten-hakuajat.accordion-item__header
    Get Attribute    \#avustusten-hakuajat.accordion-item__header button    aria-expanded    ==    true
    Get Element States
    ...    \#avustusten-hakuajat.accordion-item__header ~ .accordion-item__content
    ...    contains
    ...    visible
