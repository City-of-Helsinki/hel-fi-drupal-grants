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
      'schema' => $this->getSchema(),
      'ui_schema' => $this->uiSchema,
      'translations' => $this->convertToI18nextFormat($this->translation),
    ];
  }

  /**
   * Convert translations from key-first format to i18next resource format.
   *
   * Input:  ['translations' => ['key' => ['en' => 'val', 'fi' => 'val']]]
   * Output: ['en' => ['translation' => ['key' => 'val']], 'fi' => [...]]
   *
   * @param array $translations
   *   Translations in key-first format.
   *
   * @return array
   *   Translations in i18next resource format.
   */
  private function convertToI18nextFormat(array $translations): array {
    $result = [];
    foreach ($translations['translations'] ?? [] as $key => $langs) {
      foreach ($langs as $lang => $value) {
        $result[$lang]['translation'][$key] = $value;
      }
    }
    return $result;
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
   * Get the application type id aka form id.
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
   * The acting year field is restricted to the acting years in settings, so
   * that a stale year (e.g. in a copied application) fails the validation.
   *
   * @return array
   *   The form schema.
   */
  public function getSchema(): array {
    $actingYears = array_map('strval', $this->getActingYears());

    // An empty enum would reject every value, so leave the field as is.
    if (!$actingYears) {
      return $this->schema;
    }

    return $this->restrictActingYears($this->schema, $actingYears);
  }

  /**
   * Set the acting years as the allowed values of every acting year field.
   *
   * @param array<mixed> $schema
   *   The schema, or a part of it.
   * @param string[] $actingYears
   *   The acting years.
   *
   * @return array<mixed>
   *   The schema with the acting year fields restricted.
   */
  private function restrictActingYears(array $schema, array $actingYears): array {
    foreach ($schema as $key => $child) {
      if (!is_array($child)) {
        continue;
      }

      if ($key === 'acting_year_section' && is_array($child['properties']['acting_year'] ?? NULL)) {
        $child['properties']['acting_year']['enum'] = $actingYears;
      }

      $schema[$key] = $this->restrictActingYears($child, $actingYears);
    }

    return $schema;
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

  /**
   * Can applications of this form be copied right now?
   *
   * A copy is a new draft, so the application period must be open as well.
   *
   * @return bool
   *   Copying is enabled and the application is open.
   */
  public function canBeCopied(): bool {
    return $this->isCopyable() && $this->isApplicationOpen();
  }

  /**
   * Get the application name.
   *
   * @return string
   *   The application name.
   */
  public function getApplicationName(): string {
    return $this->settings['title'] ?? '';
  }

  /**
   * Calculate delete after value for this application.
   *
   * Draft - continuous:
   * - With close-time: +1year.
   * - Without close-time: +1year.
   * Draft - non-continuous
   * - Close-time + 1 month
   * Submitted: +6 Years.
   *
   * @return string
   *   The delete after value.
   */
  public function getDraftDeleteAfter(): string {
    $isContinuous = $this->settings['continuous'] ?? FALSE;
    $endDate = $this->settings['application_close'] ?? NULL;

    if ($isContinuous && $endDate) {
      return date('Y-m-d', strtotime('+1 year'));
    }

    if ($isContinuous && !$endDate) {
      return date('Y-m-d', strtotime('+1 year'));
    }

    if (!$isContinuous && $endDate) {
      return (new \DateTimeImmutable($endDate))->add(new \DateInterval('P1M'))->format('Y-m-d');
    }

    return date('Y-m-d', strtotime('+1 year'));
  }

  /**
   * Get the acting years for the form.
   *
   * @return int[]
   *   The acting years.
   */
  public function getActingYears(): array {
    return $this->settings['acting_years'] ?? [];
  }

  /**
   * Get the settings with the acting years of the given form data allowed.
   *
   * A submitted application must stay editable even after its acting year
   * is no longer among the acting years of the form.
   *
   * @param array<string, mixed> $form_data
   *   The form data of a submitted application.
   *
   * @return self
   *   The form settings.
   */
  public function withActingYearsOf(array $form_data): self {
    $actingYears = $this->getActingYears();

    array_walk_recursive($form_data, function ($value, $key) use (&$actingYears) {
      if ($key === 'acting_year' && is_numeric($value)) {
        $actingYears[] = (int) $value;
      }
    });

    $actingYears = array_values(array_unique($actingYears));
    sort($actingYears);

    return new self(
      ['acting_years' => $actingYears] + $this->settings,
      $this->schema,
      $this->uiSchema,
      $this->translation,
    );
  }

}
