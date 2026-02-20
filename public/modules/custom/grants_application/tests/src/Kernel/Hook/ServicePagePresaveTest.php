<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel\Hook;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\grants_application\Kernel\KernelTestBase;

/**
 * Tests the Views relationship joining application_submission to content_lock.
 *
 * @group grants_application
 */
final class ServicePagePresaveTest extends KernelTestBase {

  protected static $modules = ['block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);

    $locale_tables = [
      'locales_source',
      'locales_target',
      'locales_location',
    ];
    $this->installSchema('locale', $locale_tables);

    $target_group_tid = $this->createTargetGroupTerm();

    // Resolve allowed values from the grants_application.module callbacks.
    $application_type = $this->firstAllowedValue('grants_application_application_type_allowed_values');
    $industry = $this->firstAllowedValue('grants_application_application_industry_allowed_values');

    $this->container->get('entity_type.manager')
      ->getStorage('application_metadata')
      ->create([
        'id' => 1,
        'label' => 'My Application Name',
        'status' => TRUE,
        'uid' => 1,
        'application_type_select' => $application_type,
        'application_type' => 'Some Type',
        'application_type_id' => '101',
        'form_identifier' => 'test-application',
        'application_industry' => $industry,
        'application_continuous' => TRUE,
        'disable_copying' => TRUE,
      ])->save();

    $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->create([
        'id' => 1,
        'type' => 'service',
        'uid' => 1,
        'title' => 'Kerneltest service page',
        'field_avustuslaji' => $industry,
        'field_target_group' => $target_group_tid,
        'field_application_continuous' => TRUE,
      ])->save();
  }

  /**
   * Test that pre save hook clears the fields.
   */
  public function testServicePagePreSaveHook(): void {
    $servicePage = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->load(1);

    // At this point the field should have a value.
    $this->assertFalse($servicePage->get('field_application_continuous')->isEmpty());

    $servicePage->set('field_react_form', 1);
    $servicePage->save();

    $servicePage = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->load(1);

    // Setting field_react_form should clear the webform-related fields.
    $this->assertFalse($servicePage->get('field_react_form')->isEmpty());
    // Check that one of the fields is emptied.
    $this->assertTrue($servicePage->get('field_application_continuous')->isEmpty());
  }

  /**
   * Picks the first key from an allowed values function.
   *
   * This method attempts to call a Drupal "allowed values" callback
   * and returns the first available key. If the function does not
   * exist or produces no values, it falls back to `'0'`.
   *
   * @param string $function
   *   The allowed values function name.
   *
   * @return string
   *   A key suitable for setting to a list_string field.
   */
  private function firstAllowedValue(string $function): string {
    // If the provided function name is not callable, return a default key.
    if (!function_exists($function)) {
      return '0';
    }

    // Provide a dummy storage definition.
    $fieldStorageDefinition = $this->createMock(FieldStorageDefinitionInterface::class);

    // Call the allowed values function.
    $values = (array) $function($fieldStorageDefinition, NULL);

    // Return the first key if values exist, otherwise fall back to '0'.
    return $values === [] ? '0' : (string) array_key_first($values);
  }

  private function createTargetGroupTerm(): int {
    $term = Term::create([
      'vid' => 'target_group',
      'name' => 'Youth',
    ]);
    $term->save();

    return (int) $term->id();
  }

}
