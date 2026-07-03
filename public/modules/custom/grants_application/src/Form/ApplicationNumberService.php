<?php

declare(strict_types=1);

namespace Drupal\grants_application\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\KeyValueStore\KeyValueDatabaseFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Form settings class.
 */
class ApplicationNumberService {

  public function __construct(
    #[Autowire(service: 'keyvalue.database')]
    private readonly KeyValueDatabaseFactory $keyValue,
    private readonly Connection $connection
  ) {
  }

  /**
   * Create application number.
   *
   * @param string $env
   *   Current environment.
   * @param int|string $application_type_id
   *   The application type id.
   *
   * @return string
   *   The application number.
   */
  public function createNewApplicationNumber(string $env, int|string $application_type_id): string {
    /** @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface $store */
    $store = $this->keyValue->get('application_numbers');

    $last_serial_key = "{$application_type_id}_{$env}";

    $savedSerial = $store->get($last_serial_key, 0);

    if ($savedSerial < 1000 && $env === 'production') {
      $savedSerial += 1000;
    }
    else if ($savedSerial < 1000 && $env !== 'production') {
      // For local development, check database for already saved applications.
      $like = $application_type_id < 100 ? "$env-0$application_type_id-%" : "$env-$application_type_id-%";
      $numberArray = $this->connection
        ->query("select application_number from application_submission where application_number like '$like' ")
        ->fetchAll();

      if (!$numberArray || !is_array($numberArray)) {
        $savedSerial += 1000;
      }

      $numbers = array_map(
        function ($num) {
          return (int) explode('-', $num->application_number)[2];
        },
        $numberArray
      );
      $max = max($numbers);

      if ($max && $max > $savedSerial) {
        $savedSerial = $max;
      }
    }
    else {
      $savedSerial += 1000;
    }

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
