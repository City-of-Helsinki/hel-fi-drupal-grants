<?php

declare(strict_types=1);

namespace Drupal\grants_application;

use Drupal\grants_application\Form\FormSettings;

/**
 * Validates `misc:sum-match` rules declared in the form uiSchema.
 *
 * Backend counterpart of the React form's sum match validation, see the
 * forms-app README. A rule requires the sum of `fields` to equal the sum of
 * `compareTo`. Paths are root-relative dot notation.
 */
final class SumMatchValidator {

  /**
   * The default translation key for the error message.
   */
  private const DEFAULT_MESSAGE = 'sum_match.mismatch';

  /**
   * Validate the form data against the sum match rules.
   *
   * @param array<string, mixed> $formData
   *   The submitted form data.
   * @param \Drupal\grants_application\Form\FormSettings $settings
   *   The form settings.
   * @param string $langcode
   *   The language of the error messages.
   *
   * @return array<string, string>
   *   Error messages keyed by the path of the field the rule is declared on.
   */
  public function validate(array $formData, FormSettings $settings, string $langcode): array {
    $errors = [];

    foreach ($this->getRules($settings->getUiSchema()) as $path => $rule) {
      $mismatch = $this->getMismatch($formData, $rule['fields'], $rule['compareTo']);

      if ($mismatch === NULL) {
        continue;
      }

      [$left, $right, $difference] = array_map(
        fn (float $sum) => str_replace('.', ',', (string) round($sum, 2)),
        [...$mismatch, abs($mismatch[0] - $mismatch[1])],
      );
      $key = $rule['message'] ?? self::DEFAULT_MESSAGE;
      $message = $settings->getTranslation($key, $langcode) ?? $key;
      $errors[$path] = strtr($message, [
        '{{left}}' => $left,
        '{{right}}' => $right,
        '{{difference}}' => $difference,
      ]);
    }

    return $errors;
  }

  /**
   * Collect the sum match rules from the uiSchema.
   *
   * Fields compared default to the field's computedSum `sourceFields`, or the
   * field itself when it is a regular input.
   *
   * @param array<string, mixed> $uiSchema
   *   The uiSchema, or a part of it.
   * @param string $prefix
   *   The dot path of the current uiSchema element.
   *
   * @return array<string, array{fields: string[], compareTo: string[], message: ?string}>
   *   The rules keyed by the path of the field they are declared on.
   */
  public function getRules(array $uiSchema, string $prefix = ''): array {
    if (isset($uiSchema['misc:sum-match']) && is_array($uiSchema['misc:sum-match'])) {
      $rule = $uiSchema['misc:sum-match'];

      return [
        $prefix => [
          'fields' => $rule['fields'] ?? $uiSchema['ui:options']['sourceFields'] ?? [$prefix],
          'compareTo' => $rule['compareTo'] ?? [],
          'message' => $rule['message'] ?? NULL,
        ],
      ];
    }

    $rules = [];
    foreach ($uiSchema as $key => $value) {
      if (is_array($value) && !array_is_list($value)) {
        $rules += $this->getRules($value, $prefix === '' ? (string) $key : "$prefix.$key");
      }
    }

    return $rules;
  }

  /**
   * Compare the sums of two sets of fields.
   *
   * Passes when none of the fields have a value, so empty optional sections
   * don't produce errors. Sums are compared to two decimals.
   *
   * @param array<string, mixed> $formData
   *   The form data.
   * @param string[] $fields
   *   The fields to sum.
   * @param string[] $compareTo
   *   The fields to compare the sum against.
   *
   * @return float[]|null
   *   [sum, compareSum] when the sums differ, NULL otherwise.
   */
  public function getMismatch(array $formData, array $fields, array $compareTo): ?array {
    $hasValues = FALSE;
    foreach ([...$fields, ...$compareTo] as $field) {
      $value = $this->getValue($formData, $field);
      if ($value !== NULL && (!is_scalar($value) || trim((string) $value) !== '')) {
        $hasValues = TRUE;
        break;
      }
    }

    if (!$hasValues) {
      return NULL;
    }

    $sum = $this->sum($formData, $fields);
    $compareSum = $this->sum($formData, $compareTo);

    return round($sum * 100) === round($compareSum * 100) ? NULL : [$sum, $compareSum];
  }

  /**
   * Sum the numeric values of the given fields.
   *
   * Empty and non-numeric values count as zero. Comma decimal separators are
   * accepted.
   *
   * @param array<string, mixed> $formData
   *   The form data.
   * @param string[] $fields
   *   The fields to sum.
   *
   * @return float
   *   The sum.
   */
  private function sum(array $formData, array $fields): float {
    $total = 0.0;

    foreach ($fields as $field) {
      $value = $this->getValue($formData, $field);
      if (!is_int($value) && !is_float($value) && !is_string($value)) {
        continue;
      }

      $numeric = str_replace(',', '.', trim((string) $value));
      if (is_numeric($numeric)) {
        $total += (float) $numeric;
      }
    }

    return $total;
  }

  /**
   * Get a value from the form data by dot path.
   *
   * @param array<string, mixed> $formData
   *   The form data.
   * @param string $path
   *   The dot path.
   *
   * @return mixed
   *   The value, or NULL if the path does not exist.
   */
  private function getValue(array $formData, string $path): mixed {
    $current = $formData;

    foreach (explode('.', $path) as $key) {
      if (!is_array($current) || !array_key_exists($key, $current)) {
        return NULL;
      }
      $current = $current[$key];
    }

    return $current;
  }

}
