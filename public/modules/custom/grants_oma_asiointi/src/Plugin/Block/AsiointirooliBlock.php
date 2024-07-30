<?php

namespace Drupal\grants_oma_asiointi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\grants_profile\GrantsProfileService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "grants_oma_asiointi_asiointirooli_block",
 *   admin_label = @Translation("Grants Oma Asiointi Asiointirooli"),
 *   category = @Translation("Oma Asiointi")
 * )
 *
 * @phpstan-consistent-constructor
 */
class AsiointirooliBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Construct block object.
   *
   * @param array $configuration
   *   Block config.
   * @param string $plugin_id
   *   Plugin.
   * @param mixed $plugin_definition
   *   Plugin def.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   The grants profile service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected GrantsProfileService $grantsProfileService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    $plugin_definition,
  ): static {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('grants_profile.service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    $companyName = NULL;
    $currentRole = NULL;

    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();

    if ($selectedCompany) {
      $companyName = $selectedCompany['name'];
      $currentRole = $selectedCompany['type'];
    }

    $switchRole = Link::createFromRoute($this->t('Switch role', [], [
      'context' => 'Asiointirooli block',
    ]), 'grants_mandate.mandateform', [],
    [
      'attributes' => [
        'class' => ['link--switch-role'],
      ],
    ]);
    $asiointiLink = '';
    if ($companyName) {
      $asiointiLink = Link::createFromRoute($companyName, 'grants_profile.show');
    }

    $build = [
      '#theme' => 'grants_oma_asiointi_asiointirooli_block',
      '#switchRole' => $switchRole,
      '#currentRole' => $currentRole,
      '#asiointiLink' => $asiointiLink,
    ];

    return $build;
  }

  /**
   * Disable cache.
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

}
