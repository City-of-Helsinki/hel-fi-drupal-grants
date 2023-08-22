<?php

namespace Drupal\grants_webform_import\Commands;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\grants_handler\ApplicationHandler;
use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Parser;
use Webmozart\PathUtil\Path;

/**
 * Class to import overridden Webform configurations.
 *
 * @package Drupal\grants_webform_import\Commands
 */
class WebformConfigOverrideCommands extends DrushCommands {

  /**
   * The allowed environments for running the command.
   */
  const ALLOWED_ENVIRONMENTS = ['DEV', 'TEST', 'STAGE'];

  /**
   * The ConfigFactoryInterface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The ConfigFactoryInterface.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    parent::__construct();
    $this->configFactory = $configFactory;
  }

  /**
   * Import overridden Webform configurations.
   *
   * @command grants-tools:webform-config-override
   *
   * @usage grants-tools:webform-config-override
   *
   * @aliases gwco
   */
  public function webformConfigOverride() {
    $overrides = $this->getOverrides();
    $mapping = $this->getApplicationTypeIdMapping();

    if (!$overrides) {
      $this->output()
        ->writeln("No overrides were found. Aborting.");
      return;
    }

    if (!$mapping) {
      $this->output()
        ->writeln("Application type ID -> Machine name mapping could not be established. Aborting.");
      return;
    }

    if (!$this->isEnvironmentAllowed()) {
      $this->output()
        ->writeln("Command not allowed in your environment. Aborting.");
      return;
    }

    $this->override($overrides, $mapping);
  }

