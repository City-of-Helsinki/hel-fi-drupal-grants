<?php

declare(strict_types=1);

namespace Drupal\grants_application;

/**
 * A class for retrieving application specific settings.
 */
class ApplicationSettingsService {

  /**
   * Hardcoded dummy list of arrays.
   */
  private array $applications = ['liikuntasuunnistus' => 'liikuntasuunnistus'];

  /**
   * Get the application settings.
   *
   * @param int $id
   *   The id of the application.
   *
   * @return ApplicationSettings
   *   Contains json-schema, third party settings and translations.
   */
  public function getApplicationSettings(string $id): ApplicationSettings {
    if (!isset($this->applications[$id])) {
      throw new \InvalidArgumentException('Application not found.');
    }

    $application_name = $this->applications[$id];

    $settings = [];
    foreach ($this->getSettingsFiles() as $suffix) {
      $path_to_file = sprintf(__DIR__ . '/../fixtures/%s/%s.json',
        $application_name,
        $suffix
      );
      $data = file_get_contents($path_to_file) ?: '{}';
      $settings[$suffix] = json_decode($data, TRUE);
    }

    return new ApplicationSettings(...$settings);
  }

  /**
   * Read the dummy hardcoded application from json files.
   */
  private function getSettingsFiles(): array {
    return ['settings', 'schema', 'uiSchema', 'translation'];
  }

}

