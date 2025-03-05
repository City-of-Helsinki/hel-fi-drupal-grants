<?php

declare(strict_types=1);

namespace Drupal\grants_application\Form;

use Drupal\Core\KeyValueStore\KeyValueDatabaseFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Form settings class.
 */
final class ApplicationNumberService {

  public function __construct(
    #[Autowire(service: 'keyvalue.database')]
    private readonly KeyValueDatabaseFactory $keyValue,
  ) {
  }

  /**
   * Create application number.
   *
   * @param string $env
   *   Current environment.
   * @param int $application_type_id
   *   The application type id.
   *
   * @return string
   *   The application number.
   */
  public function createNewApplicationNumber(string $env, int $application_type_id): string {
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $store */
    $store = $this->keyValue->get('application_numbers');

    $last_serial_key = "{$application_type_id}_{$env}";

    $savedSerial = $store->get($last_serial_key, 0);

    $newSerial = $savedSerial + 1;

    $application_number = self::getApplicationNumberInEnvFormat($env, (string) $application_type_id, (string) $newSerial);

    $store->set($last_serial_key, $newSerial);

    return $application_number;
  }

  /**
   * Create an application number in env format.
   */
  private static function getApplicationNumberInEnvFormat(string $env, string $type_id, string $serial): string {
    $application_number = $env . '-' .
      str_pad($type_id, 3, '0', STR_PAD_LEFT) . '-' .
      str_pad($serial, 7, '0', STR_PAD_LEFT);

    if ($env == 'PROD') {
      $application_number = str_pad($type_id, 3, '0', STR_PAD_LEFT) . '-' .
        str_pad($serial, 7, '0', STR_PAD_LEFT);
    }

    return $application_number;
  }

}
