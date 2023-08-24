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
  private StorageInterface $storage;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  private EventDispatcherInterface $eventDispatcher;

  /**
   * Config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  private ConfigManagerInterface $configManager;

  /**
   * Lock.
   *
   * @var \Drupal\Core\Lock\LockBackendInterface
   */
  private LockBackendInterface $lock;

  /**
   * Config typed.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  private TypedConfigManagerInterface $configTyped;

  /**
   * ModuleHandler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * Module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  private ModuleInstallerInterface $moduleInstaller;

  /**
   * Theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  private ThemeHandlerInterface $themeHandler;

  /**
   * String translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  private TranslationInterface $stringTranslation;

  /**
   * Extension list module.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  private ModuleExtensionList $extensionListModule;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * The force flag.
   *
   * An option boolean indicating if forms should be imported
   * even if they are ignored in "grants_metadata.settings.yml".
   *
   * @var bool
   */
  private bool $force;

  /**
   * The application type ID.
   *
   * A forms application type ID that can be passed in as a
   * parameter to the drush command. Passing in a form ID will
   * only import said forms configuration.
   *
   * @var string|bool
   */
  private string|bool $applicationTypeID;

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
  public function __construct(
    StorageInterface $storage,
    EventDispatcherInterface $eventDispatcher,
    ConfigManagerInterface $configManager,
    LockBackendInterface $lock,
    TypedConfigManagerInterface $configTyped,
    ModuleHandlerInterface $moduleHandler,
    ModuleInstallerInterface $moduleInstaller,
    ThemeHandlerInterface $themeHandler,
    TranslationInterface $stringTranslation,
    ModuleExtensionList $extensionListModule,
    ConfigFactoryInterface $configFactory
  ) {
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
   * @param string|false $applicationTypeID
   *   A singular (numeric) form ID. The configuration for only this form
   *   will be imported.
   * @param false[] $options
   *   An array of options provided to the command.
   *
   * @command grants-tools:webform-import
   *
   * @option force
   *   Force importing configurations, even if they are ignored.
   *
   * @usage grants-tools:webform-import
   *
   * @aliases gwi, gwi --force, gwi 49, gwi 49 --force
   */
  public function webformImport(mixed $applicationTypeID = FALSE, array $options = ['force' => FALSE]) {
    $directory = Settings::get('config_sync_directory');
    $webformFiles = glob($directory . '/webform.webform.*');
    $this->force = $options['force'];
    $this->applicationTypeID = $applicationTypeID;

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
   * @throws \Drupal\Core\Config\ConfigImporterException
   *   Exception on ConfigImporterException.
   */
  public function import(array $files) {
    $parser = new Parser();
    $processedFiles = [];
    $sourceStorage = new StorageReplaceDataWrapper(
      $this->storage
    );

    foreach ($files as $file) {
      $name = Path::getFilenameWithoutExtension($file);
      $value = $parser->parse(file_get_contents($file));

      // Check if a singular form ID has been requested.
      if ($this->applicationTypeID && !$this->formMatchesRequestedId($name)) {
        $this->output()
          ->writeln("File skipped because of mismatching application type ID: $file");
        continue;
      }

      // Check if configuration importing is ignored.
      if (!$this->force && $this->formIsConfigIgnored($name)) {
        $this->output()
          ->writeln("File skipped because of config ignore: $file");
        continue;
      }

      $processedFiles[] = $file;
      $sourceStorage->replaceData($name, $value);
    }

    $storageComparer = new StorageComparer(
      $sourceStorage,
      $this->storage
    );

    if ($this->configImport($storageComparer)) {
      foreach ($processedFiles as $file) {
        $this->output()->writeln("Successfully imported file: $file");
      }
      $this->importWebformTranslations();
    }
    else {
      throw new ConfigImporterException("Failed importing files");
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
   * The importWebformTranslations method.
   *
   * This method imports English and Swedish Webform
   * translations from to configuration directory.
   */
  private function importWebformTranslations() {
    $directory = Settings::get('config_sync_directory');
    $parser = new Parser();

    $webformTranslationFiles = [
      'en' => glob($directory . '/language/en/webform.webform.*'),
      'sv' => glob($directory . '/language/sv/webform.webform.*'),
    ];

    foreach ($webformTranslationFiles as $language => $files) {

      foreach ($files as $file) {
        $name = Path::getFilenameWithoutExtension($file);
        $configFileValue = $parser->parse(file_get_contents($file));

        // Check that we have config values.
        if (!$configFileValue) {
          $this->output()->writeln("Configuration not found.");
          continue;
        }

        // Check if a singular form ID has been requested.
        if ($this->applicationTypeID && !$this->formMatchesRequestedId($name)) {
          $this->output()
            ->writeln("Translation skipped because of mismatching application type ID: $file");
          continue;
        }

        // Check if configuration importing is ignored.
        if (!$this->force && $this->formIsConfigIgnored($name)) {
          $this->output()
            ->writeln("Translation skipped because of config ignore: $file");
          continue;
        }

        /** @var \Drupal\language\Config\LanguageConfigOverride $languageOverride */
        $languageOverride = \Drupal::languageManager()->getLanguageConfigOverride($language, $name);
        $languageOverride->setData($configFileValue);
        $languageOverride->save();
        $this->output()
          ->writeln("Successfully imported translation: $file");
      }
    }
  }

  /**
   * The formIsConfigIgnored method.
   *
   * This method checks if the importing of a forms configuration should be
   * skipped. This is done by comparing the forms "applicationTypeId" value
   * against the values found under "config_import_ignore" in the
   * "grants_metadata.settings.yml" file.
   *
   * The format of the "config_import_ignore" array should be the following:
   *
   * config_import_ignore:
   *  - 29
   *  - 48
   *  - 51
   *
   * @param string $name
   *   The name of the form configuration (yaml) file.
   *
   * @return bool
   *   A boolean indicating if a forms configuration should be ignored or not.
   */
  private function formIsConfigIgnored(string $name): bool {
    $directory = Settings::get('config_sync_directory');
    $parser = new Parser();

    $configurationYamlFile = $directory . '/grants_metadata.settings.yml';
    $formYamlFile = $directory . '/' . $name . '.yml';

    $configurationSettings = $parser->parse(file_get_contents($configurationYamlFile));
    $formConfiguration = $parser->parse(file_get_contents($formYamlFile));

    // False if we can't find the configuration settings or
    // if the form doesn't have third party settings.
    if (!$configurationSettings ||
        !isset($configurationSettings['config_import_ignore']) ||
        !$formConfiguration ||
        !isset($formConfiguration['third_party_settings'])) {
      return FALSE;
    }

    // True only if the form is ignored in "config_import_ignore".
    $ignoredFormIds = $configurationSettings['config_import_ignore'];
    $applicationTypeID = $formConfiguration['third_party_settings']['grants_metadata']['applicationTypeID'];
    if (in_array($applicationTypeID, $ignoredFormIds)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * The formMatchesRequestedId method.
   *
   * This method compares the "$applicationTypeID" drush parameter against
   * the forms own applicationTypeID.
   *
   * @param string $name
   *   The name of the form configuration (yaml) file.
   *
   * @return bool
   *   A boolean indicating if the parameter and the forms own
   *   applicationTypeID are a match or not.
   */
  private function formMatchesRequestedId(string $name): bool {
    $directory = Settings::get('config_sync_directory');
    $parser = new Parser();

    $formYamlFile = $directory . '/' . $name . '.yml';
    $formConfiguration = $parser->parse(file_get_contents($formYamlFile));

    // False if the form doesn't have third party settings.
    if (!isset($formConfiguration['third_party_settings'])) {
      return FALSE;
    }

    // True only if the IDs match.
    $applicationTypeID = $formConfiguration['third_party_settings']['grants_metadata']['applicationTypeID'];
    if ($this->applicationTypeID == $applicationTypeID) {
      return TRUE;
    }

    return FALSE;
  }

}
