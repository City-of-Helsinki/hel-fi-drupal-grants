<?php

namespace Drupal\grants_metadata;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\locale\StringStorageInterface;
use Drupal\locale\TranslationString;

/**
 * Provide useful helper for converting values.
 */
class GrantsConverterService {

  use StringTranslationTrait;

  const DEFAULT_DATETIME_FORMAT = 'c';

  /**
   * Constructs a new GrantsConverterService.
   *
   * @param \Drupal\locale\StringStorageInterface $storage
   *   String database storage.
   */
  public function __construct(private StringStorageInterface $storage) {
  }

  /**
   * Format dates to a given or default format.
   *
   * @param mixed $value
   *   Input value.
   * @param array $arguments
   *   Arguments, dateFormat is used.
   *
   * @return string
   *   Formatted datetime string.
   */
  public function convertDates(mixed $value, array $arguments): string {

    try {
      if ($value === NULL || $value === '') {
        $retval = '';
      }
      else {
        $dateObject = new \DateTime($value);
        if (isset($arguments['dateFormat'])) {
          $retval = $dateObject->format($arguments['dateFormat']);
        }
        else {
          $retval = $dateObject->format(self::DEFAULT_DATETIME_FORMAT);
        }
      }
    }
    catch (\Exception $e) {
      $retval = '';
    }

    return $retval;
  }

  /**
   * Extract & process subvention amount field value.
   *
   * @param array|string $value
   *   Value from JSON data.
   *
   * @return string
   *   Processed field value.
   */
  public function extractFloatValue(array|string $value): string {
    if (is_array($value)) {
      return str_replace('.', ',', $value['value']);
    }

    return str_replace('.', ',', $value);
  }

  /**
   * The convertSportName method.
   *
   * This method attempts to convert any translated sport names
   * (Finnish or Swedish) back to the original English
   * name. If a translation is found, the translations source
   * string (the English version) is returned through
   * the t() function. Otherwise, the passed in value is just
   * returned through the t() function.
   *
   * Ex. 1,  $value = 'Käsipallo'.
   * 1. Look for a translation entry for the string 'Käsipallo'.
   * 2. If one is found, get the source string, which is 'Handball'.
   * 3. Return the value 'Handball' back through the t() function.
   *
   * Ex. 2,  $value = 'Football'.
   * 1. Look for a translation entry for the string 'Football'.
   * 2. A translation won't be found since it is the source translation.
   * 3. Return the value 'Football' back through the t() function.
   *
   * @param array|string $value
   *   The sport name or an array containing the 'value'
   *   key with the sport name.
   *
   * @return string
   *   English (source string) version of a sports name,
   *   passed through the t() function.
   */
  public function convertSportName(array|string $value): string {
    $tOpts = ['context' => 'grants_club_section'];
    $original = $value['value'] ?? $value;

    if (empty($original)) {
      return '';
    }

    $translationEntry = $this->storage->getTranslations([
      'translation' => $original,
      'context' => 'grants_club_section',
      'translated' => TRUE,
    ]);

    if (!empty($translationEntry)) {
      /** @var \Drupal\locale\TranslationString $translationEntry */
      $translationEntry = reset($translationEntry);

      if ($translationEntry instanceof TranslationString) {
        return $this->t($translationEntry->source, [], $tOpts); // phpcs:ignore
      }
    }

    return $this->t($original, [], $tOpts); // phpcs:ignore
  }

  /**
   * Convert "dot" float to "comma" float.
   *
   * @param array $value
   *   Value to be converted.
   *
   * @return string|null
   *   Comma floated value.
   */
  public function convertToCommaFloat(array $value): ?string {
    $fieldValue = $value['value'] ?? '';
    return str_replace(['€', '.', ' '], ['', ',', ''], $fieldValue);
  }

}
