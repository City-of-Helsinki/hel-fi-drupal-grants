<?php

declare(strict_types=1);

namespace Drupal\grants_application\Form;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Interface for form settings service.
 *
 * Provides methods to retrieve and manage form configurations and settings.
 */
interface FormSettingsServiceInterface extends ContainerFactoryPluginInterface {

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
  public function getFormSettings(int|string $form_type_id, ?string $identifier): FormSettings;

  /**
   * Checks if the application is open.
   *
   * @param int|string $id
   *   The numeric ID of the form.
   * @param string $identifier
   *   The identifier of the form.
   *
   * @return bool
   *   TRUE if the application is open, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *    When the application_metadata entity type is not valid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *    When the application_metadata entity type is not found.
   */
  public function isApplicationOpen(int|string $id, string $identifier): bool;

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
  public function getFormConfig(string $configName): array;

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
   * @param string|null $langcode
   *   The language code to use for translation.
   *
   * @return string|array
   *   - If the input is a single label section, returns the translated string
   *   - If the input contains multiple labels, returns an array of translated
   *     strings
   *   - Returns an empty string if no matching translation is found
   */
  public function getLabels(?array $section, ?string $langcode = NULL): string|array;

}
