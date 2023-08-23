*** Settings ***
Documentation       Robot test for testing public Drupal website functionality
Metadata            Examplemetadata    This is a simple browser test for ${baseurl}. Metadata is shown in the reports.

Library             Browser
Library             String
Resource            ../resources/common.resource
Resource            ../resources/tunnistamo.resource

Suite Setup         Initialize Browser Session
Suite Teardown      Close Browser


*** Test Cases ***
Test General UI Functionality
    Open Main Menu Dropdown
    Change Language
    Check Footer Links

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
Go To Application Search
    Click    \#block-mainnavigation a[data-drupal-link-system-path="etsi-avustusta"]
    Get Title    ==    Etsi avustusta | ${SITE_NAME}

Search Grants
    ${search_input}=    Get Element    input[data-drupal-selector="edit-combine"]
    Scroll To Element    ${search_input}
    Type Text    ${search_input}    avustus
    Click    \#edit-submit-application-search
    Get Attribute    ${search_input}    value    ==    avustus

Go To FAQ
    Click    \#block-mainnavigation a[data-drupal-link-system-path="node/47"] ~ button
    Click    \#block-mainnavigation a[data-drupal-link-system-path="tietoa-avustuksista/ukk"]
    Get Title    ==    UKK | ${SITE_NAME}

Go To First Application
    Click    .view-application-search .views-row:nth-child(1) a.application_search--link
    # Application start button should not exist since we are not logged in
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

Change Language
    Get Element Count    .language-switcher a[lang="fi"][aria-current="true"]    ==    1
    Click    .language-switcher a[lang="sv"]
    Get Element Count    .language-switcher a[lang="sv"][aria-current="true"]    ==    1
    Click    .language-switcher a[lang="fi"]
    Get Element Count    .language-switcher a[lang="fi"][aria-current="true"]    ==    1

Filter FAQ Categories
    Get Element Count    .ukk--filters .category:not(.category-unselected)    ==    1
    Click    .ukk--filters .category[href="?ukk=13"]
    Get Element Count    .ukk--filters .category:not(.category-unselected)    ==    1
    Get Element Count    .ukk--filters .category:not(.category-unselected)[href="?ukk=13"]    ==    1
    Go Back
    Get Element Count    .ukk--filters .category:not(.category-unselected)    ==    1

Test FAQ Accordion
    Get Attribute    \#mista-loydan-hakemusluonnoksen-.accordion-item__header button    aria-expanded    ==    false
    Get Element States
    ...    \#mista-loydan-hakemusluonnoksen-.accordion-item__header ~ .accordion-item__content
    ...    contains
    ...    hidden
    Click    \#mista-loydan-hakemusluonnoksen-.accordion-item__header
    Get Attribute    \#mista-loydan-hakemusluonnoksen-.accordion-item__header button    aria-expanded    ==    true
    Get Element States
    ...    \#mista-loydan-hakemusluonnoksen-.accordion-item__header ~ .accordion-item__content
    ...    contains
    ...    visible

Check Footer Links
    Get Element Count    .footer \#block-hdbt-subtheme-footertopnavigation a    >=    1
    Get Element Count    .footer \#block-footertopnavigationsecond-2 a    >=    1
    Get Element Count    .footer \#block-hdbt-subtheme-footerbottomnavigation a    >=    1

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
