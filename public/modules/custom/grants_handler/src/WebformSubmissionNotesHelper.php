<?php

namespace Drupal\grants_handler;

use Drupal\Component\Serialization\Json;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Helper class to handle custom data saved in submissions notes field.
 */
class WebformSubmissionNotesHelper {

  /**
   * Get all custom values from the notes field.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission object.
   */
  private static function getCustomData(WebformSubmissionInterface $submission) {
    $data = $submission->get('notes')->value;
    // Do no pass null values to json decode.
    if (!$data) {
      return $data;
    }
    return Json::decode($data);
  }

  /**
   * Updates custom data to the submission object.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission object.
   * @param array|null $values
   *   Values to be saved. This will be Json encoded, unless the value is null.
   */
  private static function setValues(WebformSubmissionInterface $submission, ?array $values): void {
    if (empty($values)) {
      $submission->set('notes', NULL);
      return;
    }

    $submission->set('notes', Json::encode($values));
  }

  /**
   * Sets value to specific key.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission object.
   * @param string $key
   *   Key where the value is stored.
   * @param mixed $value
   *   Value to be saved, needs to be json serializable.
   */
  public static function setValue(WebformSubmissionInterface $submission, string $key, mixed $value): void {
    $values = self::getCustomData($submission);
    $values[$key] = $value;
    self::setValues($submission, $values);
  }

  /**
   * Remove a value by key.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission object.
   * @param string $key
   *   Key to remove.
   */
  public static function removeValue(WebformSubmissionInterface $submission, string $key): void {
    $values = self::getCustomData($submission);
    unset($values[$key]);
    self::setValues($submission, $values);
  }

  /**
   * Get a value by key.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission object.
   * @param string $key
   *   Key to return.
   *
   * @return mixed
   *   Value from the notes field or NULL if not found.
   */
  public static function getValue(WebformSubmissionInterface $submission, string $key): mixed {
    $customValues = self::getCustomData($submission);
    if (!isset($customValues[$key])) {
      return NULL;
    }

    return $customValues[$key];
  }

}
