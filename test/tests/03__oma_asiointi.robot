*** Settings ***
Documentation       Robot test for testing oma asiointi
Metadata            Examplemetadata    This is a simple browser test for ${baseurl}. Metadata is shown in the reports.

Library             Browser
Library             String
Resource            ../resources/common.resource
Resource            ../resources/tunnistamo.resource

Test Setup          Initialize Browser Session
Test Teardown       Run Common Teardown Process


*** Test Cases ***
Login And Check Oma Asiointi Data
    Do Company Login Process With Tunnistamo
    Go To Oma Asiointi
    Check Oma Asiointi Data
    Sort Sent Applications


*** Keywords ***
Check Oma Asiointi Data
    Get Element Count    h2\#keskeneraiset-hakemukset    ==    1
    Get Element Count    h2\#lahetetyt-hakemukset    ==    1

Sort Sent Applications
    Select Options By    \#applicationListSort    value    asc application-list__item--status
    Select Options By    \#applicationListSort    value    desc application-list__item--submitted
