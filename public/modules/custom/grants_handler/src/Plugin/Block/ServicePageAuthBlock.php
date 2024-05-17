<?php

namespace Drupal\grants_handler\Plugin\Block;

use Carbon\Carbon;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_handler\ServicePageBlockService;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a service page block.
 *
 * @Block(
 *   id = "grants_handler_service_page_auth_block",
 *   admin_label = @Translation("Service Page Auth Block"),
 *   category = @Translation("Custom")
 * )
 *
 * @phpstan-consistent-constructor
 */
class ServicePageAuthBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The helfi_helsinki_profiili service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helfiHelsinkiProfiili;

  /**
   * Get route parameters.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected CurrentRouteMatch $routeMatch;

  /**
   * Get current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected AccountProxy $currentUser;

  /**
   * Get profile data.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * The service page block service.
   *
   * @var \Drupal\grants_handler\ServicePageBlockService
   */
  protected ServicePageBlockService $servicePageBlockService;

  /**
   * Constructs a new ServicePageBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $pluginId
   *   The plugin_id for the plugin instance.
   * @param mixed $pluginDefinition
   *   The plugin implementation definition.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helfiHelsinkiProfiili
   *   The helfi_helsinki_profiili service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   Get route params.
   * @param \Drupal\Core\Session\AccountProxy $user
   *   Current user.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Get profile data.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Entity info.
   * @param \Drupal\grants_handler\ServicePageBlockService $servicePageBlockService
   *   The service page block service.
   */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    HelsinkiProfiiliUserData $helfiHelsinkiProfiili,
    CurrentRouteMatch $routeMatch,
    AccountProxy $user,
    GrantsProfileService $grantsProfileService,
    EntityTypeManager $entityTypeManager,
    ServicePageBlockService $servicePageBlockService
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->helfiHelsinkiProfiili = $helfiHelsinkiProfiili;
    $this->routeMatch = $routeMatch;
    $this->currentUser = $user;
    $this->grantsProfileService = $grantsProfileService;
    $this->entityTypeManager = $entityTypeManager;
    $this->servicePageBlockService = $servicePageBlockService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('helfi_helsinki_profiili.userdata'),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('grants_profile.service'),
      $container->get('entity_type.manager'),
      $container->get('grants_handler.service_page_block_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResultNeutral|AccessResult|AccessResultAllowed|AccessResultInterface {
    if (!$webform = $this->servicePageBlockService->loadServicePageWebform()) {
      return AccessResult::forbidden('No referenced Webform.');
    }

    if (!$this->servicePageBlockService->isCorrectApplicantType($webform)) {
      return AccessResult::forbidden('User is of wrong applicant type.');
    }

    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $tOpts = ['context' => 'grants_handler'];

    // Load the webform. No need to check if it exists since this
    // has already been done in the access checks.
    $webform = $this->servicePageBlockService->loadServicePageWebform();
    $webformId = $webform->id();

    if (!ApplicationHandler::isApplicationOpen($webform)) {
      $build['content'] = [
        '#theme' => 'grants_service_page_block',
        '#text' =>  $this->t('This application is not open', [], $tOpts),
        '#auth' => 'not_open',
      ];
      return $build;
    }

    // Create link for new application.
    $applicationLinkUrl = Url::fromRoute(
      'grants_handler.new_application',
      [
        'webform_id' => $webformId,
      ],
      [
        'attributes' => [
          'class' => ['hds-button', 'hds-button--primary'],
        ],
      ]
    );

    $applicationLinkText = [
      '#theme' => 'edit-label-with-icon',
      '#icon' => 'document',
      '#text_label' => $this->t('New application', [], $tOpts),
    ];

    $applicationLink = Link::fromTextAndUrl($applicationLinkText, $applicationLinkUrl);
    $descrtiption = $this->t('Please familiarize yourself with the instructions on this page before proceeding to the application.', [], $tOpts);
    $webformLink = Url::fromRoute('grants_webform_print.print_webform', ['webform' => $webformId]);

    $build['content'] = [
      '#theme' => 'grants_service_page_block',
      '#link' => $applicationLink,
      '#text' => $descrtiption,
      '#auth' => 'auth',
      '#webformLink' => $webformLink,
    ];

    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $node = $this->routeMatch->getParameter('node');
    return Cache::mergeTags(parent::getCacheTags(), $node->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), [
      'languages:language_content',
      'url.path',
    ]);
  }

  /**
   * Builds the link content as LinkItem values.
   *
   * @return array|bool
   *   False if nothing to show, otherwise ready to use array for LinkItem.
   */
  public function buildAsTprLink() {
    $tOpts = ['context' => 'grants_handler'];

    if ($this->currentUser->isAnonymous()) {
      return FALSE;
    }

    $roles = $this->currentUser->getRoles();
    if (!in_array('helsinkiprofiili', $roles)) {
      return FALSE;
    }

    $node = $this->routeMatch->getParameter('node');

    $webformId = $node->get('field_webform')->target_id;

    try {
      $access = $this->checkFormAccess();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      $access = FALSE;
    }

    if (!$access) {
      return FALSE;
    }

    // Create link for new application.
    $link = Url::fromRoute('grants_handler.new_application',
      [
        'webform_id' => $webformId,
      ], ['absolute' => TRUE]);

    return [
      'title' => $this->t('New application', [], $tOpts),
      'uri' => $link->toString(),
      'options' => [],
      '_attributes' => [],
    ];
  }

}
