<?php

namespace Drupal\grants_oma_asiointi\Plugin\ParagraphHandler;

use Drupal\paragraph_handler\Plugin\ParagraphHandlerBase;
use Drupal\Core\Url;
use Drupal\grants_profile\GrantsProfileService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Class OmaAsiointiParagraph.
 *
 * @ParagraphHandler(
 *   id = "paragraph__oma_asiointi",
 *   label = @Translation("OmaAsiointiParagraph")
 * )
 */
class OmaAsiointiParagraph extends ParagraphHandlerBase implements ContainerFactoryPluginInterface {

  /**
   * The grants_profile.service service.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

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
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    GrantsProfileService $grants_profile_service
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->grantsProfileService = $grants_profile_service;
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
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $selectedCompany = $this->grantsProfileService->getSelectedCompany();

    $build = [
      '#theme' => 'paragraph__oma_asiointi',
      '#company' => $selectedCompany,
    ];

    return $build;
  }
}
