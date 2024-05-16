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
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\node\Entity\Node;
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
   */
  public function __construct(
    array $configuration,
                             $pluginId,
                             $pluginDefinition,
    HelsinkiProfiiliUserData $helfiHelsinkiProfiili,
    CurrentRouteMatch $routeMatch,
    AccountProxy $user,
    GrantsProfileService $grantsProfileService,
    EntityTypeManager $entityTypeManager
  ) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->helfiHelsinkiProfiili = $helfiHelsinkiProfiili;
    $this->routeMatch = $routeMatch;
    $this->currentUser = $user;
    $this->grantsProfileService = $grantsProfileService;
    $this->entityTypeManager = $entityTypeManager;
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
      $container->get('entity_type.manager')
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
    $access = FALSE;

    try {
      $access = $this->checkFormAccess();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
    }

    return AccessResult::allowedIf($access);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $tOpts = ['context' => 'grants_handler'];

    $node = $this->routeMatch->getParameter('node');

    $webformId = $node->get('field_webform')->target_id;

    // No webform reference, no need for this block.
    if (!$webformId) {
      return [];
    }
    // Create link for new application.
    $applicationUrl = Url::fromRoute(
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
    $applicationText = [
      '#theme' => 'edit-label-with-icon',
      '#icon' => 'document',
      '#text_label' => $this->t('New application', [], $tOpts),
    ];

    $link = Link::fromTextAndUrl($applicationText, $applicationUrl);

    $text = $this->t('Please familiarize yourself with the instructions on this page before proceeding to the application.', [], $tOpts);

    $node = $this->routeMatch->getParameter('node');
    $webformArray = $node->get('field_webform')->getValue();

    if ($webformArray) {
      $webformName = $webformArray[0]['target_id'];

      $webformLink = Url::fromRoute('grants_webform_print.print_webform',
        [
          'webform' => $webformName,
        ]);
    }
    else {
      $webformLink = NULL;
    }

    $build['content'] = [
      '#theme' => 'grants_service_page_block',
      '#link' => $link,
      '#text' => $text,
      '#auth' => 'auth',
      '#webformLink' => $webformLink,
    ];

    if (!$this->isApplicationOpen($node)) {
      $build['content'] = [
        '#theme' => 'grants_service_page_block',
        '#text' =>  $this->t('This application is not open', [], $tOpts),
        '#auth' => 'not_open',
      ];
    }

    $build['#cache']['contexts'] = [
      'languages:language_content',
      'url.path',
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

  /**
   * The checkFormAccess function.
   *
   * This function checks whether the block should be displayed or not.
   * The block is NOT displayed if:
   *
   * 1. We are NOT on a node.
   * 2. The node has NOT referenced a Webform.
   * 3. A Webform can NOT be found with the referenced ID.
   * 4. The user has NOT selected a role.
   * 4. The user does NOT have an allowed role for the form.
   *
   * @return bool
   *   Boolean value telling if user can see the new application button.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function checkFormAccess(): bool {
    $node = $this->routeMatch->getParameter('node');
    if (!$node) {
      return FALSE;
    }

    $webformId = $node->get('field_webform')->target_id;
    if (!$webformId) {
      return FALSE;
    }

    $webform = $this->entityTypeManager->getStorage('webform')->load($webformId);
    if (!$webform) {
      return FALSE;
    }

    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    if (!$selectedCompany) {
      return FALSE;
    }

    $thirdPartySettings = $webform->getThirdPartySettings('grants_metadata');
    $applicantTypes = $this->normalizeApplicantTypes($thirdPartySettings['applicantTypes']);
    if (!in_array($selectedCompany['type'], $applicantTypes)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * The isApplicationOpen function.
   *
   * Determines if the application is currently open
   * based on timing and continuous availability.
   *
   * @param \Drupal\node\Entity\Node $node
   *   The node entity.
   * @return bool
   *   True if the application is open. Otherwise, false.
   */
  private function isApplicationOpen(Node $node): bool {
    $continuous = (bool) $node->get('field_application_continuous')->value;

    if ($continuous) {
      return TRUE;
    }

    $periodStart = new Carbon($node->get('field_application_period')->value);
    $periodEnd = new Carbon($node->get('field_application_period')->end_value);
    $now = new Carbon();

    return $now->between($periodStart, $periodEnd);
  }

  /**
   * The normalizeApplicantTypes function.
   *
   * Normalizes applicant types to ensure compatibility
   * with single and multiple type settings.
   *
   * @param mixed $applicantTypes
   *   The applicant types from third-party settings, may be an array or a single value.
   * @return array
   *   An array of applicant types.
   */
  private function normalizeApplicantTypes(mixed $applicantTypes): array {
    if (!is_array($applicantTypes)) {
      return [$applicantTypes];
    }
    return array_values($applicantTypes);
  }

}
