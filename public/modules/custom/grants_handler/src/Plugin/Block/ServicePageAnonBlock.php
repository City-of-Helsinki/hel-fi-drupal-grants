<?php

namespace Drupal\grants_handler\Plugin\Block;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\grants_handler\ApplicationStatusServiceInterface;
use Drupal\grants_handler\ServicePageBlockService;
use Drupal\webform\Entity\Webform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a service page block.
 */
#[Block(
  id: "grants_handler_service_page_anon_block",
  admin_label: new TranslatableMarkup("Service Page Anon Block"),
  category: new TranslatableMarkup("Custom"),
)]
final class ServicePageAnonBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The webform.
   *
   * @var \Drupal\webform\Entity\Webform|null
   */
  protected ?Webform $webform = NULL;

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
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user.
   * @param \Drupal\grants_handler\ServicePageBlockService $servicePageBlockService
   *   The service page block service.
   * @param \Drupal\grants_handler\ApplicationStatusServiceInterface $applicationStatusService
   *   The application status service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected CurrentRouteMatch $routeMatch,
    protected AccountProxy $currentUser,
    protected ServicePageBlockService $servicePageBlockService,
    protected ApplicationStatusServiceInterface $applicationStatusService,
    protected CacheBackendInterface $cache,
    protected TimeInterface $time,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
      $container->get('grants_handler.application_status_service'),
      $container->get('cache.default'),
      $container->get('datetime.time'),
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
  public function build(): array {
    $webform = $this->getWebform();

    if (!$this->applicationStatusService->isApplicationOpen($webform)) {
      $build['content'] = [
        '#theme' => 'grants_service_page_block',
        '#text' => $this->t('This application is not open', options: ['context' => 'grants_handler']),
        '#auth' => 'not_open',
      ];
    }
    else {
      $mandateUrl = Url::fromRoute(
        'grants_mandate.mandateform',
        ['redirect_to_service_page' => TRUE],
        [
          'attributes' => ['class' => ['hds-button', 'hds-button--primary']],
        ]
      );

      $mandateText = [
        '#theme' => 'edit-label-with-icon',
        '#icon' => 'swap-user',
        '#text_label' => $this->t('Change your role', options: ['context' => 'grants_handler']),
      ];

      $loginUrl = Url::fromRoute(
        'user.login',
        [],
        [
          'attributes' => ['class' => ['hds-button', 'hds-button--primary']],
        ]
      );

      $loginText = [
        '#theme' => 'edit-label-with-icon',
        '#icon' => 'signin',
        '#text_label' => $this->t('Log in'),
      ];

      if ($this->currentUser->isAuthenticated()) {
        $link = Link::fromTextAndUrl($mandateText, $mandateUrl);
        $text = $this->t('Change your role and return to make the application with role which the application is instructed to be made.', options: ['context' => 'grants_handler']);
        $title = $this->t('Change role', options: ['context' => 'grants_handler']);
      }
      else {
        $link = Link::fromTextAndUrl($loginText, $loginUrl);
        $text = $this->t('Log in to the service and do the identification.', options: ['context' => 'grants_handler']);
        $title = $this->t('Identification', options: ['context' => 'grants_handler']);
      }

      $webformLink = Url::fromRoute('grants_webform_print.print_webform', ['webform' => $webform->id()]);
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
    }

    // Apply cache metadata.
    $cache_metadata = new CacheableMetadata();
    $cache_metadata->setCacheContexts($this->getCacheContexts());
    $cache_metadata->setCacheTags($this->getCacheTags());
    $cache_metadata->setCacheMaxAge($this->getCacheMaxAge());
    $cache_metadata->applyTo($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $tags = parent::getCacheTags();
    $node = $this->routeMatch->getParameter('node');
    if ($node && $node->id()) {
      $tags = Cache::mergeTags($tags, ["node:{$node->id()}"]);
    }

    $webform = $this->getWebform();
    if ($webform && $webform->id()) {
      $tags = Cache::mergeTags($tags, ["webform:{$webform->id()}"]);
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    // This block's output depends on the current route (e.g., the node)
    // and the current user (anonymous vs authenticated).
    // Add both as cache contexts.
    return Cache::mergeContexts(parent::getCacheContexts(), ['route', 'user']);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    $webform = $this->getWebform();
    if (!$webform) {
      return Cache::PERMANENT;
    }
    return $this->getCacheInvalidationTime($webform);
  }

  /**
   * Get cache invalidation time.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform entity.
   *
   * @return int
   *   Returns the expiration time.
   */
  protected function getCacheInvalidationTime(Webform $webform): int {
    $open = $webform->getThirdPartySetting('grants_metadata', 'applicationOpen');
    $close = $webform->getThirdPartySetting('grants_metadata', 'applicationClose');

    $now = $this->time->getCurrentTime();

    $expiry_times = [];

    if (!empty($open)) {
      $open_ts = strtotime($open);
      if ($open_ts > $now) {
        $expiry_times[] = $open_ts - $now;
      }
    }

    if (!empty($close)) {
      $close_ts = strtotime($close);
      if ($close_ts > $now) {
        $expiry_times[] = $close_ts - $now;
      }
    }
    return !empty($expiry_times) ? min($expiry_times) : Cache::PERMANENT;
  }

  /**
   * Get the webform.
   *
   * @return \Drupal\webform\Entity\Webform|null
   *   Returns the webform or null.
   */
  protected function getWebform(): ?Webform {
    if (!$this->webform) {
      // Load the webform. No need to check if it exists since this
      // has already been done in the access checks.
      $this->webform = $this->servicePageBlockService->loadServicePageWebform();
    }
    return $this->webform;
  }

}
