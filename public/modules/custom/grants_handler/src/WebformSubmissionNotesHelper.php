<?php

namespace Drupal\grants_handler;

use Drupal\Component\Serialization\Json;
use Drupal\webform\WebformSubmissionInterface;

/**
 *
 */
class WebformSubmissionNotesHelper {

  /**
   *
   */
  private static function getCustomData(WebformSubmissionInterface $submission) {
    $data = $submission->get('notes')->value;
    $values = Json::decode($data);
    $error = json_last_error();
    return $values;
  }

  /**
   *
   */
  private static function setValues(WebformSubmissionInterface $submission, $values) {
    if (empty($values)) {
      $submission->set('notes', NULL);
      return;
    }

    $submission->set('notes', Json::encode($values));
  }

  /**
   *
   */
  public static function setValue(WebformSubmissionInterface $submission, $key, $value) {
    $values = self::getCustomData($submission);
    $values[$key] = $value;
    self::setValues($submission, $values);
  }

  /**
   *
   */
  public static function removeValue(WebformSubmissionInterface $submission, $key) {
    $values = self::getCustomData($submission);
    unset($values[$key]);
    self::setValues($submission, $values);
  }

  /**
   *
   */
  public static function getValue(WebformSubmissionInterface $submission, $key) {
    $customValues = self::getCustomData($submission);
    if (!isset($customValues[$key])) {
      return NULL;
    }

    return $customValues[$key];
  }

}
