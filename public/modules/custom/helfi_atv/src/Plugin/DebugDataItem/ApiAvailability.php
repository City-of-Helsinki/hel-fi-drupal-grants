<?php

declare(strict_types=1);

namespace Drupal\helfi_atv\Plugin\DebugDataItem;

use Drupal\Core\DependencyInjection\AutowiredInstanceTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_api_base\Attribute\DebugDataItem;
use Drupal\helfi_api_base\Debug\SupportsValidityChecksInterface;
use Drupal\helfi_api_base\DebugDataItemPluginBase;
use Drupal\helfi_atv\AtvService;

/**
 * Debug data client for ATV API connection.
 *
 * This is used to ensure the current instance has access to the ATV API.
 */
#[DebugDataItem(
  id: 'atv',
  title: new TranslatableMarkup('ATV'),
)]
final class ApiAvailability extends DebugDataItemPluginBase implements ContainerFactoryPluginInterface, SupportsValidityChecksInterface {

  use AutowiredInstanceTrait;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly AtvService $atvService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function check(): bool {
    return $this->atvService->ping();
  }

}
