<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_application\SumMatchValidator;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\grants_application\SumMatchValidator
 *
 * @group grants_application
 */
final class SumMatchValidatorTest extends UnitTestCase {

  private const GENDER_FIELDS = [
    'step.genders.women',
    'step.genders.men',
    'step.genders.other',
    'step.genders.unknown',
  ];

  private const AGE_FIELDS = [
    'step.ages.minors',
    'step.ages.adults',
    'step.ages.seniors',
  ];

  /**
   * Build form settings with a sum match rule on a computedSum total.
   *
   * @param array<string, mixed> $rule
   *   The misc:sum-match rule.
   *
   * @return \Drupal\grants_application\Form\FormSettings
   *   The form settings.
   */
  private function getSettings(array $rule = []): FormSettings {
    $uiSchema = [
      'step' => [
        'genders' => [
          'total' => [
            'ui:field' => 'computedSum',
            'ui:options' => ['sourceFields' => self::GENDER_FIELDS],
            'misc:sum-match' => $rule + ['compareTo' => self::AGE_FIELDS],
          ],
        ],
      ],
    ];
    $translation = [
      'translations' => [
        'sum_match.mismatch' => [
          'en' => 'The totals do not match ({{left}} / {{right}}).',
          'fi' => 'Yhteissummat eivät vastaa toisiaan ({{left}} / {{right}}).',
        ],
        'custom.key' => ['fi' => 'Tiedot eivät vastaa toisiaan'],
        'difference.key' => ['fi' => 'Ero on {{difference}} liikkujaa'],
      ],
    ];

    return new FormSettings([], [], $uiSchema, $translation);
  }

  /**
   * Build form data.
   *
   * @param array<string, int|string> $ages
   *   Age field values.
   * @param array<string, int|string> $genders
   *   Gender field values.
   *
   * @return array<string, array<string, array<string, int|string>>>
   *   The form data.
   */
  private function getFormData(array $ages, array $genders): array {
    return ['step' => ['ages' => $ages, 'genders' => $genders]];
  }

  /**
   * Matching sums pass, mixing integers, strings and comma decimals.
   */
  public function testMatchingSums(): void {
    $formData = $this->getFormData(
      ['minors' => 3, 'adults' => '5', 'seniors' => ''],
      ['women' => 4, 'men' => 3, 'other' => '0,5', 'unknown' => '0,5'],
    );

    $this->assertSame([], (new SumMatchValidator())->validate($formData, $this->getSettings(), 'en'));
  }

  /**
   * Differing sums produce a translated, interpolated error on the rule path.
   */
  public function testMismatchingSums(): void {
    $formData = $this->getFormData(
      ['minors' => 1, 'adults' => 1, 'seniors' => 1],
      ['women' => 4, 'men' => '3,5'],
    );

    $this->assertSame(
      ['step.genders.total' => 'The totals do not match (7,5 / 3).'],
      (new SumMatchValidator())->validate($formData, $this->getSettings(), 'en'),
    );
  }

  /**
   * A custom message key is used, falling back to Finnish.
   */
  public function testCustomMessageFallsBackToFinnish(): void {
    $formData = $this->getFormData(['minors' => 2], []);

    $this->assertSame(
      ['step.genders.total' => 'Tiedot eivät vastaa toisiaan'],
      (new SumMatchValidator())->validate($formData, $this->getSettings(['message' => 'custom.key']), 'sv'),
    );
  }

  /**
   * The difference between the sums can be interpolated.
   */
  public function testDifference(): void {
    $formData = $this->getFormData(['minors' => 360], ['women' => '349,5']);

    $this->assertSame(
      ['step.genders.total' => 'Ero on 10,5 liikkujaa'],
      (new SumMatchValidator())->validate($formData, $this->getSettings(['message' => 'difference.key']), 'fi'),
    );
  }

  /**
   * Rules where every field is empty or missing pass.
   */
  public function testEmptyFieldsPass(): void {
    $formData = $this->getFormData(['minors' => '', 'adults' => ' '], []);

    $this->assertSame([], (new SumMatchValidator())->validate($formData, $this->getSettings(), 'fi'));
    $this->assertSame([], (new SumMatchValidator())->validate([], $this->getSettings(), 'fi'));
  }

  /**
   * Rules default their fields to sourceFields, or the field itself.
   */
  public function testGetRules(): void {
    $uiSchema = [
      'step' => [
        'genders' => [
          'total' => [
            'ui:options' => ['sourceFields' => self::GENDER_FIELDS],
            'misc:sum-match' => ['compareTo' => self::AGE_FIELDS],
          ],
        ],
        'ages' => [
          'minors' => ['misc:sum-match' => ['compareTo' => ['step.ages.adults']]],
          'adults' => [
            'misc:sum-match' => [
              'fields' => ['step.ages.seniors'],
              'compareTo' => ['step.ages.minors'],
              'message' => 'custom.key',
            ],
          ],
        ],
        'ui:order' => ['genders', 'ages'],
      ],
    ];

    $this->assertSame([
      'step.genders.total' => [
        'fields' => self::GENDER_FIELDS,
        'compareTo' => self::AGE_FIELDS,
        'message' => NULL,
      ],
      'step.ages.minors' => [
        'fields' => ['step.ages.minors'],
        'compareTo' => ['step.ages.adults'],
        'message' => NULL,
      ],
      'step.ages.adults' => [
        'fields' => ['step.ages.seniors'],
        'compareTo' => ['step.ages.minors'],
        'message' => 'custom.key',
      ],
    ], (new SumMatchValidator())->getRules($uiSchema));
  }

}
