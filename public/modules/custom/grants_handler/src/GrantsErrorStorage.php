<?php

namespace Drupal\grants_handler;

/**
 * Class to store webform validations errors during runtime.
 */
class GrantsErrorStorage {

  /**
   * Current errors.
   *
   * @var array
   */
  private static array $errors = [];

  /**
   * Retrieve current errors.
   *
   * @return array
   * All currently set validation errors.
   */
  public static function getErrors() {
    return self::$errors;
  }

  /**
   * Set new error values to the class.
   *
   * @param array $errors
   */
  public static function setErrors(array $errors) {
    self::$errors = $errors;
  }

}
