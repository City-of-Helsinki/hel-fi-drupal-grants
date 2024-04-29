<?php

namespace Drupal\grants_front_banner\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\user\Form\UserLoginForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a grants front banner block.
 *
 * @Block(
 *   id = "grants_front_banner",
 *   admin_label = @Translation("Grants Front Banner"),
 *   category = @Translation("Oma Asiointi")
 * )
 *
 * @phpstan-consistent-constructor
 */
class GrantsFrontBannerBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The grants_profile.service service.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * Construct block object.
   *
   * @param array $configuration
   *   Block config.
   * @param string $plugin_id
   *   Plugin.
   * @param mixed $plugin_definition
   *   Plugin def.
   * @param \Drupal\grants_profile\GrantsProfileService $grants_profile_service
   *   The grants_profile.service service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user.
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   Form builder.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    GrantsProfileService $grants_profile_service,
    AccountInterface $currentUser,
    FormBuilder $formBuilder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->grantsProfileService = $grants_profile_service;
    $this->currentUser = $currentUser;
    $this->formBuilder = $formBuilder;
  }

  /**
   * Factory function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   * @param array $configuration
   *   Block config.
   * @param string $plugin_id
   *   Plugin.
   * @param mixed $plugin_definition
   *   Plugin def.
   *
   * @return static
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('grants_profile.service'),
      $container->get('current_user'),
      $container->get('form_builder'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function build() {

    // @todo Refactor to support other profile types https://helsinkisolutionoffice.atlassian.net/browse/AU-662
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();

    $getGrantsProfile = NULL;
    if ($selectedCompany) {
      $getGrantsProfile = $this->grantsProfileService->getGrantsProfile($selectedCompany);
    }

    $logged_in = $this->currentUser->isAuthenticated();
    $fillinfo = Url::fromRoute('grants_profile.edit');
    $loginForm = $this->formBuilder->getForm(UserLoginForm::class);

    $build = [
      '#theme' => 'grants_front_banner',
      '#loggedin' => $logged_in,
      '#fillinfo' => $fillinfo,
      '#loginform' => $loginForm,
      '#getgrantsprofile' => $getGrantsProfile,
    ];
    return $build;
  }

}
