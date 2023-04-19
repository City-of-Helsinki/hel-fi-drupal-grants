<?php

namespace Drupal\grants_applicant_info\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Entity\Webform;

/**
 * Provides a 'applicant_info'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. applicant_info)
 *
 * @FormElement("applicant_info")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 */
class ApplicantInfoComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'applicant_info'];
  }

  /**
   * {@inheritdoc}
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public static function getCompositeElements(array $element): array {

    $webform = Webform::load($element['#webform']);
    $user = \Drupal::currentUser();

    if (!$webform || !in_array('helsinkiprofiili',$user->getRoles())) {
      return [];
    }

    $elements = [];
    $thirdPartySettings = $webform->getThirdPartySettings('grants_metadata');
    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');
    $selectedRoleData = $grantsProfileService->getSelectedRoleData();
    $grantsProfile = $grantsProfileService->getGrantsProfile($selectedRoleData);

    $elements['applicantType'] = [
      '#type' => 'hidden',
      '#value' => $selectedRoleData["type"]
    ];


    switch ($selectedRoleData["type"]) {

      case 'private_person':
        self::getPrivatePersonForm($elements, $grantsProfile);
        break;

      case 'unregistered_community':
        self::getUnregisteredForm($elements);
        break;

      default:
        self::getRegisteredForm($elements, $grantsProfile);
        break;


    }

    return $elements;
  }

  /**
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  protected static function getPrivatePersonForm(array &$elements, $grantsProfile){

    $profileContent = $grantsProfile->getContent();
    /** @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliDataService */
    $helsinkiProfiiliDataService = \Drupal::service('helfi_helsinki_profiili.userdata');
    $userData = $helsinkiProfiiliDataService->getUserProfileData();



    $elements['firstname'] = [
      '#type' => 'textfield',
      '#title' => t('First name'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $userData["myProfile"]["verifiedPersonalInformation"]["firstName"]
    ];
    $elements['lastname'] = [
      '#type' => 'textfield',
      '#title' => t('Last name'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $userData["myProfile"]["verifiedPersonalInformation"]["lastName"]
    ];
    $elements['socialSecurityNumber'] = [
      '#type' => 'textfield',
      '#title' => t('Social security number'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $userData["myProfile"]["verifiedPersonalInformation"]["nationalIdentificationNumber"]
    ];
    $elements['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $userData["myProfile"]["primaryEmail"]["email"]
    ];

    $elements['street'] = [
      '#type' => 'textfield',
      '#title' => t('Street Address'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["addresses"][0]["street"]
    ];
    $elements['city'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["addresses"][0]["city"]
    ];
    $elements['postCode'] = [
      '#type' => 'textfield',
      '#title' => t('Postal Code'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["addresses"][0]["postCode"]
    ];
    $elements['country'] = [
      '#type' => 'textfield',
      '#title' => t('Country'),
      '#readonly' => TRUE,
      '#required' => FALSE,
      '#value' => $profileContent["addresses"][0]["country"]
    ];
  }

  protected static function getUnregisteredForm(array &$elements){
    $elements['isSportsClub'] = [
      '#type' => 'textfield',
      '#title' => t('Social security number')
    ];
    $elements['registerNumber'] = [
      '#type' => 'textfield',
      '#title' => t('Social security number')
    ];
  }

  protected static function getRegisteredForm(array &$elements, $grantsProfile ){

    $profileContent = $grantsProfile->getContent();

    $elements['companyNumber'] = [
      '#type' => 'textfield',
      '#title' => t('Company number'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["businessId"]
    ];
    $elements['communityOfficialName'] = [
      '#type' => 'textfield',
      '#title' => t('Community official name'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["companyName"]
    ];
    $elements['communityOfficialNameShort'] = [
      '#type' => 'textfield',
      '#title' => t('Community official shortname'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["companyNameShort"]
    ];
    $elements['registrationDate'] = [
      '#type' => 'textfield',
      '#title' => t('Registartion date'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["registrationDate"]
    ];
    $elements['foundingYear'] = [
      '#type' => 'textfield',
      '#title' => t('Founding year'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["foundingYear"]
    ];

    $elements['home'] = [
      '#type' => 'textfield',
      '#title' => t('Home'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["companyHome"]
    ];
    $elements['homePage'] = [
      '#type' => 'textfield',
      '#title' => t('Home page'),
      '#readonly' => TRUE,
      '#required' => FALSE,
      '#value' => $profileContent["homePage"] ?? ''
    ];

  }


  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);

    $elementValue = $element['#value'];



    return $element;
  }

  /**
   * Build select option from profile data.
   *
   * The default selection CANNOT be done here.
   *
   * @param array $element
   *   Element to change.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   *
   * @return array
   *   Updated element
   *
   * @see grants_handler.module
   */
  public static function buildPremiseListOptions(array $element, FormStateInterface $form_state): array {

    return $element;

  }

}
