<?php

declare(strict_types=1);

namespace Drupal\grants_application;

/**
 * Application settings class.
 */
final class ApplicationSettings {

  /**
   * The actual data on the form.
   */
  private array $formData = [];

  /**
   * The constructor.
   */
  public function __construct(
    private array $settings,
    private array $schema,
    private array $uiSchema,
    private array $translation,
  ) {
  }

  /**
   * Set the form data.
   *
   * @param array $formData
   *   The form data.
   */
  public function setFormData(array $formData): void {
    $this->formData = $formData;
  }

  /**
   * Return the settings as an array.
   *
   * @return array
   *   Array of settings.
   */
  public function toArray(): array {
    return [
      'settings' => $this->settings,
      'schema' => $this->schema,
      'ui_schema' => $this->uiSchema,
      'translations' => $this->translation,
      'form_data' => $this->formData,
    ];
  }

  /**
   * Return the settings as an array.
   *
   * @return string
   *   Array of application form settings.
   */
  public function toJson(): string {
    return json_encode($this->toArray());
  }

}
