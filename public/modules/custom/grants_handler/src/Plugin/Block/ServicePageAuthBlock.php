<?php

namespace Drupal\grants_handler\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Carbon\Carbon;

/**
 * Provides a service page block.
 *
 * @Block(
 *   id = "grants_handler_service_page_auth_block",
 *   admin_label = @Translation("Service Page Auth Block"),
 *   category = @Translation("Custom")
 * )
 */
class ServicePageAuthBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The helfi_helsinki_profiili service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected $helfiHelsinkiProfiili;

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
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helfi_helsinki_profiili
   *   The helfi_helsinki_profiili service.
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, HelsinkiProfiiliUserData $helfiHelsinkiProfiili) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->helfiHelsinkiProfiili = $helfiHelsinkiProfiili;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $container->get('helfi_helsinki_profiili.userdata')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {

    $access = FALSE;

    $node = \Drupal::routeMatch()->getParameter('node');

    $applicationOpen = $node->get('field_application_open')->value;
    $applicationContinuous = (bool) $node->get('field_application_continuous')->value;
    $applicationPeriodStart = new Carbon($node->get('field_application_period')->value);
    $applicationPeriodEnd = new Carbon($node->get('field_application_period')->end_value);
    $now = new Carbon();

    $applicationOpenByTime = $now->between($applicationPeriodStart, $applicationPeriodEnd);

    $webformId = $node->get('field_webform')->target_id;

    if (!$webformId) {
      $access = FALSE;
    }

    if (($applicationOpenByTime || $applicationContinuous) && $applicationOpen == '1') {
      $access = TRUE;
    }

    return AccessResult::allowedIf($access);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $node = \Drupal::routeMatch()->getParameter('node');

    $webformId = $node->get('field_webform')->target_id;

    // No webform reference, no need for this block.
    if (!$webformId) {
      return;
    }
    // Create link for new application.
    $link = Link::createFromRoute($this->t('New application'), 'grants_handler.new_application',
      [
        'webform_id' => $webformId,
      ],
      [
        'attributes' => [
          'class' => ['hds-button', 'hds-button--primary'],
        ],
      ]);

    $build['content'] = [
      '#markup' => $link->toString(),
    ];

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    $node = \Drupal::routeMatch()->getParameter('node');
    return Cache::mergeTags(parent::getCacheTags(), $node->getCacheTags());
  }

}
