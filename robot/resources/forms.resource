*** Settings ***
Documentation       Keywords used when submitting applications

Resource            ../resources/common.resource


*** Keywords ***
Completion Page
    Get Text    \#avustushakemus-lahetetty-onnistuneesti    ==    Avustushakemus lähetetty onnistuneesti
    ${application_id} =    Get Text    .breadcrumb a:last-of-type
    Get Url    *=    ${application_id}

Search For Application
    [Arguments]    ${search_term}
    Fill Text    \#edit-combine    ${search_term}
    Click    \#edit-submit-application-search
    Wait Until Network Is Idle

Start New Application
    [Arguments]    ${application_title}
    Click    text=${application_title}
    Get Title    *=    ${application_title}
    Click    \#block-servicepageauthblock .hds-button
    Wait For Elements State    li[data-webform-page="1_hakijan_tiedot"].is-active    visible

Submit Application
    Wait Until Network Is Idle
    Click    \#accept_terms_1 ~ label
    # Submitting form can take a long time
    ${old_timeout} =    Set Browser Timeout    120s
    Click    \#edit-actions-submit
    Set Browser Timeout    ${old_timeout}
