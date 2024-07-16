<?php

namespace Drupal\grants_handler\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\grants_handler\ServicePageBlockService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a service page block.
 *
 * @Block(
 *   id = "grants_handler_service_page_anon_block",
 *   admin_label = @Translation("Service Page Anon Block"),
 *   category = @Translation("Custom")
 * )
 *
 * @phpstan-consistent-constructor
 */
class ServicePageAnonBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The service page block service.
   *
   * @var \Drupal\grants_handler\ServicePageBlockService
   */
  protected ServicePageBlockService $servicePageBlockService;

  /**
   * The application status service.
   *
   * @var \Drupal\grants_handler\ApplicationStatusService
   */
  protected ApplicationStatusService $applicationStatusService;

  /**
   * Constructs a new ServicePageBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   Get route params.
   * @param \Drupal\Core\Session\AccountProxy $user
   *   Current user.
   * @param \Drupal\grants_handler\ServicePageBlockService $servicePageBlockService
   *   The service page block service.
   * @param \Drupal\grants_handler\ApplicationStatusService $applicationStatusService
   *   The application status service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    CurrentRouteMatch $routeMatch,
    AccountProxy $user,
    ServicePageBlockService $servicePageBlockService,
    ApplicationStatusService $applicationStatusService
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->routeMatch = $routeMatch;
    $this->currentUser = $user;
    $this->servicePageBlockService = $servicePageBlockService;
    $this->applicationStatusService = $applicationStatusService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('grants_handler.service_page_block_service'),
      $container->get('grants_handler.application_status_service')
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
  protected function blockAccess(AccountInterface $account): AccessResultInterface {
    if (!$webform = $this->servicePageBlockService->loadServicePageWebform()) {
      return AccessResult::forbidden('No referenced Webform.');
    }

    $isCorrectApplicantType = $this->servicePageBlockService->isCorrectApplicantType($webform);
    return AccessResult::allowedIf(!$isCorrectApplicantType);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $tOpts = ['context' => 'grants_handler'];

    // Load the webform. No need to check if it exists since this
    // has already been done in the access checks.
    $webform = $this->servicePageBlockService->loadServicePageWebform();
    $webformId = $webform->id();

    // If the application isn't open, just display a message.
    if (!$this->applicationStatusService->isApplicationOpen($webform)) {
      $build['content'] = [
        '#theme' => 'grants_service_page_block',
        '#text' => $this->t('This application is not open', [], $tOpts),
        '#auth' => 'not_open',
      ];
      return $build;
    }

    $mandateUrl = Url::fromRoute(
      'grants_mandate.mandateform',
      ['redirect_to_service_page' => TRUE],
      [
        'attributes' => [
          'class' => ['hds-button', 'hds-button--primary'],
        ],
      ]
    );

    $mandateText = [
      '#theme' => 'edit-label-with-icon',
      '#icon' => 'swap-user',
      '#text_label' => $this->t('Change your role', [], $tOpts),
    ];

    $loginUrl = Url::fromRoute(
      'user.login',
      [],
      [
        'attributes' => [
          'class' => ['hds-button', 'hds-button--primary'],
        ],
      ]
    );

    $loginText = [
      '#theme' => 'edit-label-with-icon',
      '#icon' => 'signin',
      '#text_label' => $this->t('Log in'),
    ];

    if ($this->currentUser->isAuthenticated()) {
      $link = Link::fromTextAndUrl($mandateText, $mandateUrl);
      $text = $this->t('Change your role and return to make the
application with role which the application is instructed to be made.', [], $tOpts);
      $title = $this->t('Change role', [], $tOpts);
    }
    else {
      $link = Link::fromTextAndUrl($loginText, $loginUrl);
      $text = $this->t('Log in to the service and do the identification.', [], $tOpts);
      $title = $this->t('Identification', [], $tOpts);
    }

    $webformLink = Url::fromRoute('grants_webform_print.print_webform', ['webform' => $webformId]);
    $isCorrectApplicantType = $this->servicePageBlockService->isCorrectApplicantType($webform);

    $build['content'] = [
      '#theme' => 'grants_service_page_block',
      '#applicantType' => $isCorrectApplicantType,
      '#link' => $link,
      '#title' => $title,
      '#text' => $text,
      '#webformLink' => $webformLink,
      '#auth' => 'anon',
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $cache_tags = parent::getCacheTags();
    $node = $this->routeMatch->getParameter('node');
    $nodeCacheTag = 'node:' . $node->id();
    return Cache::mergeTags($cache_tags, [$nodeCacheTag]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    // If you depends on \Drupal::routeMatch()
    // you must set context of this block with 'route' context tag.
    // Every new route this block will rebuild.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route', 'user']);
  }

}
