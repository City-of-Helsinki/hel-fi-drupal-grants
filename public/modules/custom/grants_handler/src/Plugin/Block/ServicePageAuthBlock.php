<?php

namespace Drupal\grants_handler\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Url;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\grants_handler\ServicePageBlockService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
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
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user.
   * @param \Drupal\grants_handler\ServicePageBlockService $servicePageBlockService
   *   The service page block service.
   * @param \Drupal\grants_handler\ApplicationStatusService $applicationStatusService
   *   The application status service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler.
 */
  public function __construct(
    array $configuration,
    $pluginId,
    $pluginDefinition,
    protected HelsinkiProfiiliUserData $helfiHelsinkiProfiili,
    protected CurrentRouteMatch $routeMatch,
    protected AccountProxy $currentUser,
    protected ServicePageBlockService $servicePageBlockService,
    protected ApplicationStatusService $applicationStatusService,
    protected ModuleHandlerInterface $moduleHandler,
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('helfi_helsinki_profiili.userdata'),
      $container->get('current_route_match'),
      $container->get('current_user'),
      $container->get('grants_handler.service_page_block_service'),
      $container->get('grants_handler.application_status_service'),
      $container->get('module_handler'),
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
    if ($this->checkFormAccess()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden('User does not have access to form.');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $tOpts = ['context' => 'grants_handler'];

    $settings = NULL;
    $useReactForm = FALSE;

    // phpcs:disable
    // Start by check if the react-form exists and the react-form application period is on.
    // In that case, we always render the react-application block.
    if (
      $this->moduleHandler->moduleExists('grants_application') &&
      $reactFormId = $this->servicePageBlockService->getReactFormId()
    ) {
      try {
        // @phpstan-ignore-next-line
        $formSettingsService = \Drupal::service('Drupal\grants_application\Form\FormSettingsService');
        $settings = $formSettingsService->getFormSettings($reactFormId);
      }
      catch (\Exception $e) {
        // If there are no settings, just use the webform.
        $useReactForm = FALSE;
      }

      if ($settings && $settings->isApplicationOpen()) {
        $useReactForm = TRUE;
      }
    }
    // phpcs:enable

    // Block default values.
    $build = [];
    $build['content'] = [
      '#theme' => 'grants_service_page_block',
      '#text' => $this->t('This application is not open', [], $tOpts),
      '#auth' => 'not_open',
    ];
    $description = $this->t('Please familiarize yourself with the instructions
  on this page before proceeding to the application.', [], $tOpts);
    $build['#attached']['library'][] = 'grants_handler/servicepage-prevent-multiple-applications';

    // React is always rendered if the settings are set correctly
    // (application period etc).
    if ($useReactForm) {
      // @todo React-production: Fix the button text from template.
      // @todo Fix the \Drupal calls above.
      $build['content'] = [
        '#auth' => 'auth',
        '#text' => $description,
        '#reactFormLink' => $this->servicePageBlockService->getReactFormLink(),
        '#theme' => 'grants_service_page_block',
      ];
    }
    else {
      // Load the webform. No need to check if it exists since this
      // has already been done in the access checks.
      $webform = $this->servicePageBlockService->loadServicePageWebform();
      $webformId = $webform->id();

      // If the application isn't open, just display a message.
      if (!$this->applicationStatusService->isApplicationOpen($webform)) {
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
      $webformLink = Url::fromRoute('grants_webform_print.print_webform', ['webform' => $webformId]);

      $build['content'] = [
        '#auth' => 'auth',
        '#link' => $applicationLink,
        '#text' => $description,
        '#theme' => 'grants_service_page_block',
        '#webformLink' => $webformLink,
      ];
    }

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
  public function getCacheMaxAge(): int {
    return 600;
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
   * The checkFormAccess function.
   *
   * This function checks if the current user has access
   * to the Webform on the current service page.
   *
   * @return bool
   *   TRUE if access is granted, FALSE otherwise.
   */
  private function checkFormAccess(): bool {
    if (!$webform = $this->servicePageBlockService->loadServicePageWebform()) {
      return FALSE;
    }

    if (!$this->servicePageBlockService->isCorrectApplicantType($webform)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * The buildAsTprLink function.
   *
   * Builds the link content as LinkItem values.
   *
   * @return array|bool
   *   False if nothing to show, otherwise ready to use array for LinkItem.
   */
  public function buildAsTprLink(): array|bool {
    $tOpts = ['context' => 'grants_handler'];

    if ($this->currentUser->isAnonymous()) {
      return FALSE;
    }

    $roles = $this->currentUser->getRoles();
    if (!in_array('helsinkiprofiili', $roles)) {
      return FALSE;
    }

    if (!$this->checkFormAccess()) {
      return FALSE;
    }

    // Create link for new application.
    $node = $this->routeMatch->getParameter('node');
    $webformId = $node->get('field_webform')->target_id;

    $link = Url::fromRoute('grants_handler.new_application', ['webform_id' => $webformId], ['absolute' => TRUE]);

    return [
      'title' => $this->t('New application', [], $tOpts),
      'uri' => $link->toString(),
      'options' => [],
      '_attributes' => [],
    ];
  }

}
