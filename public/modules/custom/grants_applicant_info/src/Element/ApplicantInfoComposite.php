<?php

namespace Drupal\grants_applicant_info\Element;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\helfi_atv\AtvDocument;
use Drupal\webform\Element\WebformCompositeBase;
use Drupal\webform\Entity\Webform;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\grants_profile\GrantsProfileException|\Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public static function getCompositeElements(array $element): array {
    $tOpts = ['context' => 'grants_handler'];

    if (isset($element['#webform'])) {
      $webform = Webform::load($element['#webform']);
    }
    else {
      $webform = FALSE;
    }

    $user = \Drupal::currentUser();

    if (!$webform || !in_array('helsinkiprofiili', $user->getRoles())) {
      return [];
    }

    $elements = [];
    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');
    $selectedRoleData = $grantsProfileService->getSelectedRoleData();
    if (!$selectedRoleData) {
      return [];
    }
    $grantsProfile = $grantsProfileService->getGrantsProfile($selectedRoleData);

    $elements['applicantType'] = [
      '#type' => 'hidden',
      '#value' => $selectedRoleData["type"],
    ];
    $elements['applicant_type'] = [
      '#type' => 'hidden',
      '#value' => $selectedRoleData["type"],
    ];
    if ($grantsProfile === NULL) {

      \Drupal::messenger()
        ->addWarning(t('You must have grants profile created.', [], $tOpts));

      $url = Url::fromRoute('grants_profile.edit');
      $response = new RedirectResponse($url->toString());
      $request = \Drupal::request();
      // Save the session so things like messages get saved.
      $request->getSession()->save();
      $response->prepare($request);

      // Make sure to trigger kernel events.
      /** @var \Drupal\Core\DrupalKernel $kernelService */
      $kernelService = \Drupal::service('kernel');
      $kernelService->terminate($request, $response);

      $response->send();
      return [];
    }

    switch ($selectedRoleData["type"]) {

      case 'private_person':
        self::getPrivatePersonForm($elements, $grantsProfile);
        break;

      case 'unregistered_community':
        self::getUnregisteredForm($elements, $grantsProfile);
        break;

      case 'registered_community':
        self::getRegisteredForm($elements, $grantsProfile);
        break;

      default:
        break;

    }

    return $elements;
  }

  /**
   * Build the private person form elements.
   *
   * @param \Drupal\helfi_atv\AtvDocument $grantsProfile
   *   User Grants Profile.
   *
   * @return array
   *   Form Array.
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  protected static function getPrivatePersonFormElements(AtvDocument $grantsProfile) {
    $profileContent = $grantsProfile->getContent();
    /** @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliDataService */
    $helsinkiProfiiliDataService = \Drupal::service('helfi_helsinki_profiili.userdata');
    $userData = $helsinkiProfiiliDataService->getUserProfileData();

    $tOpts = ['context' => 'grants_handler'];

    $suffix = array_key_exists('phone_number', $profileContent) ? '' : '</div>';

    $elements['firstname'] = [
      '#type' => 'textfield',
      '#title' => t('First name'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $userData["myProfile"]["verifiedPersonalInformation"]["firstName"] ?? '',
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
      '#prefix' => '<div class="applicant-info--from-grants">',
    ];
    $elements['lastname'] = [
      '#type' => 'textfield',
      '#title' => t('Last name'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $userData["myProfile"]["verifiedPersonalInformation"]["lastName"] ?? '',
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
    ];
    $elements['socialSecurityNumber'] = [
      '#type' => 'textfield',
      '#title' => t('Social security number'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $userData["myProfile"]["verifiedPersonalInformation"]["nationalIdentificationNumber"] ?? '',
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
    ];
    $elements['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email', [], $tOpts),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $userData["myProfile"]["primaryEmail"]["email"] ?? '',
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
    ];

    $elements['street'] = [
      '#type' => 'textfield',
      '#title' => t('Street address'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["addresses"][0]["street"] ?? '',
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
    ];
    $elements['city'] = [
      '#type' => 'textfield',
      '#title' => t('City'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["addresses"][0]["city"] ?? '',
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
    ];
    $elements['postCode'] = [
      '#type' => 'textfield',
      '#title' => t('Postal code'),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["addresses"][0]["postCode"] ?? '',
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
    ];
    $elements['country'] = [
      '#type' => 'textfield',
      '#title' => t('Country'),
      '#readonly' => TRUE,
      '#required' => FALSE,
      '#value' => $profileContent["addresses"][0]["country"] ?? '',
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
      '#suffix' => $suffix,
    ];
    if (array_key_exists('phone_number', $profileContent)) {
      $elements['phone_number'] = [
        '#type' => 'textfield',
        '#title' => t('Phone number'),
        '#readonly' => TRUE,
        '#required' => TRUE,
        '#value' => $profileContent["phone_number"],
        '#wrapper_attributes' => [
          'class' => ['grants-handler--prefilled-field'],
        ],
        '#suffix' => '</div>',
      ];
    }
    return $elements;
  }

  /**
   * Form for private person.
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  protected static function getPrivatePersonForm(array &$elements, $grantsProfile) {

    $elements = array_merge($elements, self::getPrivatePersonFormElements($grantsProfile));
  }

  /**
   * Form unregistered community.
   *
   * @param array $elements
   *   ELements.
   * @param \Drupal\helfi_atv\AtvDocument $grantsProfile
   *   Profile data.
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  protected static function getUnregisteredForm(array &$elements, AtvDocument $grantsProfile) {
    $tOpts = ['context' => 'grants_profile'];

    $profileContent = $grantsProfile->getContent();

    $elements['communityOfficialName'] = [
      '#type' => 'textfield',
      '#title' => t('Name of association', [], $tOpts),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["companyName"],
      '#default_value' => $profileContent["companyName"],
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
      '#prefix' => '<div class="applicant-info--from-grants">',
      '#suffix' => '</div>',
    ];
    $elements = array_merge($elements, self::getPrivatePersonFormElements($grantsProfile));
  }

  /**
   * Registered form.
   *
   * @param array $elements
   *   Elements.
   * @param \Drupal\helfi_atv\AtvDocument $grantsProfile
   *   Atv documenht.
   */
  protected static function getRegisteredForm(array &$elements, AtvDocument $grantsProfile) {
    $tOpts = ['context' => 'grants_profile'];

    $profileContent = $grantsProfile->getContent();

    $elements['communityOfficialName'] = [
      '#type' => 'textfield',
      '#title' => t('Name of association', [], $tOpts),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["companyName"],
      '#default_value' => $profileContent["companyName"],
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
      '#prefix' => '<div class="applicant-info--from-prh">',
    ];
    $elements['companyNumber'] = [
      '#type' => 'textfield',
      '#title' => t('Business ID', [], $tOpts),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $profileContent["businessId"],
      '#default_value' => $profileContent["businessId"],
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
    ];
    $registrationDate = '';

    if (isset($profileContent["registrationDate"])) {
      $regDate = new DrupalDateTime($profileContent["registrationDate"], 'Europe/Helsinki');
      $registrationDate = $regDate->format('d.m.Y');
    }
    $elements['registrationDate'] = [
      '#type' => 'textfield',
      '#title' => t('Date of registration', [], $tOpts),
      '#readonly' => TRUE,
      '#required' => TRUE,
      '#value' => $registrationDate,
      '#default_value' => $registrationDate,
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
      '#suffix' => '</div>',
    ];
    $elements['home'] = [
      '#type' => 'textfield',
      '#title' => t('Municipality where the association is based (domicile)', [], $tOpts),
      '#readonly' => TRUE,
      '#required' => FALSE,
      '#value' => $profileContent["companyHome"],
      '#default_value' => $profileContent["companyHome"],
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
      '#prefix' => '<div class="applicant-info--from-grants">',
    ];
    $elements['communityOfficialNameShort'] = [
      '#type' => 'textfield',
      '#title' => t('Abbreviated name', [], $tOpts),
      '#readonly' => TRUE,
      '#required' => FALSE,
      '#value' => $profileContent["companyNameShort"],
      '#default_value' => $profileContent["companyNameShort"],
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
    ];
    $elements['foundingYear'] = [
      '#type' => 'textfield',
      '#title' => t('Year of establishment', [], $tOpts),
      '#readonly' => TRUE,
      '#required' => FALSE,
      '#value' => $profileContent["foundingYear"],
      '#default_value' => $profileContent["foundingYear"],
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
    ];
    $elements['homePage'] = [
      '#type' => 'textfield',
      '#title' => t('Website address', [], $tOpts),
      '#readonly' => TRUE,
      '#required' => FALSE,
      '#value' => $profileContent["companyHomePage"] ?? '',
      '#default_value' => $profileContent["companyHomePage"] ?? '',
      '#wrapper_attributes' => [
        'class' => ['grants-handler--prefilled-field'],
      ],
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function processWebformComposite(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processWebformComposite($element, $form_state, $complete_form);

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
