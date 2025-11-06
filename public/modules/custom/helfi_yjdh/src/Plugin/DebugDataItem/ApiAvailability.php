<?php

declare(strict_types=1);

namespace Drupal\helfi_yjdh\Plugin\DebugDataItem;

use Drupal\Core\DependencyInjection\AutowiredInstanceTrait;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\helfi_api_base\Attribute\DebugDataItem;
use Drupal\helfi_api_base\Debug\SupportsValidityChecksInterface;
use Drupal\helfi_api_base\DebugDataItemPluginBase;
use Drupal\helfi_yjdh\YjdhClient;

/**
 * Debug data client for YJDH API connection.
 *
 * This is used to ensure the current instance has access to the YJDH API.
 */
#[DebugDataItem(
  id: 'yjdh',
  title: new TranslatableMarkup('YJDH'),
)]
final class ApiAvailability extends DebugDataItemPluginBase implements ContainerFactoryPluginInterface, SupportsValidityChecksInterface {

  use AutowiredInstanceTrait;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly YjdhClient $yjdhClient,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function check(): bool {
    // Business ID is for "Helsingin kaupunki".
    $businessId = '0201256-6';
    $company = $this->yjdhClient->getCompany($businessId);

    return !empty($company['BusinessId']);
  }

}
