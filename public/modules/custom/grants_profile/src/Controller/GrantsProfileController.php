<?php

namespace Drupal\grants_profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\grants_profile\Form\GrantsProfileFormRegisteredCommunity;
use Drupal\grants_profile\GrantsProfileException;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Grants Profile routes.
 */
class GrantsProfileController extends ControllerBase {


  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Grants profile service.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Helsinki profile service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData;

  /**
   * ModalFormContactController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Grants profile service.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData
   *   Data for Helsinki Profile.
   */
  public function __construct(
    FormBuilder $form_builder,
    GrantsProfileService $grantsProfileService,
    HelsinkiProfiiliUserData $helsinkiProfiiliUserData
  ) {
    $this->formBuilder = $form_builder;
    $this->grantsProfileService = $grantsProfileService;
    $this->helsinkiProfiiliUserData = $helsinkiProfiiliUserData;
  }

  /**
   * {@inheritdoc}
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal service container.
   *
   * @return static
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('form_builder'),
      $container->get('grants_profile.service'),
      $container->get('helfi_helsinki_profiili.userdata')
    );
  }

  /**
   * Builds the response.
   *
   * @return array|Symfony\Component\HttpFoundation\RedirectResponse
   *   Data to render
   */
  public function viewProfile(): array|RedirectResponse {
    $selectedRoleData = $this->grantsProfileService->getSelectedRoleData();
    $tOpts = ['context' => 'grants_profile'];

    if ($selectedRoleData == NULL) {
      $this->messenger()
        ->addError($this->t('No profile data available, select company', [], $tOpts), TRUE);

      return new RedirectResponse('/asiointirooli-valtuutus');
    }
    try {
      $profile = $this->grantsProfileService->getGrantsProfileContent($selectedRoleData, TRUE);
    }
    catch (GrantsProfileException $e) {
      $this->messenger()
        ->addError($this->t('Connection error', [], $tOpts), TRUE);
      // Not much to do without actual data.
      return new RedirectResponse('<front>');
    }

    if (empty($profile)) {
      $editProfileUrl = Url::fromRoute(
        'grants_profile.edit'
      );
      return new RedirectResponse($editProfileUrl->toString());
    }

    $build['#theme'] = 'own_profile_' . $selectedRoleData["type"];
    $build['#profile'] = $profile;
    $build['#userData'] = $this->helsinkiProfiiliUserData->getUserProfileData();

    $profileEditUrl = Url::fromUri(getenv('HELSINKI_PROFIILI_URI'));
    $profileEditUrl->mergeOptions([
      'attributes' => [
        'title' => $this->t('If you want to change the information from Helsinki-profile
you can do that by going to the Helsinki-profile from this link.', [], $tOpts),
        'target' => '_blank',
      ],
    ]);

    $editProfileUrl = Url::fromRoute(
      'grants_profile.edit',
      [],
      [
        'attributes' => [
          'data-drupal-selector' => 'profile-edit-link',
          'class' => ['hds-button', 'hds-button--primary'],
        ],
      ]
    );

    $editProfileText = $this->t('Edit community information', [], $tOpts);
    if ($selectedRoleData["type"] === 'private_person') {
      $editProfileText = $this->t('Edit own information', [], $tOpts);
    }

    $editProfileText = [
      '#theme' => 'edit-label-with-icon',
      '#icon' => 'pen-line',
      '#text_label' => $editProfileText,
    ];

    $deleteProfileUrl = Url::fromRoute(
      'grants_profile.remove',
      [],
      [
        'attributes' => [
          'data-drupal-selector' => 'profile-delete-link',
          'class' => [
            'use-ajax',
            'hds-button',
            'hds-button--secondary',
          ],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => '{"width":400}',
        ],
      ]
    );
    $deleteProfileText = [
      '#theme' => 'edit-label-with-icon',
      '#icon' => 'trash',
      '#text_label' => $this->t('Remove profile', [], $tOpts),
    ];

    $build['#editHelsinkiProfileLink'] = Link::fromTextAndUrl(
      $this->t('Go to Helsinki-profile to edit your information.', [], $tOpts),
      $profileEditUrl
    );
    $build['#editProfileLink'] = Link::fromTextAndUrl($editProfileText, $editProfileUrl);
    $build['#deleteProfileLink'] = Link::fromTextAndUrl($deleteProfileText, $deleteProfileUrl);
    $build['#roles'] = GrantsProfileFormRegisteredCommunity::getOfficialRoles();

    return $build;
  }

  /**
   * Edit profile form.
   *
   * @return array
   *   Build data
   */
  public function editProfile(): array {

    $build = [];
    $build['#theme'] = 'edit_own_profile';

    $selectedRoleData = $this->grantsProfileService->getSelectedRoleData();

    $formObject = NULL;

    if ($selectedRoleData['type'] == 'private_person') {
      $formObject = $this->formBuilder->getForm('\Drupal\grants_profile\Form\GrantsProfileFormPrivatePerson');
    }

    if ($selectedRoleData['type'] == 'registered_community') {
      $formObject = $this->formBuilder->getForm('\Drupal\grants_profile\Form\GrantsProfileFormRegisteredCommunity');
    }

    if ($selectedRoleData['type'] == 'unregistered_community') {
      $formObject = $this->formBuilder->getForm('\Drupal\grants_profile\Form\GrantsProfileFormUnregisteredCommunity');
    }

    $build['#profileForm'] = $formObject;
    return $build;

  }

  /**
   * Redirect to my service page.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to profile page.
   */
  public function redirectToMyServices(): RedirectResponse {
    $showProfileUrl = Url::fromRoute(
      'grants_profile.show'
    );
    return new RedirectResponse($showProfileUrl->toString());
  }

}
