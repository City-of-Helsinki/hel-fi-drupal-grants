*** Settings ***
Documentation       A resource file with Tunnistamo related keywords

Resource            ./common.resource


*** Keywords ***
Login With Tunnistamo
    Click Identification Method
    Input Demo Hetu
    Click    id=tunnistaudu
    Wait Until Network Is Idle
    Wait For Condition    Title    should start with    Suomi.fi-tunnistus
    Click    \#continue-button

Choose Company Profile With Tunnistamo
    Click Company Row
    Click Choose Company And Continue

Click Identification Method
    Click    \#fakevetuma2
    Get Title    ==    Testitunnistaja

Input Demo Hetu
    Type Text    \#hetu_input    ${TUNNISTAMO_HETU}

Click Company Row
    Click    .fi-radio-button_input[value="${TUNNISTAMO_COMPANY_ID}"] ~ .fi-radio-button_label
    Wait For Elements State    .fi-chip--content    visible

Click Choose Company And Continue
    Click    form[name="authorizeForm"] button[type="submit"]