  /**
   * Private method to figure config override is allowed.
   *
   * This will look into current app env as returned from
   * ApplicatioHandler and disallows config overrides in production.
   *
   * @return bool
   *   True if override is allowed.
   */
  private function isEnvironmentAllowed(): bool {
    // Get current env from handler method.
    $appEnv = ApplicationHandler::getAppEnv();

    // If current env is in allowed, return true.
    if (in_array($appEnv, self::ALLOWED_ENVIRONMENTS)) {
      return TRUE;
    }
    // Because of the different local envs we need to check explicitly for
    // local envs.
    if (str_starts_with($appEnv, 'LOCAL')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * The override method.
   *
   * This method performs the overriding of Webform
   * configurations. The overrides are only
   * performed on the information in the DB, and not on
   * the actual configuration files.
   *
   * @param array $overrides
   *   The overrides we are implementing.
   * @param array $mapping
   *   An "Application type id" -> "Machine name" map.
   */
  private function override(array $overrides, array $mapping): void {
    foreach ($overrides as $override) {
      $applicationTypeId = key($override);
      $configurationOverrides = $override[$applicationTypeId]['grants_metadata'];
      $configurationName = $mapping[$applicationTypeId];

      $config = $this->configFactory->getEditable($configurationName);
      $originalConfiguration = $config->get('third_party_settings.grants_metadata');

      if ($configurationOverrides && $originalConfiguration) {
        $overriddenConfiguration = array_merge($originalConfiguration, $configurationOverrides);
        $config->set('third_party_settings.grants_metadata', $overriddenConfiguration);
        $config->save();
        $this->logMessage($configurationName,
                          $applicationTypeId,
                          $configurationOverrides,
                          $originalConfiguration,
                          $overriddenConfiguration);
        $this->updateServicePages($configurationName);
        continue;
      }
      $this->output()->writeln("Error importing configuration overrides for $applicationTypeId.\n");
    }
  }

  /**
   * The getOverrides method.
   *
   * This method gets the Webform configuration overrides from
   * the "grants_metadata.settings.yml" file. The overrides should
   * be located under the "overridden_configuration" key.
   * The overrides presented in the file should have a structure that matches
   * the structure in the forms own configuration file.
   *
   * Example:
   *
   * overridden_configuration:
   *  - 53:
   *      grants_metadata:
   *        applicationOpen: '2024-06-16T08:30:52'
   *        applicationClose: '2025-08-19T08:36:07'
   *        disableCopying: 1
   *  - 47:
   *      grants_metadata:
   *        applicationOpen: '2024-06-16T08:30:52'
   *        applicationClose: '2025-08-19T08:36:07'
   *        disableCopying: 1
   *
   * @return false|mixed
   *   Returns either the configuration overrides or FALSE.
   */
  private function getOverrides(): mixed {
    $parser = new Parser();
    $directory = Settings::get('config_sync_directory');

    $configurationYamlFile = $directory . '/grants_metadata.settings.yml';
    $configurationSettings = $parser->parse(file_get_contents($configurationYamlFile));

    // False if we can't find the overridden configuration settings.
    if (!$configurationSettings || !isset($configurationSettings['overridden_configuration'])) {
      return FALSE;
    }
    return $configurationSettings['overridden_configuration'];
  }

  /**
   * The getApplicationTypeIdMapping method.
   *
   * This method builds a map between Webform application
   * type IDs and their corresponding machine names. If multiple
   * Webforms with the same application type ID exist, then only
   * the "newest" is included in the map. The final map is
   * structured like this:
   *
   * [
   *  53 => "webform.webform.kasko_ip_lisa",
   *  51 => "webform.webform.kasvatus_ja_koulutus_yleisavustu",
   *  48 => "webform.webform.kuva_projekti",
   *  47 => "webform.webform.kuva_toiminta"
   * ]
   *
   * @return array
   *   An array containing an "Application type id" -> "Machine name"
   *   map.
   */
  private function getApplicationTypeIdMapping(): array {
    $parser = new Parser();
    $configurationDirectory = Settings::get('config_sync_directory');
    $webformConfigurationFiles = glob($configurationDirectory . '/webform.webform.*');
    $mapping = [];

    /*
     * Start by building a data structure that looks like this.
     * Each item will be unique, since the machine name is always
     * unique. Duplicate "applicationTypeId" entries may exist at
     * this point.
     *
     * "webform.webform.kuva_projekti" => [
     *    "applicationTypeId" => "48"
     *    "uuid" => "099f2c14-fa1d-41fa-bdae-f26bd47f936e"
     *    "parent" => "e02b8012-bb8b-40d7-9d6b-2f6776882fe6"
     *    "name" => "webform.webform.kuva_projekti"
     *  ],
     *  ....
     */
    foreach ($webformConfigurationFiles as $file) {
      $formConfiguration = $parser->parse(file_get_contents($file));

      // If "grants_metadata" does not exist, continue.
      if (!isset($formConfiguration['third_party_settings']['grants_metadata'])) {
        continue;
      }

      // Collect variables and add to array.
      $name = Path::getFilenameWithoutExtension($file);
      $uuid = $formConfiguration['uuid'];
      $grantsMetadata = $formConfiguration['third_party_settings']['grants_metadata'];
      $applicationTypeID = $grantsMetadata['applicationTypeID'];
      $parent = $grantsMetadata['parent'] ?? NULL;

      if ($name && $applicationTypeID && $uuid) {
        $mapping[$name] = [
          'applicationTypeId' => $applicationTypeID,
          'uuid' => $uuid,
          'parent' => $parent,
          'name' => $name,
        ];
      }
    }

    // Get an array of all the items with a "parent".
    $itemsWithParent = array_filter($mapping, function ($item) {
      return isset($item['parent']);
    });

    // Get the parent UUIDs.
    $parentUuids = array_column($itemsWithParent, 'parent');

    // Filter out all the items that are set as another items parent.
    // This way only the "newest" version of a form will be included.
    $mapping = array_filter($mapping, function ($item) use ($parentUuids) {
      return !in_array($item['uuid'], $parentUuids);
    });

    // Build the desired output and return.
    return array_reduce($mapping, function ($output, $item) {
      $output[$item['applicationTypeId']] = $item['name'];
      return $output;
    });
  }

  /**
   * The updateServicePages method.
   *
   * This method updates service page nodes with the newly
   * imported configuration overrides. The process is initialized
   * by calling "grants_metadata_webform_presave" with a Webform.
   *
   * @param string $configurationName
   *   The filename of a Webforms configuration.
   */
  private function updateServicePages(string $configurationName): void {
    try {
      $parts = explode('.', $configurationName);
      $webformMachineName = array_pop($parts);
      $webform = \Drupal::entityTypeManager()->getStorage('webform')->load($webformMachineName);
      grants_metadata_webform_presave($webform);
    }
    catch (PluginNotFoundException | InvalidPluginDefinitionException $e) {
      $this->output()->writeln("Error saving Webform.\n");
    }
  }

  /**
   * The logMessage method.
   *
   * This method logs messages when an import has been completed.
   *
   * @param string $configurationName
   *   The name of the configuration.
   * @param string $applicationTypeId
   *   The forms application type ID.
   * @param array $configurationOverrides
   *   The configuration overrides.
   * @param array $originalConfiguration
   *   The forms original configuration.
   * @param array $overriddenConfiguration
   *   The forms new configuration.
   */
  private function logMessage(string $configurationName,
                              string $applicationTypeId,
                              array $configurationOverrides,
                              array $originalConfiguration,
                              array $overriddenConfiguration): void {
    $this->output()->writeln("Importing configuration for $configurationName ($applicationTypeId):\n");
    $this->output()->writeln("ORIGINAL CONFIGURATION:");
    $this->output()->writeln(print_r($originalConfiguration, TRUE));
    $this->output()->writeln("OVERRIDES:");
    $this->output()->writeln(print_r($configurationOverrides, TRUE));
    $this->output()->writeln("NEW CONFIGURATION:");
    $this->output()->writeln(print_r($overriddenConfiguration, TRUE));
    $this->output()->writeln("=========================================================================\n");
  }

}
