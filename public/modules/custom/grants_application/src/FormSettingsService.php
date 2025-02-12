<?php

declare(strict_types=1);

namespace Drupal\grants_application;

/**
 * A class for retrieving form specific settings.
 */
class FormSettingsService {

  /**
   * Hardcoded dummy list of forms.
   */
  private array $forms = ['liikuntasuunnistus' => 'liikuntasuunnistus'];

  /**
   * Get the application settings.
   *
   * @param string $id
   *   The id of the application.
   *
   * @return FormSettings
   *   Contains json-schema, third party settings and translations etc.
   */
  public function getFormSettings(string $id): FormSettings {
    if (!isset($this->forms[$id])) {
      throw new \InvalidArgumentException('Application not found.');
    }

    $form_name = $this->forms[$id];

    $settings = [];
    foreach ($this->getSettingsFiles() as $suffix) {
      $path_to_file = sprintf(__DIR__ . '/../fixtures/%s/%s.json',
        $form_name,
        $suffix
      );
      $data = file_get_contents($path_to_file) ?: '{}';
      $settings[$suffix] = json_decode($data, TRUE);
    }

    return new FormSettings(...$settings);
  }

  /**
   * Is the application open?
   *
   * @param string $id
   *   ID of the form.
   *
   * @return bool
   *   Application is open.
   */
  public function isApplicationOpen(string $id): bool {
    return $this->getFormSettings($id)->isApplicationOpen();
  }

  /**
   * Read the dummy hardcoded form from json files.
   */
  private function getSettingsFiles(): array {
    return ['settings', 'schema', 'uiSchema', 'translation'];
  }

}
