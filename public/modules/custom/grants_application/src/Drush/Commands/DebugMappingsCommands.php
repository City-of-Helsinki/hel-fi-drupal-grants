<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace Drupal\grants_application\Drush\Commands;

use Consolidation\AnnotatedCommand\Attributes;
use Drupal\grants_application\Mapper\JsonMapper;
use Drupal\grants_handler\Helpers;
use Drush\Commands\DrushCommands;

/**
 * Debug commands.
 */
final class DebugMappingsCommands extends DrushCommands {

  /**
   * Test mappings.
   */
  #[Attributes\Command(name: 'grants-application:debug-mappings')]
  public function debugMappings(): int {

    if (Helpers::isProduction(Helpers::getAppEnv())) {
      $this->io()->error('This command should not be run in production environment');
      return self::EXIT_FAILURE;
    }

    // Paste the filled form JSON from session storage here.
    $data = '{}';
    // Update path to mapping, set ID and name.json.
    $mappingFilePath = __DIR__ . '/../../Mapper/Mappings/ID57/liikunta_laitosavustushakemus.json';

    // Source data contains hardcoded test data instead of real user data.
    $dataSources = json_decode(file_get_contents(__DIR__ . '/../../../tests/fixtures/reactForm/commonDatasources.json'), TRUE);
    $dataSources['form_data'] = json_decode($data, TRUE);
    $commonFieldMapping = json_decode(file_get_contents(__DIR__ . '/../../Mapper/Mappings/common/registered_community.json'), TRUE);
    $specificFormMapping = json_decode(file_get_contents($mappingFilePath), TRUE);

    $allMappings = array_merge($commonFieldMapping, $specificFormMapping);

    try {
      $mapper = new JsonMapper();
      $mapper->setMappings($allMappings);
      $mappedData = $mapper->map($dataSources);
    }
    catch (\Exception $e) {
      $this->io()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }

    $this->io()->success(json_encode($mappedData, JSON_PRETTY_PRINT));
    return self::EXIT_SUCCESS;
  }

}
