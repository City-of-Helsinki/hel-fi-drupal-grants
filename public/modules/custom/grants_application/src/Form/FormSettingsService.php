<?php

declare(strict_types=1);

namespace Drupal\grants_application\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\grants_application\Entity\ApplicationMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A class for retrieving form specific settings.
 */
final class FormSettingsService implements FormSettingsServiceInterface {

  /**
   * List of application types.
   */
  private array $formTypes;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly ModuleExtensionList $moduleExtensionList,
    protected LanguageManagerInterface $languageManager,
    protected ?string $formConfigDir = NULL,
    protected ?string $fixturesDir = NULL,
  ) {
    // Default to module directories if not provided.
    $moduleRelativePath = $this->moduleExtensionList->getPath('grants_application');
    $moduleAbsolutePath = rtrim((defined('DRUPAL_ROOT') ? DRUPAL_ROOT . '/' : '') . $moduleRelativePath, '/');

    $this->formConfigDir = $formConfigDir ?: $moduleAbsolutePath . '/form_configuration';
    $this->fixturesDir = $fixturesDir ?: $moduleAbsolutePath . '/fixtures';

    // Load form types from configured directory.
    $formTypesPath = $this->formConfigDir . '/form_types.json';
    $json = file_get_contents($formTypesPath);
    if ($json === FALSE) {
      throw new \RuntimeException(sprintf('Unable to read %s', $formTypesPath));
    }
    $this->formTypes = json_decode($json, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \RuntimeException(sprintf('Invalid JSON in %s: %s', $formTypesPath, json_last_error_msg()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id = '', $plugin_definition = NULL) {
    $formConfigDir = $container->hasParameter('grants_application.form_config_dir')
      ? (string) $container->getParameter('grants_application.form_config_dir')
      : NULL;
    $fixturesDir = $container->hasParameter('grants_application.fixtures_dir')
      ? (string) $container->getParameter('grants_application.fixtures_dir')
      : NULL;

    return new static(
      $container->get('entity_type.manager'),
      $container->get('extension.list.module'),
      $container->get('language_manager'),
      $formConfigDir,
      $fixturesDir,
    );
  }

  /**
   * Retrieves form settings for a specific form type.
   *
   * Loads form settings from either the application metadata or from fixture
   * files if no metadata is available.
   *
   * @param int|string $form_type_id
   *   The unique identifier of the form type to load settings for.
   * @param string|null $identifier
   *   The form identifier.
   *
   * @return \Drupal\grants_application\Form\FormSettings
   *   The form settings object containing configuration,
   *   schema and translations.
   *
   * @throws \InvalidArgumentException
   *   When the specified form type ID is not found in the configuration.
   * @throws \RuntimeException
   *   When there's an error reading the form configuration files.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   When the application_metadata entity type is not found.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   When the application_metadata entity type is not valid.
   */
  public function getFormSettings(int|string $form_type_id, ?string $identifier = NULL): FormSettings {
    // ID70 requires identifier.
    $form_type = array_find($this->formTypes, fn($type) => $type['id'] == $form_type_id && ($type['id'] !== '70' || $type['form_identifier'] === $identifier));

    if (!$form_type || !isset($form_type['id'])) {
      throw new \InvalidArgumentException(sprintf('Unknown form type id: %s %s', (string) $form_type_id, $identifier ?? 'unknown'));
    }
    $form_name = $form_type['form_identifier'];
    $settings = [];

    // Load all the required settings from fixtures.
    foreach ($this->getSettingsFiles() as $suffix) {
      $pathToFile = sprintf('%s/%s/%s.json', $this->fixturesDir, $form_name, $suffix);
      $data = file_get_contents($pathToFile) ?: '{}';

      if (!isset($settings[$suffix])) {
        $settings[$suffix] = json_decode($data, TRUE);
      }
    }

    // Load application metadata if available and set it to settings.
    $storage = $this->entityTypeManager->getStorage('application_metadata');
    $matches = $storage->loadByProperties(['application_type_id' => $form_type_id]);
    $application_metadata = reset($matches);

    /** @var \Drupal\grants_application\Entity\ApplicationMetadata $application_metadata */
    if ($application_metadata instanceof ApplicationMetadata) {
      $settings['settings'] = $application_metadata->getMetadata();
    }

    // Throw an exception if settings are not found.
    if (!isset($settings['settings'])) {
      throw new \Exception("Unable to load settings for form $form_type_id.");
    }

    // Combine form specific translations with default translations.
    $settings['translation'] = $this->combineTranslations($settings['translation']);

    return new FormSettings(...$settings);
  }

  /**
   * Get the metadata object related to the settings.
   *
   * @param int|string $application_type_id
   *   The application type id.
   * @param string|null $identifier
   *   The form identifier.
   *
   * @return \Drupal\grants_application\Entity\ApplicationMetadata|null
   *   The application metadata object.
   */
  public function getFormSettingsMetadata(int|string $application_type_id, ?string $identifier = NULL): ?ApplicationMetadata {
    $storage = $this->entityTypeManager->getStorage('application_metadata');
    $parameters = ['application_type_id' => $application_type_id];

    if ($identifier) {
      $parameters['form_identifier'] = $identifier;
    }

    /** @var \Drupal\grants_application\Entity\ApplicationMetadata[] $matches */
    $matches = $storage->loadByProperties($parameters);
    return reset($matches) ?: NULL;
  }

  /**
   * Is the application open?
   *
   * @param int|string $id
   *   The numeric ID of the form.
   * @param string $identifier
   *   The identifier for application.
   *
   * @return bool
   *   Application is open.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   When the application_metadata entity type is not valid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   When the application_metadata entity type is not found.
   */
  public function isApplicationOpen(int|string $id, string $identifier): bool {
    return $this->getFormSettings($id, $identifier)->isApplicationOpen();
  }

  /**
   * Loads and returns configuration data from JSON files.
   *
   * This method will provide configuration data. It can return either
   * the entire configuration array or a specific section if specified.
   *
   * @param string $configName
   *   The name of the configuration file (without .json extension) to load.
   *   Example: 'form_configuration' loads from 'form_configuration.json'.
   *
   * @return array
   *   The requested configuration data. If $sectionName is provided and exists,
   *   returns only that section. Otherwise, returns the entire configuration.
   *
   * @throws \RuntimeException
   *   If the configuration file cannot be found, read, or parsed as JSON.
   *
   * @see \Drupal\grants_application\Form\FormSettingsService::loadApplicationConfig()
   */
  public function getFormConfig(string $configName): array {
    return $this->loadApplicationConfig($configName);
  }

  /**
   * Get form configuration by application type id.
   *
   * @param int|string $id
   *   The application type id.
   * @param string $identifier
   *   The identifier.
   *
   * @return array
   *   The form configuration.
   */
  public function getFormConfigById(int|string $id, string $identifier): ?array {
    return array_find(
      $this->loadApplicationConfig('form_types'),
      fn($item) => $item['id'] == $id && $item['form_identifier'] === $identifier
    );
  }

  /**
   * Get application labels.
   *
   * @param array $section
   *   The section from configuration.
   *
   * @return array|string|null
   *   The label or array of labels.
   */
  public function getApplicationLabels(array $section): array|string|null {
    $language = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)
      ->getId();

    // Return empty string if section is not an array.
    if (!is_array($section)) {
      return '';
    }

    // Return single label.
    if (array_key_exists('labels', $section)) {
      $labels = $section['labels'];
      return $labels[$language]
        ?? $labels['en']
        ?? $labels[LanguageInterface::LANGCODE_NOT_SPECIFIED]
        ?? '';
    }

    // Return the result as array with application type id as key.
    $result = [];
    foreach ($section as $s) {
      $result[$s['id']]['labels'] = $s['labels'];
    }

    // Go through array of labels and return array of translated strings.
    return array_map(function ($type) use ($language) {
      if (!isset($type['labels']) || !is_array($type['labels'])) {
        return [];
      }

      $labels = $type['labels'];
      return $labels[$language]
        ?? $labels['en']
        ?? $labels[LanguageInterface::LANGCODE_NOT_SPECIFIED]
        ?? [];
    }, $result);
  }

  /**
   * Retrieves translated labels from a configuration section.
   *
   * This method handles both single label and multiple label translations.
   * It will automatically use the current interface language and fall back to
   * English ('en') or unspecified language ('und') if the current language is
   * not available.
   *
   * @param array|null $section
   *   The configuration section containing label data. Expected to have a
   *   'labels' key with language codes as keys and translations as values.
   *
   * @return string|array
   *   - If the input is a single label section, returns the translated string
   *   - If the input contains multiple labels, returns an array of translated
   *     strings
   *   - Returns an empty string if no matching translation is found
   *
   * @see \Drupal\Core\Language\LanguageManagerInterface
   */
  public function getLabels(?array $section): string|array {
    $language = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_INTERFACE)
      ->getId();

    // Return empty string if section is not an array.
    if (!is_array($section)) {
      return '';
    }

    // Return single label.
    if (array_key_exists('labels', $section)) {
      $labels = $section['labels'];
      return $labels[$language]
        ?? $labels['en']
        ?? $labels[LanguageInterface::LANGCODE_NOT_SPECIFIED]
        ?? '';
    }

    // Go through array of labels and return array of translated strings.
    return array_map(function ($type) use ($language) {
      if (!isset($type['labels']) || !is_array($type['labels'])) {
        return [];
      }

      $labels = $type['labels'];
      return $labels[$language]
        ?? $labels['en']
        ?? $labels[LanguageInterface::LANGCODE_NOT_SPECIFIED]
        ?? [];
    }, $section);
  }

  /**
   * Combine default translations with form specific translations.
   *
   * @param array $translations
   *   Form specific translations.
   */
  private function combineTranslations(array $translations): array {
    $path = $this->fixturesDir . '/defaultTranslations.json';
    $default = file_get_contents($path);
    $defaultTranslations = $default !== FALSE ? json_decode($default, TRUE) : [];
    return array_replace_recursive($defaultTranslations, $translations);
  }

  /**
   * Get the required setting files for any application.
   *
   * @return array
   *   List of files required by any React-application form.
   */
  private function getSettingsFiles(): array {
    $files = ['schema', 'uiSchema', 'translation'];
    if (getenv('APP_ENV') !== 'production') {
      $files[] = 'settings';
    }

    return $files;
  }

  /**
   * Loads and parses a JSON configuration file.
   *
   * This method handles the low-level loading and parsing of JSON configuration
   * files from the module's form_configuration directory.
   *
   * @param string $configName
   *   The base name of the configuration file (without .json extension).
   *   Example: 'form_configuration' loads from 'form_configuration.json'.
   * @param string|null $sectionName
   *   (optional) The specific section to return from the configuration.
   *   If provided, only that section will be returned.
   *
   * @return array
   *   The parsed configuration data. If $sectionName is provided and exists,
   *   returns only that section, otherwise the entire configuration.
   *
   * @throws \RuntimeException
   *   Thrown if:
   *   - The configuration file cannot be found.
   *   - The file cannot be read.
   *   - The file contains invalid JSON.
   *   - The provided section name doesn't exist in the configuration.
   *
   * @see \Drupal\grants_application\Form\FormSettingsService::getFormConfig()
   */
  protected function loadApplicationConfig(string $configName, ?string $sectionName = NULL): array {
    $filePath = $this->formConfigDir . '/' . $configName . '.json';

    if (!file_exists($filePath)) {
      throw new \RuntimeException(sprintf('Configuration file %s not found', $filePath));
    }

    $content = file_get_contents($filePath);
    if ($content === FALSE) {
      throw new \RuntimeException(sprintf('Could not read configuration file %s', $filePath));
    }

    $decoded = json_decode($content, TRUE);
    if (json_last_error() !== JSON_ERROR_NONE) {
      throw new \RuntimeException(sprintf('Invalid JSON in configuration file %s: %s',
        $filePath,
        json_last_error_msg()
      ));
    }

    // If section name is given, return only that section.
    if ($sectionName && array_key_exists($sectionName, $decoded)) {
      return $decoded[$sectionName];
    }
    return $decoded;
  }

}
