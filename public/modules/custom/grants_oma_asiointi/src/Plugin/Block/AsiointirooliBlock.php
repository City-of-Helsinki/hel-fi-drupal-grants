<?php

declare(strict_types=1);

namespace Drupal\grants_oma_asiointi\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\grants_profile\GrantsProfileService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an example block.
 *
 * @phpstan-consistent-constructor
 */
#[Block(
  id: 'grants_oma_asiointi_asiointirooli_block',
  admin_label: new TranslatableMarkup('Grants Oma Asiointi Asiointirooli'),
)]
final class AsiointirooliBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The grants profile service.
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->grantsProfileService = $container->get('grants_profile.service');
    return $instance;
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
