<?php

declare(strict_types=1);

namespace Drupal\grants_application\Form;

/**
 * A class for retrieving form specific settings.
 */
class FormSettingsService {

  /**
   * Hardcoded dummy list of forms.
   */
  private array $forms = [
    52 => 'kaskoiptoim',
    58 => 'liikuntasuunnistus',
  ];

  /**
   * Get the application settings.
   *
   * This is initial hardcoded way to load the settings. The form name
   * is the same as the folder name in fixtures folder.
   *
   * @param int|string $form_type_id
   *   The id of the application.
   *
   * @return FormSettings
   *   Contains json-schema, third party settings and translations etc.
   */
  public function getFormSettings(int|string $form_type_id): FormSettings {
    if (!isset($this->forms[$form_type_id])) {
      throw new \InvalidArgumentException('Application not found.');
    }

    $form_name = $this->forms[$form_type_id];

    // Load all the required settings from fixtures.
    $settings = [];
    foreach ($this->getSettingsFiles() as $suffix) {
      $path_to_file = sprintf(__DIR__ . '/../../fixtures/%s/%s.json',
        $form_name,
        $suffix
      );
      $data = file_get_contents($path_to_file) ?: '{}';
      $settings[$suffix] = json_decode($data, TRUE);
    }

    $settings['translation'] = $this->combineTranslations($settings['translation']);

    return new FormSettings(...$settings);
  }

  /**
   * Is the application open?
   *
   * @param int $id
   *   The numeric ID of the form.
   *
   * @return bool
   *   Application is open.
   */
  public function isApplicationOpen(int $id): bool {
    return $this->getFormSettings($id)->isApplicationOpen();
  }

  /**
   * Combine default translations with form specific translations.
   *
   * @param array $translations
   *   Form specific translations.
   */
  private function combineTranslations(array $translations): array {
    $defaultTranslations = json_decode(file_get_contents(__DIR__ . '/../../fixtures/defaultTranslations.json'), TRUE);

    return array_merge_recursive($defaultTranslations, $translations);
  }

  /**
   * Read the dummy hardcoded form from json files.
   */
  private function getSettingsFiles(): array {
    return ['settings', 'schema', 'uiSchema', 'translation'];
  }

}
