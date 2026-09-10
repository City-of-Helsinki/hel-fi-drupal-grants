<?php

namespace Drupal\grants_metadata\Validator;

/**
 * The EmailValidator class.
 */
class EmailValidator {
  const PATTERN = "(?:[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-zA-Z0-9!#$%&'*+/=?^_`{|}~-]+)*" .
  "|\"(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21\x23-\x5b\x5d-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])*\")@" .
  "(?:(?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?|" .
  "\[(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?|" .
  "[a-zA-Z0-9-]*[a-zA-Z0-9]:(?:[\x01-\x08\x0b\x0c\x0e-\x1f\x21-\x5a\x53-\x7f]|\\[\x01-\x09\x0b\x0c\x0e-\x7f])+)\])";

  /**
   * The greatest number of characters an address may have.
   *
   * RFC 5321 limits a path to 256 characters including the angle brackets,
   * which leaves 254 for the address itself, the "@" included.
   */
  const MAX_LENGTH = 254;

  /**
   * The greatest number of characters the part before the "@" may have.
   */
  const MAX_LOCAL_PART_LENGTH = 64;

  /**
   * Builds the address pattern with the length limits applied.
   *
   * @return string
   *   A pattern accepting addresses within the length limits.
   */
  public static function getPatternWithLengthLimits(): string {
    return sprintf(
      '(?=.{1,%d}$)(?=[^@]{1,%d}@)%s',
      self::MAX_LENGTH,
      self::MAX_LOCAL_PART_LENGTH,
      self::PATTERN
    );
  }

}
