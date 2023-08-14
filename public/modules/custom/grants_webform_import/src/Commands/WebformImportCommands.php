<?php

namespace Drupal\grants_webform_import\Commands;

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
 * Class to import webform files into config.
 *
 * This class is based on config_import_single module in drupal.org.
 *
 * @package Drupal\grants_webform_import\Commands
 */
class WebformImportCommands extends DrushCommands {

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
   * Import webform config ignoring config_ignore.
   *
   * @command grants-tools:webform-import
   *
   * @usage grants-tools:webform-import
   *
   * @aliases gwi
   *
   * @throws \Exception
   */
  public function webformImport() {
    $directory = Settings::get('config_sync_directory');
    $webformFiles = glob($directory . '/webform.webform.*');

    if (!$webformFiles) {
      return;
    }
    $this->import($webformFiles);
  }

  /**
   * Import given configuration files.
   *
   * @param array $files
   *   The config files to import.
   *
   * @throws \Exception
   */
  public function import(array $files) {
    $ymlFile = new Parser();
    $source_storage = new StorageReplaceDataWrapper(
      $this->storage
    );
    $processedFiles = [];
    $ignoredFiles = [];

    foreach ($files as $file) {
      $name = Path::getFilenameWithoutExtension($file);
      $value = $ymlFile->parse(file_get_contents($file));
      // Check if configuration importing is ignored.
      if ($this->formIsIgnored($name)) {
        $ignoredFiles[] = $file;
        continue;
      }
      $source_storage->replaceData($name, $value);
      $processedFiles[] = $file;
    }

    $storageComparer = new StorageComparer(
      $source_storage,
      $this->storage
    );

    if ($this->configImport($storageComparer)) {
      $processed = implode(', ', $processedFiles);
      $ignored = implode(', ', $ignoredFiles);
      $this->output()->write("Successfully imported the following files: $processed", TRUE);
      $this->output()->write("The following files were ignored: $ignored", TRUE);
      $this->importWebformTranslations();
    }
    else {
      throw new \Exception("Failed importing files");
    }
  }

  /**
   * Import the config.
   *
   * @param \Drupal\Core\Config\StorageComparer $storageComparer
   *   The storage comparer.
   *
   * @return bool|void
   *   Returns TRUE if succeeded.
   */
  private function configImport(StorageComparer $storageComparer) {
    $configImporter = new ConfigImporter(
      $storageComparer,
      $this->eventDispatcher,
      $this->configManager,
      $this->lock,
      $this->configTyped,
      $this->moduleHandler,
      $this->moduleInstaller,
      $this->themeHandler,
      $this->stringTranslation,
      $this->extensionListModule
    );

    if ($configImporter->alreadyImporting()) {
      $this->output()->writeln('Import already running.');
      return FALSE;
    }
    if ($configImporter->validate()) {
      try {
        $syncSteps = $configImporter->initialize();
        $batch = [
          'operations' => [],
          'finished' => [ConfigImporterBatch::class, 'finish'],
          'title' => $this->stringTranslation->translate('Importing configuration'),
          'init_message' => $this->stringTranslation->translate('Starting configuration import.'),
          'progress_message' => $this->stringTranslation->translate('Completed @current step of @total.'),
          'error_message' => $this->stringTranslation->translate('Configuration import has encountered an error.'),
        ];
        foreach ($syncSteps as $syncStep) {
          $batch['operations'][] = [
            [ConfigImporterBatch::class, 'process'],
            [$configImporter, $syncStep],
          ];
        }

        batch_set($batch);
        drush_backend_batch_process();

        $this->configFactory->reset();
        return TRUE;
      }
      catch (ConfigImporterException $e) {
        return FALSE;
      }
    }
  }

  /**
   * The formIsIgnored method.
   *
   * This method checks if the importing of a forms configuration should be skipped.
   * This is done by comparing the forms "applicationTypeId" value against the values
   * found under "config_import_ignore" in the "grants_metadata.settings.yml" file.
   *
   * The format of the "config_import_ignore" array should be the following:
   *
   * config_import_ignore:
   *  - 29
   *  - 48
   *  - 51
   *
   * @param string $name
   *   The name of the form configuration file.
   *
   * @return bool
   *   A boolean indicating if a forms configuration should be ignored or not.
   */
  private function formIsIgnored(string $name): bool {
    $directory = Settings::get('config_sync_directory');
    $parser = new Parser();

    $configurationSettingsFile = $directory . '/grants_metadata.settings.yml';
    $formConfigurationFile = $directory . '/' . $name . '.yml';

    $configurationSettings = $parser->parse(file_get_contents($configurationSettingsFile));
    $formConfiguration = $parser->parse(file_get_contents($formConfigurationFile));

    // Skip if we can't find the configuration settings.
    if (!$configurationSettings || !isset($configurationSettings['config_import_ignore'])) {
     return FALSE;
    }

    // Skip if the form doesn't have third party settings.
    if (!isset($formConfiguration['third_party_settings'])) {
      return FALSE;
    }

    $ignoredFormIds = $configurationSettings['config_import_ignore'];
    foreach ($ignoredFormIds as $ignoredFormId) {
      $formId = $formConfiguration['third_party_settings']['grants_metadata']['applicationTypeID'];
      if ($formId == $ignoredFormId) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * The importWebformTranslations method.
   *
   * This method imports English and Swedish Webform
   * translations from to configuration directory.
   *
   * @throws \Exception
   *   Exception on import fail.
   */
  private function importWebformTranslations() {
    $directory = Settings::get('config_sync_directory');
    $parser = new Parser();

    $webformTranslationFiles = [
      'en' => glob($directory . '/language/en/webform.webform.*'),
      'sv' => glob($directory . '/language/sv/webform.webform.*'),
    ];

    try {
      foreach ($webformTranslationFiles as $language => $files) {
        foreach ($files as $file) {
          $name = Path::getFilenameWithoutExtension($file);
          $configFileValue = $parser->parse(file_get_contents($file));

          if ($this->formIsIgnored($name)) {
            $this->output()->writeln("The following translation was skipped because of config ignore: $file");
            continue;
          }

          /** @var \Drupal\language\Config\LanguageConfigOverride $languageOverride */
          $languageOverride = \Drupal::languageManager()->getLanguageConfigOverride($language, $name);
          $languageOverrideValue = $languageOverride->get();

          if ($configFileValue && $languageOverrideValue) {
            $languageOverride->setData($configFileValue);
            $languageOverride->save();
            $this->output()->writeln("Successfully imported the following translation: $file");
          }
        }
      }
    }
    catch (\Exception $e) {
      throw new \Exception("Failed importing translations.");
    }
  }

}
