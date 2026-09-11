<?php

declare(strict_types=1);

namespace Drupal\grants_industries\Hook;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Hook\Order\Order;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\grants_industries\Services\WebformAccessCheckService;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Form hook implementations for grants_industries module.
 */
final class FormHooks {

  use AutowireTrait;

  public function __construct(
    private readonly AccountProxyInterface $currentUser,
    #[Autowire(service: 'grants_industries.webform_access_check_service')]
    private readonly WebformAccessCheckService $webformAccessService,
  ) {
  }

  /**
   * Implements hook_form_alter().
   *
   * Runs last so the access restrictions below are not overridden.
   *
   * @phpstan-param array<mixed> $form
   */
  #[Hook('form_alter', order: Order::Last)]
  public function formAlter(array &$form, FormStateInterface $form_state, string $form_id): void {
    $userEntity = User::load($this->currentUser->id());
    $roles = $this->currentUser->getRoles();

    // Disallow access to various webform settings for non admin users.
    if ($form_id === 'webform_settings_form' && !$this->webformAccessService->hasAdminRole($userEntity)) {
      $form['general_settings']['#access'] = FALSE;
      $form['page_settings']['#access'] = FALSE;
      $form['ajax_settings']['#access'] = FALSE;
      $form['author_information']['#access'] = FALSE;
      $form['share_settings']['#access'] = FALSE;
      $form['advanced_settings']['#access'] = FALSE;

      // Restrict access to certain third-party settings for non-admin users.
      $form['third_party_settings']['grants_metadata']['applicationTypeSelect']['#disabled'] = TRUE;
      $form['third_party_settings']['grants_metadata']['applicationType']['#disabled'] = TRUE;
      $form['third_party_settings']['grants_metadata']['applicationTypeID']['#disabled'] = TRUE;
      $form['third_party_settings']['grants_metadata']['applicationIndustry']['#disabled'] = TRUE;
      $form['third_party_settings']['grants_metadata']['applicantTypes']['#disabled'] = TRUE;
      $form['third_party_settings']['grants_metadata']['applicationTypeTerms']['#disabled'] = TRUE;
    }

    if (!in_array('helsinkiprofiili', $roles)) {
      return;
    }

    if ($form_id == 'user_form' && !$this->webformAccessService->hasAdminRole($userEntity)) {
      $form['field_industry']['#disabled'] = TRUE;
    }
  }

}
