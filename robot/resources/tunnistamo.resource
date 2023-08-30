*** Settings ***
Documentation       A resource file with Tunnistamo related keywords

Resource            ./common.resource


*** Keywords ***
Login With Tunnistamo
    Click Identification Method
    Input Demo Hetu
    Continue Identification

Choose Company Profile With Tunnistamo
    Click Company Row
    Click Choose Company And Continue

Click Identification Method
    Wait For Elements State    \#fakevetuma2    visible    30s
    Click    \#fakevetuma2
    Get Title    ==    Testitunnistaja

Input Demo Hetu
    Type Text    \#hetu_input    ${TUNNISTAMO_HETU}
    Click    \#tunnistaudu
    Wait For Elements State    \#continue-button
    Get Title    ==    Suomi.fi-tunnistus

Continue Identification
    Click    \#continue-button

Click Company Row
    Click    .fi-radio-button_input[value="${TUNNISTAMO_COMPANY_ID}"] ~ .fi-radio-button_label
    Wait For Elements State    .fi-chip--content    visible

Click Choose Company And Continue
    Click    form[name="authorizeForm"] button[type="submit"]
