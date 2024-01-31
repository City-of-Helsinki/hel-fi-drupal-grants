<?php

namespace Drupal\grants_handler\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_profile\Form\GrantsProfileFormRegisteredCommunity;
use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a 'community_officials_composite'.
 *
 * Webform composites contain a group of sub-elements.
 *
 *
 * IMPORTANT:
 * Webform composite can not contain multiple value elements (i.e. checkboxes)
 * or composites (i.e. community_officials_composite)
 *
 * @FormElement("community_officials_composite")
 *
 * @see \Drupal\webform\Element\WebformCompositeBase
 * @see \Drupal\grants_handler\Element\WebformExampleComposite
 */
class CommunityOfficialsComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getInfo(): array {
    return parent::getInfo() + ['#theme' => 'community_officials_composite'];
  }

  /**
   * {@inheritdoc}
   */
  public static function getCompositeElements(array $element): array {
    $is_required = FALSE;
    $is_disabled = FALSE;
    $description = NULL;
    $tOpts = ['context' => 'grants_handler'];

    if (\Drupal::currentUser()->isAuthenticated()) {
      /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
      $grantsProfileService = \Drupal::service('grants_profile.service');
      $selectedCompany = $grantsProfileService->getSelectedRoleData();
      $profileType = $grantsProfileService->getApplicantType();
      $is_required = ($profileType === 'unregistered_community');

      if (is_array($selectedCompany)) {
        $profileData = $grantsProfileService->getGrantsProfileContent($selectedCompany);
        if (isset($profileData['officials']) && $profileType === 'registered_community' && count($profileData['officials']) == 0) {
          $is_disabled = TRUE;
          $description = t('You do not have any community officials saved in your profile, so you cannot add any to the application.', [], $tOpts);
        }
      }

    }
    $elements = [];

    $elements['community_officials_select'] = [
      '#type' => 'select',
      '#title' => t('Select official', [], $tOpts),
      '#required' => $is_required,
      '#after_build' => [[get_called_class(), 'buildOfficialOptions']],
      '#options' => [],
      '#description' => $description,
      '#disabled' => $is_disabled,
      '#attributes' => [
        'class' => [
          'community-officials-select',
        ],
      ],
    ];

    $elements['name'] = [
      '#type' => 'textfield',
      '#readonly' => true,
      '#title_display' => 'visually-hidden',
      '#title' => t('Name'),
    ];
    $elements['role'] = [
      '#type' => 'textfield',
      '#readonly' => true,
      '#title_display' => 'visually-hidden',
      '#title' => t('Role', [], $tOpts),
    ];
    $elements['email'] = [
      '#type' => 'textfield',
      '#readonly' => true,
      '#title_display' => 'visually-hidden',
      '#title' => t('Email'),
    ];
    $elements['phone'] = [
      '#type' => 'textfield',
      '#readonly' => true,
      '#title_display' => 'visually-hidden',
      '#title' => t('Phone', [], $tOpts),
    ];

    return $elements;
  }

  /**
   * Build select option from profile data.
   *
   * The default selection CANNOT be done here.
   *
   * @param array $element
   *   Element to fix.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Fixed element
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   *
   * @see grants_handler.module
   */
  public static function buildOfficialOptions(array $element, FormStateInterface $form_state): array {
    $tOpts = ['context' => 'grants_handler'];

    // If user has no helsinkiprofiili role, then they have nothing to do here
    // IF this method fails for admins, the form config saving will fail.
    $user = \Drupal::currentUser()->getAccount();
    if (!in_array('helsinkiprofiili', $user->getRoles())) {
      return [];
    }

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');
    $officialRole = GrantsProfileFormRegisteredCommunity::getOfficialRoles();
    $selectedCompany = $grantsProfileService->getSelectedRoleData();
    $profileData = $grantsProfileService->getGrantsProfileContent($selectedCompany ?? '');

    $defaultDelta = '0';

    $options = [
      '' => '- ' . t('Select', [], $tOpts) . ' -',
    ];

    if (isset($profileData['officials'])) {
      $persons = $profileData['officials'];
    }
    else {
      $persons = [];
    }

    foreach ($persons as $delta => $official) {
      $deltaString = (string) $delta;

      if ($official['role'] != '0') {
        $optionSelection = $official['name'] . ' (' . $officialRole[$official['role']] . ')';
      }
      else {
        $optionSelection = $official['name'];
      }
      $options[$deltaString] = $optionSelection;
    }

    $element['#options'] = $options;
    $element['#default_value'] = $defaultDelta;

    return $element;

  }

}
