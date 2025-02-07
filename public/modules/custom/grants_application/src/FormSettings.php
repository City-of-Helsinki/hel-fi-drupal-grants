<?php

declare(strict_types=1);

namespace Drupal\grants_application;

/**
 * Application settings class.
 */
final class FormSettings {

  /**
   * The actual data on the form.
   */
  private array $formData = [];

  /**
   * Company specific data shown on the application form.
   */
  private array $grantsProfile = [];

  /**
   * User specific data.
   */
  private array $userData = [];

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
   * Set the grants profile data.
   *
   * @param array $grantsProfile
   *   The grants profile data.
   */
  public function setGrantsProfileData(array $grantsProfile): void {
    $this->grantsProfile = $grantsProfile;
  }

  /**
   * Set the user specific data.
   *
   * @param array $userData
   *   The user data.
   */
  public function setUserData(array $userData): void {
    $this->userData = $userData;
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
      'grants_profile' => $this->grantsProfile,
      'user_data' => $this->userData,
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
