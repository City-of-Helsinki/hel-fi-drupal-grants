<?php

namespace Drupal\grants_profile\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\grants_profile\Form\GrantsProfileFormPrivatePerson;
use Drupal\grants_profile\Form\GrantsProfileFormRegisteredCommunity;
use Drupal\grants_profile\Form\GrantsProfileFormUnregisteredCommunity;
use Drupal\grants_profile\GrantsProfileException;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\grants_profile\ProfileFetchTimeoutException;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Grants Profile routes.
 *
 * @phpstan-consistent-constructor
 */
class GrantsProfileController extends ControllerBase {

  use AutowireTrait;

  /**
   * ModalFormContactController constructor.
   *
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Grants profile service.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData
   *   Data for Helsinki Profile.
   */
  public function __construct(
    protected GrantsProfileService $grantsProfileService,
    #[Autowire(service: 'helfi_helsinki_profiili.userdata')]
    protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
  ) {}

  /**
   * Builds the response.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   *   Data to render
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
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
    catch (ProfileFetchTimeoutException $e) {
      $this->messenger()
        ->addError(
          $this->t(
            'Fetching the profile timed out. Please try again in a moment.',
            [],
            ['context' => 'grants_oma_asiointi']
          )
        );
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
    }
    catch (GrantsProfileException $e) {
      $this->messenger()
        ->addError($this->t('Connection error', [], $tOpts), TRUE);
      // Not much to do without actual data.
      return new RedirectResponse(Url::fromRoute('<front>')->toString());
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
      $this->t('Go to the Helsinki profile to update your email address.', [], $tOpts),
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

    $formObject = $this->formBuilder()->getForm(match ($selectedRoleData["type"]) {
      'private_person' => GrantsProfileFormPrivatePerson::class,
      'registered_community' => GrantsProfileFormRegisteredCommunity::class,
      'unregistered_community' => GrantsProfileFormUnregisteredCommunity::class,
    });

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
