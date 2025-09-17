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
   * Get the application settings.
   *
   * @param int|string $form_type_id
   *   The id of the application.
   *
   * @return \Drupal\grants_application\Form\FormSettings
   *   Contains json-schema, third party settings and translations etc.
   *
   * @throws \InvalidArgumentException
   *   When application configuration is not found.
   */
  public function getFormSettings(int|string $form_type_id): FormSettings;

  /**
   * Checks if the application is open.
   *
   * @param int $id
   *   The numeric ID of the form.
   *
   * @return bool
   *   TRUE if the application is open, FALSE otherwise.
   */
  public function isApplicationOpen(int $id): bool;

  /**
   * Loads and returns configuration data from JSON files.
   *
   * @param string $configName
   *   The name of the configuration file (without .json extension).
   * @param string|null $sectionName
   *   (optional) The specific section to return from the configuration.
   *
   * @return array
   *   The requested configuration data.
   *
   * @throws \RuntimeException
   *   If the configuration cannot be loaded or parsed.
   */
  public function getFormConfig(string $configName, ?string $sectionName = ''): array;

  /**
   * Retrieves translated labels from a configuration section.
   *
   * @param array|null $section
   *   The configuration section containing label data.
   *
   * @return string|array
   *   The translated label(s).
   */
  public function getLabels(?array $section): string|array;

}
