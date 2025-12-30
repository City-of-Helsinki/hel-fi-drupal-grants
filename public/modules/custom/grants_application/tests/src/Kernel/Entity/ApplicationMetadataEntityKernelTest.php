<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Tests\grants_application\Kernel\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * @coversDefaultClass \Drupal\grants_application\Entity\ApplicationMetadata
 *
 * @group grants_application
 */
final class ApplicationMetadataEntityKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  // phpcs:ignore
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a target group vocabulary required by the field handler settings.
    $vocab = Vocabulary::create([
      'vid' => 'target_group',
      'name' => 'Target group',
    ]);
    $vocab->save();
  }

  /**
   * Creates a target group term and returns its ID.
   */
  private function createTargetGroupTerm(): int {
    $term = Term::create([
      'vid' => 'target_group',
      'name' => 'Youth',
    ]);
    $term->save();

    return (int) $term->id();
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

  /**
   * @covers ::getTranslation
   * @covers ::getUntranslated
   * @covers ::getMetadata
   */
  public function testEntityStoresAndExposesMetadata(): void {
    $target_group_tid = $this->createTargetGroupTerm();

    // Resolve allowed values from the grants_application.module callbacks.
    $application_type = $this->firstAllowedValue('grants_application_application_type_allowed_values');
    $industry = $this->firstAllowedValue('grants_application_application_industry_allowed_values');

    // Multi-value fields should accept arrays of keys.
    $applicant_types = [
      $this->firstAllowedValue('grants_application_applicant_types_allowed_values'),
    ];
    $subvention_types = [
      $this->firstAllowedValue('grants_application_application_subvention_types_allowed_values'),
      '2',
    ];
    $acting_years = [
      $this->firstAllowedValue('grants_application_application_acting_years_allowed_values'),
      '2030',
    ];

    // Generate dates and format them for database storage.
    $open = new DrupalDateTime('2025-01-02 03:04:05', new \DateTimeZone('UTC'));
    $close = new DrupalDateTime('2025-02-03 04:05:06', new \DateTimeZone('UTC'));
    $open_date = $open->format('Y-m-d\TH:i:s');
    $close_date = $close->format('Y-m-d\TH:i:s');

    // Create an entity with all fields populated.
    /** @var \Drupal\grants_application\Entity\ApplicationMetadata $entity */
    $entity = $this->container->get('entity_type.manager')
      ->getStorage('application_metadata')
      ->create([
        'label' => 'My Application Name',
        'status' => TRUE,
        'uid' => 1,
        'application_type_select' => $application_type,
        'application_type' => 'Some Type',
        'application_type_id' => '101',
        'application_industry' => $industry,
        'application_target_group' => $target_group_tid,
        'application_open' => $open_date,
        'application_close' => $close_date,
        'applicant_types' => array_map(static fn(string $v) => ['value' => $v], $applicant_types),
        'application_subvention_type' => array_map(static fn(string $v) => ['value' => $v], $subvention_types),
        'application_acting_years' => array_map(static fn(string $v) => ['value' => $v], $acting_years),
        'application_continuous' => TRUE,
        'disable_copying' => TRUE,
      ]);

    // Saving should create a first revision (show_revision_ui = TRUE).
    $entity->save();
    $this->assertNotNull($entity->getRevisionId());
    $this->assertTrue($entity->isPublished());
    $this->assertSame(1, (int) $entity->getOwnerId());

    // The entity is not translatable, so getTranslation()/getUntranslated()
    // must return same instance.
    $this->assertSame($entity, $entity->getTranslation('fi'));
    $this->assertSame($entity, $entity->getUntranslated());

    // The getMetadata() method must convert types exactly as implemented.
    $metadata = $entity->getMetadata();

    $this->assertSame('My Application Name', $metadata['title']);
    $this->assertSame('Some Type', $metadata['description']);
    $this->assertSame('Some Type', $metadata['application_type']);
    $this->assertSame(101, $metadata['application_type_id']);
    $this->assertSame($industry, $metadata['application_industry']);
    $this->assertSame($open_date, $metadata['application_open']);
    $this->assertSame($close_date, $metadata['application_close']);
    $this->assertSame($applicant_types, $metadata['applicant_types']);
    $this->assertSame(array_map('intval', $subvention_types), $metadata['subvention_type']);
    $this->assertSame(array_map('intval', $acting_years), $metadata['acting_years']);
    $this->assertSame($target_group_tid, $metadata['target_group']);
    $this->assertTrue($metadata['continuous']);
    $this->assertTrue($metadata['disable_copy']);
  }

  /**
   * @covers ::getMetadata
   */
  public function testGetMetadataHandlesEmptyValues(): void {
    /** @var \Drupal\grants_application\Entity\ApplicationMetadata $entity */
    $entity = $this->container->get('entity_type.manager')
      ->getStorage('application_metadata')
      ->create([
        'label' => 'Empty Case',
        'application_type' => '',
        'application_type_id' => NULL,
        'application_industry' => '',
        'application_open' => NULL,
        'application_close' => NULL,
      ]);

    $entity->save();
    $metadata = $entity->getMetadata();
    $this->assertSame([], $metadata['applicant_types']);
    $this->assertSame([], $metadata['subvention_type']);
    $this->assertSame([], $metadata['acting_years']);
    $this->assertNull($metadata['target_group']);
  }

}
