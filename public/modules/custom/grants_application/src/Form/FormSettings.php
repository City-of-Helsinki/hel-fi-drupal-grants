<?php

declare(strict_types=1);

namespace Drupal\grants_application\Form;

/**
 * Form settings class.
 */
final class FormSettings {

  /**
   * The constructor.
   *
   * @param array $settings
   *   The metadata related to the form, check fixtures settings.json.
   * @param array $schema
   *   The form RJSF-schema.
   * @param array $uiSchema
   *   The form RJSF-ui-schema.
   * @param array $translation
   *   The form translations.
   */
  public function __construct(
    private readonly array $settings,
    private readonly array $schema,
    private readonly array $uiSchema,
    private readonly array $translation,
  ) {
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

  /**
   * Are we within application period?
   *
   * @return bool
   *   Application is open.
   */
  public function isApplicationOpen(): bool {
    if (isset($this->settings['continuous']) && $this->settings['continuous']) {
      return TRUE;
    }

    try {
      $open = new \DateTime($this->settings['application_open']);
      $closed = new \DateTime($this->settings['application_close']);
      $now = new \DateTime();
    }
    catch (\Exception $e) {
      return FALSE;
    }

    if ($open < $now && $closed > $now) {
      return TRUE;
    }

    return FALSE;
  }

}
