<?php

namespace Drupal\grants_webform_import\Commands;

use Drupal\Component\Utility\NestedArray;
use Drupal\config\StorageReplaceDataWrapper;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterException;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Importer\ConfigImporterBatch;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Lock\LockBackendInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Parser;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Webmozart\PathUtil\Path;

/**
 * Class to import overridden Webform configurations.
 *
 * This class is based on config_import_single module in drupal.org.
 *
 * @package Drupal\grants_webform_import\Commands
 */
class WebformConfigOverrideCommands extends DrushCommands {

  /**
   * CachedStorage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  private $storage;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * Config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  private $configManager;

  /**
   * Lock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  private $lock;

  /**
   * Config typed.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  private $configTyped;

  /**
   * ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  private $moduleInstaller;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  private $themeHandler;

  /**
   * String translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  private $stringTranslation;

  /**
   * Extension list module.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private $extensionListModule;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * ConfigImportSingleCommands constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   Storage.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   Event Dispatcher.
   * @param \Drupal\Core\Config\ConfigManagerInterface $configManager
   *   Config Manager.
   * @param \Drupal\Core\Lock\LockBackendInterface $lock
   *   Lock.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $configTyped
   *   Config typed.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $moduleInstaller
   *   Module Installer.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   Theme handler.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   String Translation.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionListModule
   *   Extension list module.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(StorageInterface $storage, EventDispatcherInterface $eventDispatcher, ConfigManagerInterface $configManager, LockBackendInterface $lock, TypedConfigManagerInterface $configTyped, ModuleHandlerInterface $moduleHandler, ModuleInstallerInterface $moduleInstaller, ThemeHandlerInterface $themeHandler, TranslationInterface $stringTranslation, ModuleExtensionList $extensionListModule, ConfigFactoryInterface $configFactory) {
    parent::__construct();
    $this->storage = $storage;
    $this->eventDispatcher = $eventDispatcher;
    $this->configManager = $configManager;
    $this->lock = $lock;
    $this->configTyped = $configTyped;
    $this->moduleHandler = $moduleHandler;
    $this->moduleInstaller = $moduleInstaller;
    $this->themeHandler = $themeHandler;
    $this->stringTranslation = $stringTranslation;
    $this->extensionListModule = $extensionListModule;
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
    $overrides = $this->loadOverrides();

    if (!$overrides) {
      $this->output()->writeln("No overrides were found. Aborting.");
      return;
    }

    $mapping = $this->getApplicationTypeIdMapping();
    $config_factory = \Drupal::configFactory();

    foreach ($overrides as $override) {
      $applicationTypeId = key($override);
      $newGrantsMetadata = reset($override[$applicationTypeId]);
      $config = $config_factory->getEditable($mapping[$applicationTypeId]);
      $originalGrantsMetadata = $config->get('third_party_settings.grants_metadata');

      if ($newGrantsMetadata && $originalGrantsMetadata) {
        $overriddenConfiguration = array_merge($originalGrantsMetadata, $newGrantsMetadata);
        $config->set('third_party_settings.grants_metadata', $overriddenConfiguration);
        $config->save();
        $this->output()->writeln("Imported overrides for $mapping[$applicationTypeId] ($applicationTypeId).");
      }
    }
  }

  /**
   * The loadOverrides method.
   *
   * @return false|mixed
   */
  private function loadOverrides(): mixed {
    $directory = Settings::get('config_sync_directory');
    $parser = new Parser();

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
   * @return array
   */
  private function getApplicationTypeIdMapping(): array {
    $parser = new Parser();
    $mapping = [];
    $configurationDirectory = Settings::get('config_sync_directory');
    $webformConfigurationFiles = glob($configurationDirectory . '/webform.webform.*');

    foreach ($webformConfigurationFiles as $file) {
      $name = Path::getFilenameWithoutExtension($file);
      $formConfiguration = $parser->parse(file_get_contents($file));

      if (!isset($formConfiguration['third_party_settings'])) {
        continue;
      }

      // Here we can implement logic regarding form versions.
      $applicationTypeID = $formConfiguration['third_party_settings']['grants_metadata']['applicationTypeID'];
      $mapping[$applicationTypeID] = $name;
    }

    return $mapping;
  }

}
