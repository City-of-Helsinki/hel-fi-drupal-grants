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
   * Get the application type id.
   *
   * @return int
   *   The application type id.
   */
  public function getFormId(): int {
    return $this->settings['application_type_id'];
  }

  /**
   * Get the form identifier.
   *
   * @return string
   *   The form identifier.
   */
  public function getFormIdentifier(): string {
    return $this->settings['form_identifier'];
  }

  /**
   * Check if applicant is within allowed applicant types.
   *
   * @param string $applicantType
   *   The applicant type.
   *
   * @return bool
   *   Is allowed applicant type.
   */
  public function isAllowedApplicantType(string $applicantType): bool {
    return (
      isset($this->settings['applicant_types']) &&
      in_array($applicantType, $this->settings['applicant_types'] ?? [])
    );
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

    if (!$this->settings['application_open'] || !$this->settings['application_close']) {
      return FALSE;
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

  /**
   * Get the application open date time.
   *
   * @return string
   *   the open time as string.
   */
  public function getApplicationOpen(): string {
    return $this->settings['application_open'] ?? '';
  }

  /**
   * Get the application close date time.
   *
   * @return string
   *   the close time as string.
   */
  public function getApplicationClose(): string {
    return $this->settings['application_close'] ?? '';
  }

  /**
   * Get form schema.
   *
   * @return array
   *   The form schema.
   */
  public function getSchema(): array {
    return $this->schema;
  }

  /**
   * Get copyable status.
   *
   * @return bool
   *   Is copyable.
   */
  public function isCopyable(): bool {
    return !$this->settings['disable_copy'];
  }

}
