<?php

namespace Drupal\grants_mandate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\grants_mandate\GrantsMandateService;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Grants Profile form.
 *
 * @phpstan-consistent-constructor
 */
class ApplicantMandateForm extends FormBase {

  /**
   * Access to profile data.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Access to helsinki profile data.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData;

  /**
   * Use mandate service things.
   *
   * @var \Drupal\grants_mandate\GrantsMandateService
   */
  protected GrantsMandateService $grantsMandateService;

  /**
   * Constructs a new ModalAddressForm object.
   */
  public function __construct(
    GrantsProfileService $grantsProfileService,
    HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
    GrantsMandateService $grantsMandateService
  ) {
    $this->grantsProfileService = $grantsProfileService;
    $this->helsinkiProfiiliUserData = $helsinkiProfiiliUserData;
    $this->grantsMandateService = $grantsMandateService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ApplicantMandateForm|static {
    return new static(
      $container->get('grants_profile.service'),
      $container->get('helfi_helsinki_profiili.userdata'),
      $container->get('grants_mandate.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grants_mandate_type';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $tOpts = ['context' => 'grants_mandate'];

    $userData = $this->helsinkiProfiiliUserData->getUserData();

    $profileOptions = [
      'new' => $this->t('Add new Unregistered community or group', [], $tOpts),
    ];
    $profiles = [];
    try {
      $profiles = $this->grantsProfileService->getUsersGrantsProfiles($userData['sub'], 'unregistered_community');

      /** @var \Drupal\helfi_atv\AtvDocument $profile */
      foreach ($profiles as $profile) {
        $meta = $profile->getMetadata();
        $content = $profile->getContent();
        /* Hide companies without a name.
         * Creation process allows them to happen
         * even though there are measures to avoid that
         */
        if (!$content['companyName']) {
          continue;
        }
        $profileOptions[$meta["profile_id"]] = $content['companyName'];
      }
    }
    catch (\Throwable $e) {
    }

    $form_state->setStorage([
      'userCommunities' => $profiles,
    ]);

    $form['info'] = [
      '#markup' => '<p>' .
      $this->t('Before proceeding to the grant application, you should
choose an applicant role. You can continue applying as an individual
or switch to applying on behalf of the community. When you choose to
apply on behalf of a registered community, you will be transferred
to Suomi.fi business authorization.', [], $tOpts) . '</p>',
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['registered_community'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['hds-card__body']],
      '#prefix' => '<div class="hds-card hds-card--applicant-role">',
      '#suffix' => '</div>',
    ];
    $form['actions']['registered_community']['info'] = [
      '#theme' => 'select_applicant_role',
      '#icon' => 'company',
      '#role' => $this->t('Registered community', [], $tOpts),
      '#role_description' => $this->t('Registered community is,
for example, a company, non-profit organization,
organization or association', [], $tOpts),
    ];
    $form['actions']['registered_community']['submit'] = [
      '#type' => 'submit',
      '#name' => 'registered_community',
      '#value' => $this->t('Select Registered community role and authorize mandate', [], $tOpts),
    ];
    $form['actions']['unregistered_community'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['hds-card__body']],
      '#prefix' => '<div class="hds-card hds-card--applicant-role">',
      '#suffix' => '</div>',
    ];
    $form['actions']['unregistered_community']['info'] = [
      '#theme' => 'select_applicant_role',
      '#icon' => 'group',
      '#role' => $this->t('Unregistered community or group', [], $tOpts),
      '#role_description' => $this->t('Apply for grant on behalf of your unregistered community or group', [], $tOpts),
    ];

    $form['actions']['unregistered_community']['unregistered_community_selection'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Unregistered community or group role', [], $tOpts),
      '#title_display' => 'invisible',
      '#options' => $profileOptions,
      '#empty_option' => $this->t('Choose', [], $tOpts),
      '#empty_value' => '0',
    ];

    $form['actions']['unregistered_community']['submit'] = [
      '#type' => 'submit',
      '#name' => 'unregistered_community',
      '#value' => $this->t('Select Unregistered community or group role', [], $tOpts),
      '#attached' => [
        'library' => [
          'grants_mandate/disable-mandate-submit',
        ],
      ],
    ];
    $form['actions']['private_person'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['hds-card__body']],
      '#prefix' => '<div class="hds-card hds-card--applicant-role">',
      '#suffix' => '</div>',
    ];
    $form['actions']['private_person']['info'] = [
      '#theme' => 'select_applicant_role',
      '#icon' => 'user',
      '#role' => $this->t('Private person', [], $tOpts),
      '#role_description' => $this->t('Apply for grant as a private person', [], $tOpts),
    ];
    $form['actions']['private_person']['submit'] = [
      '#name' => 'private_person',
      '#type' => 'submit',
      '#value' => $this->t('Select Private person role', [], $tOpts),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \Drupal\grants_mandate\GrantsMandateException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $tOpts = ['context' => 'grants_mandate'];

    $triggeringElement = $form_state->getTriggeringElement();

    $selectedType = $triggeringElement['#name'];
    $this->grantsProfileService->setApplicantType($selectedType);

    $selectedProfileData = [
      'type' => $selectedType,
    ];

    switch ($selectedType) {
      case 'unregistered_community':

        $redirect = $this->handleUnregisteredCommunity($form_state, $selectedProfileData, $tOpts);

        break;

      case 'private_person':
        $this->grantsMandateService->setPrivatePersonRole($selectedProfileData);

        // Redirect user to grants profile page.
        $redirectUrl = Url::fromRoute('grants_oma_asiointi.front');
        $redirect = new TrustedRedirectResponse($redirectUrl->toString());

        break;

      default:
        $mandateMode = 'ypa';
        $redirectUrl = Url::fromUri($this->grantsMandateService->getUserMandateRedirectUrl($mandateMode));
        $redirect = new TrustedRedirectResponse($redirectUrl->toString());

        break;
    }
    $form_state->setResponse($redirect);
  }

  /**
   * Extract unregistered handling to method to make sonar shut up.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state object.
   * @param array $selectedProfileData
   *   Profile data.
   * @param array $tOpts
   *   Translation options.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   Redirect response object.
   */
  public function handleUnregisteredCommunity(
    FormStateInterface $form_state,
    array $selectedProfileData,
    array $tOpts): TrustedRedirectResponse {
    $storage = $form_state->getStorage();
    $userCommunities = $storage['userCommunities'];

    $selectedCommunity = $form_state->getValue('unregistered_community_selection');

    if ($selectedCommunity == 'new') {
      $selectedProfileData['identifier'] = Uuid::uuid4()->toString();
      $selectedProfileData['name'] = $this->t('New Unregistered Community or group', [], $tOpts)
        ->render();
      $selectedProfileData['complete'] = FALSE;

      // Redirect user to grants profile page.
      $redirectUrl = Url::fromRoute('grants_profile.edit');

      $this->grantsProfileService->setSelectedRoleData($selectedProfileData);
    }
    else {
      $selectedCommunityObject = array_filter(
        $userCommunities,
        function ($item) use ($selectedCommunity) {
          $meta = $item->getMetadata();
          if ($meta['profile_id'] == $selectedCommunity) {
            return TRUE;
          }
          return FALSE;
        }
      );

      $selectedCommunityObject = reset($selectedCommunityObject);
      $selectedMetadata = $selectedCommunityObject->getMetadata();
      $selectedContent = $selectedCommunityObject->getContent();

      $selectedProfileData['identifier'] = $selectedMetadata['profile_id'];
      $selectedProfileData['name'] = $selectedContent["companyName"];
      $selectedProfileData['complete'] = TRUE;

      $this->grantsProfileService->setSelectedRoleData($selectedProfileData);

      // Redirect user to grants profile page.
      $redirectUrl = Url::fromRoute('grants_oma_asiointi.front');
    }

    return new TrustedRedirectResponse($redirectUrl->toString());

  }

}
