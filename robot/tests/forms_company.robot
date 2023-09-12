*** Settings ***
Documentation       Tests for submitting forms as a company user

Resource            ../resources/common.resource
Resource            ../resources/oma_asiointi.resource
Resource            ../resources/forms/kasvatus_ja_koulutus_yleisavustu.resource
Resource            ../resources/forms/yleisavustushakemus.resource
Resource            ../resources/forms/kulttuurin_kehittamis.resource
Resource            ../resources/forms/kulttuurin_toiminta.resource
Resource            ../resources/forms/nuorlomaleir.resource
Resource            ../resources/forms/kasko_ip_lisa.resource
Resource            ../resources/forms/kulttuurin_projekti.resource

Suite Setup         Login To Service As Company User
Suite Teardown      Close Browser
Test Setup          Go To Front Page


*** Test Cases ***
Oma Asiointi
    [Tags]    robot:skip
    Check Oma Asiointi

Kasvatus ja koulutus: Yleisavustuslomake
    [Tags]    robot:skip
    Submit "Kasvatus ja koulutus: yleisavustuslomake" application

Kaupunginhallituksen yleisavustushakemus
    [Tags]    robot:skip
    Submit "Kaupunginhallitus, yleisavustus" application

Taide ja kulttuuri: Kehittämisavustus
    [Tags]    robot:skip
    Submit "Kulttuurin kehittämisavustukset" application

Taide- ja kulttuuriavustukset: Toiminta-avustus
    [Tags]    robot:skip
    Submit "Kulttuurin taide- ja kulttuuriavustukset: toiminta-avustukset" application

Kasvatus ja koulutus: Iltapäivätoiminnan harkinnanvarainen lisäavustushakemus
    [Tags]    robot:skip
    Submit "Iltapäivätoiminnan harkinnanvarainen lisäavustushakemus" application

Loma-aikojen leiriavustushakemus
    [Tags]    robot:skip
    Submit "Nuorisotoiminnan loma-aikojen leiriavustus" application

Taide ja kulttuuri: Projektiavustus
    [Tags]    robot:skip
    Submit "Kulttuurin taide- ja kulttuuriavustukset: Projektiavustukset" application
