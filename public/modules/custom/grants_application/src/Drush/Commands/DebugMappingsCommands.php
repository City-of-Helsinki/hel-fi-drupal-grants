<?php
// phpcs:ignoreFile

declare(strict_types=1);

namespace Drupal\grants_application\Drush\Commands;

use Consolidation\AnnotatedCommand\Attributes;
use Drupal\grants_application\Mapper\JsonMapper;
use Drush\Commands\DrushCommands;

/**
 * Debug commands.
 */
final class DebugMappingsCommands extends DrushCommands {

  use EnvironmentRestrictionTrait;

  /**
   * Prints the document the mapper produces from the given form data.
   *
   * The applicant data (profile, company, user) comes from test fixtures, and
   * the common fields are mapped as for a registered community.
   *
   * @param string $mapping
   *   Path to the form mapping file, relative to the current directory.
   * @param string $data
   *   The filled form data as JSON, e.g. copied from session storage.
   * @param array<string, mixed> $options
   *   The command options.
   *
   * @return int
   *   The exit code.
   */
  #[Attributes\Command(name: 'grants-application:debug-mappings', aliases: ['gadm'])]
  #[Attributes\Argument(name: 'mapping', description: 'Path to the form mapping file, relative to the current directory.')]
  #[Attributes\Argument(name: 'data', description: 'The filled form data as JSON.')]
  #[Attributes\Option(name: 'only', description: 'Print only the part of the document at this dot-separated path.')]
  #[Attributes\Usage(name: 'drush grants-application:debug-mappings public/modules/custom/grants_application/src/Mapper/Mappings/ID70/liikuntaharrastamisen_avustus.json \'{"grant_info_step":{...}}\' --only=compensation.customQuestionsInfo', description: 'Map the given form data and print only the custom questions.')]
  #[Attributes\Usage(name: 'drush grants-application:debug-mappings public/modules/custom/grants_application/src/Mapper/Mappings/ID70/liikuntaharrastamisen_avustus.json "$(cat form-data.json)"', description: 'Map form data saved in a file.')]
  public function debugMappings(string $mapping, string $data = '{}', array $options = ['only' => NULL]): int {
    if (!$this->isEnvironmentAllowed()) {
      $this->io()->error('This command should not be run in production environment');
      return self::EXIT_FAILURE;
    }

    $mappingFilePath = str_starts_with($mapping, '/') ? $mapping : $this->getConfig()->cwd() . '/' . $mapping;
    if (!is_file($mappingFilePath)) {
      $this->io()->error("Mapping file not found: $mappingFilePath");
      return self::EXIT_FAILURE;
    }

    try {
      $formData = json_decode($data, TRUE, flags: JSON_THROW_ON_ERROR);
      $specificFormMapping = json_decode(file_get_contents($mappingFilePath), TRUE, flags: JSON_THROW_ON_ERROR);
    }
    catch (\JsonException $e) {
      $this->io()->error('Invalid JSON: ' . $e->getMessage());
      return self::EXIT_FAILURE;
    }

    // Source data contains hardcoded test data instead of real user data.
    $dataSources = json_decode(file_get_contents(__DIR__ . '/../../../tests/fixtures/reactForm/commonDatasources.json'), TRUE);
    $dataSources['form_data'] = $formData;
    $commonFieldMapping = json_decode(file_get_contents(__DIR__ . '/../../Mapper/Mappings/common/registered_community.json'), TRUE);

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

    if ($options['only']) {
      foreach (explode('.', $options['only']) as $key) {
        if (!is_array($mappedData) || !array_key_exists($key, $mappedData)) {
          // Unselected and empty fields are left out of the document.
          $this->io()->note("Nothing at {$options['only']} in the mapped document.");
          return self::EXIT_SUCCESS;
        }
        $mappedData = $mappedData[$key];
      }
    }

    $this->output()->writeln(json_encode($mappedData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    return self::EXIT_SUCCESS;
  }

}
