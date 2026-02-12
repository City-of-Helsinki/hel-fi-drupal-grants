<?php

declare(strict_types=1);

namespace Drupal\grants_application\Form;

/**
 * React form validator.
 */
class FormValidator {

  public function __construct(private FormSettingsServiceInterface $formSettingsService) {
  }

  /**
   * Form validator.
   *
   * @param int|string $id
   *   The application type id.
   * @param array $data
   *   The form data.
   *
   * @return bool
   *   Form data is in valid state.
   */
  public function validateForm(int|string $id, array $data): bool {
    $schema = $this->formSettingsService->getFormSettings($id)->toArray()['schema'];
    if (!$schema) {
      throw new \Exception('Form schema not found');
    }

    // @todo Use swaggest/json-schema or similar.
    return FALSE;
  }

}
