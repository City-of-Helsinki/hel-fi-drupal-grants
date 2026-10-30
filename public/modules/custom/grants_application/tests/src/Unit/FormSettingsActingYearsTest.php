<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\grants_application\Form\FormSettings;
use Drupal\Tests\UnitTestCase;

/**
 * Tests restricting the acting year field to the configured acting years.
 *
 * @coversDefaultClass \Drupal\grants_application\Form\FormSettings
 * @group grants_application
 */
final class FormSettingsActingYearsTest extends UnitTestCase {

  /**
   * Loads a form schema from the fixtures.
   *
   * @return array<string, mixed>
   *   The schema.
   */
  private function loadSchema(): array {
    $contents = file_get_contents(__DIR__ . '/../../../fixtures/kasvatus_ja_koulutus_yleisavustu/schema.json');
    $this->assertIsString($contents);
    return json_decode($contents, TRUE, flags: JSON_THROW_ON_ERROR);
  }

  /**
   * The acting years become the allowed values of the acting year field.
   */
  public function testActingYearsAreSetAsEnum(): void {
    $settings = new FormSettings(['acting_years' => [2027, 2028]], $this->loadSchema(), [], []);

    foreach ([$settings->getSchema(), $settings->toArray()['schema']] as $schema) {
      $this->assertSame(
        ['2027', '2028'],
        $schema['definitions']['grant_info_step']['properties']['acting_year_section']['properties']['acting_year']['enum'],
      );
    }
  }

  /**
   * The acting year of a submitted application is added to the settings.
   */
  public function testWithActingYearsOf(): void {
    $settings = new FormSettings(['acting_years' => [2027], 'title' => 'Test'], $this->loadSchema(), [], []);
    $formData = [
      'grant_info_step' => [
        'acting_year_section' => ['acting_year' => '2026'],
      ],
    ];

    $withYears = $settings->withActingYearsOf($formData);
    $array = $withYears->toArray();

    $this->assertSame([2026, 2027], $withYears->getActingYears());
    $this->assertSame([2026, 2027], $array['settings']['acting_years']);
    $this->assertSame('Test', $array['settings']['title']);
    $this->assertSame(
      ['2026', '2027'],
      $array['schema']['definitions']['grant_info_step']['properties']['acting_year_section']['properties']['acting_year']['enum'],
    );

    // The original settings are not changed.
    $this->assertSame([2027], $settings->getActingYears());

    // A year that is already allowed, or a missing one, adds nothing.
    $formData['grant_info_step']['acting_year_section']['acting_year'] = '2027';
    $this->assertSame([2027], $settings->withActingYearsOf($formData)->getActingYears());
    $this->assertSame([2027], $settings->withActingYearsOf([])->getActingYears());
  }

  /**
   * The schema is left as is when no acting years are configured.
   */
  public function testSchemaIsUnchangedWithoutActingYears(): void {
    $schema = $this->loadSchema();

    $this->assertSame($schema, (new FormSettings([], $schema, [], []))->getSchema());
    $this->assertSame($schema, (new FormSettings(['acting_years' => []], $schema, [], []))->getSchema());
  }

}
