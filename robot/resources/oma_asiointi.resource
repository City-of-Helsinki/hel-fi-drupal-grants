*** Settings ***
Resource    ../resources/common.resource


*** Keywords ***
Check Oma Asiointi
    Go To Oma Asiointi
    Check Oma Asiointi Data
    Sort Sent Applications

Check Oma Asiointi Data
    Get Element Count    h2\#keskeneraiset-hakemukset    ==    1
    Get Element Count    h2\#lahetetyt-hakemukset    ==    1

Sort Sent Applications
    Select Options By    \#applicationListSort    value    asc application-list__item--status
    Select Options By    \#applicationListSort    value    desc application-list__item--submitted
