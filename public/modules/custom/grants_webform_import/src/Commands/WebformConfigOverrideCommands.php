<?php

namespace Drupal\grants_webform_import\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
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
   *
   * @throws \Exception
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

    if (getenv('APP_ENV') == 'production') {
      $this->output()
        ->writeln("Command not allowed in production. Aborting.");
      return;
    }

    $this->override($overrides, $mapping);
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

        $this->output()
          ->writeln("Imported overrides for $mapping[$applicationTypeId] ($applicationTypeId).\n");

        # Debug printing.
        # dump($configurationOverrides);
        # dump($originalConfiguration);
        # dump($overriddenConfiguration);
      }
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
   * type IDs and their corresponding machine names. The map is
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

    foreach ($webformConfigurationFiles as $file) {
      $name = Path::getFilenameWithoutExtension($file);
      $formConfiguration = $parser->parse(file_get_contents($file));

      if (!isset($formConfiguration['third_party_settings'])) {
        continue;
      }

      // TODO: Implement logic regarding form versions.
      $applicationTypeID = $formConfiguration['third_party_settings']['grants_metadata']['applicationTypeID'];
      if ($name && $applicationTypeID) {
        $mapping[$applicationTypeID] = $name;
      }
    }
    return $mapping;
  }

}
