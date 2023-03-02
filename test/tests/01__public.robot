*** Settings ***
Documentation       Robot test for testing public Drupal website functionality
Metadata            Examplemetadata          This is a simple browser test for ${baseurl}. Metadata is shown in the reports.
Library             Browser
Library             String
Suite Setup
Resource            ../resources/common.resource
Resource            ../resources/tunnistamo.resource

*** Test Cases ***

Browse Public Drupal Website
    Open Browser To Home Page
    Accept Cookies Banner
    Go To Application Search
    Search Grants
    Go To First Application
    Go To FAQ
    Open Accordion on FAQ
    [Teardown]    Close Browser

*** Keywords ***

Go To Application Search
    Click          \#block-mainnavigation a[data-drupal-link-system-path="etsi-avustusta"]
    Get Title           ==    Etsi avustusta | ${SITE_NAME}

Search Grants
    Scroll To Element    \#views-exposed-form-application-search-page-1 input[data-drupal-selector="edit-combine"]
    Type Text   \#views-exposed-form-application-search-page-1 input[data-drupal-selector="edit-combine"]    avustus
    Click       \#views-exposed-form-application-search-page-1 input[data-drupal-selector="edit-submit-application-search"]
    Get Attribute   \#views-exposed-form-application-search-page-1 input[data-drupal-selector="edit-combine"]    value     ==      avustus
    Scroll To Element    .main-content .view-footer strong
    Get Text    .main-content .view-footer strong      !=      0

Go To FAQ
    Click           \#block-mainnavigation a[data-drupal-link-system-path="node/47"] ~ button
    Click           \#block-mainnavigation a[data-drupal-link-system-path="tietoa-avustuksista/ukk"]
    Get Title       ==      UKK | ${SITE_NAME}

Open Accordion on FAQ
    Scroll To Element     \#handorgel1-fold1-header
    Get Element States    \#handorgel1-fold1-content      contains      hidden
    Click          \#handorgel1-fold1-header
    Get Element States    \#handorgel1-fold1-content      contains      visible

Go To First Application
    Click      .view-application-search .views-row:nth-child(1) .views-field-view-node a
    Get Title           ==    ${APPLICATION_TITLE_ALT} | ${SITE_NAME_ALT}
    Get Text    h1      ==    ${APPLICATION_TITLE_ALT}
    # Application start button should not exist since we are not logged in
    Get Element Count   \#block-servicepageauthblock .hds-button   ==    0
