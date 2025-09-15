<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel\Form;

use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\Tests\grants_application\Kernel\KernelTestBase;

/**
 * Tests the FormSettingsService.
 *
 * @group grants_application
 */
final class FormSettingsServiceTest extends KernelTestBase {

  /**
   * The form settings service being tested.
   */
  private FormSettingsService $service;

  /**
   * The directory path containing test fixtures.
   */
  private string $fixturesDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Point the service to tests/fixtures/ paths for test data.
    $this->fixturesDir = dirname(__DIR__, 3) . '/fixtures';

    $this->service = new FormSettingsService(
      $this->container->get('entity_type.manager'),
      $this->container->get('extension.list.module'),
      $this->container->get('language_manager'),
      $this->fixturesDir,
      $this->fixturesDir,
    );
  }

  /**
   * Tests retrieving form configuration with and without section specification.
   */
  public function testGetFormConfig(): void {
    // Test getting the entire form types configuration.
    $config = $this->service->getFormConfig('form_types');
    $this->assertIsArray($config);
    $this->assertArrayHasKey('123', $config);

    // Test getting a specific section from the configuration.
    $section = $this->service->getFormConfig('form_types', '123');
    $this->assertIsArray($section);
    $this->assertSame('TESTAPPLICATION', $section['code']);
    $this->assertSame('TEST123APPLICATION', $section['id']);
    $this->assertArrayHasKey('labels', $section);
    $this->assertIsArray($section['labels']);

    // Test the fallback to entire config if section is not found.
    $this->assertArrayHasKey('123',
      $this->service->getFormConfig('form_types', 'non_existing_section')
    );
  }

  /**
   * Tests application window status based on fixture configuration.
   */
  public function testIsApplicationOpenFromFixtures(): void {
    $this->assertTrue(
      $this->service->isApplicationOpen(123),
      'Application window should be open according to test fixture configuration'
    );
  }

  /**
   * Tests that an unknown form type ID throws an exception.
   */
  public function testGetFormSettingsUnknownIdThrows(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->service->getFormSettings(999999);
  }

  /**
   * Tests that a single section's labels are correctly retrieved.
   */
  public function testGetLabelsSingleSectionDefaultLanguage(): void {
    $serviceEn = $this->createServiceWithLanguage('en');

    $applicationTypes = $serviceEn->getFormConfig('form_types', 'application_types');
    $single = $applicationTypes['123'];

    $label = $serviceEn->getLabels($single);
    $this->assertSame('Test 123 application', $label);
  }

  /**
   * Tests that multiple items with labels are correctly mapped to their labels.
   */
  public function testGetLabelsMultipleItemsReturnsMappedArray(): void {
    $serviceEn = $this->createServiceWithLanguage('en');

    $applicationTypes = $serviceEn->getFormConfig('form_types', 'application_types');
    $labels = $serviceEn->getLabels($applicationTypes);

    $this->assertIsArray($labels);
    $this->assertArrayHasKey('123', $labels);
    $this->assertSame('Test 123 application', $labels['123']);
  }

  /**
   * Tests that the method falls back to English when language is not available.
   */
  public function testGetLabelsFallsBackToEnglish(): void {
    $serviceIt = $this->createServiceWithLanguage('it');

    $applicationTypes = $serviceIt->getFormConfig('form_types', 'application_types');
    $single = $applicationTypes['123'];

    $label = $serviceIt->getLabels($single);
    $this->assertSame('Test 123 application', $label);
  }

  /**
   * Tests that the method falls back to 'und' when no other match is found.
   */
  public function testGetLabelsFallsBackToUnd(): void {
    $serviceFi = $this->createServiceWithLanguage('fi');
    $inline = ['labels' => ['und' => 'Undetermined label']];

    $this->assertSame('Undetermined label', $serviceFi->getLabels($inline));
  }

  /**
   * Tests that the method handles invalid entries in the input array.
   */
  protected function testGetLabelsMultipleItemsHandlesInvalidEntries(): void {
    $service = $this->createServiceWithLanguage('en');
    $section = [
      'valid' => ['labels' => ['en' => 'OK']],
      'invalid' => ['foo' => 'bar'],
    ];

    $labels = $service->getLabels($section);

    $this->assertSame('OK', $labels['valid']);
    $this->assertSame([], $labels['invalid']);
    $this->assertSame('', $service->getLabels(NULL));
  }

  /**
   * Tests that settings.json is loaded when not in production.
   */
  protected function testFormSettingsLoadsSettingsWhenNotProduction(): void {
    putenv('APP_ENV=dev');
    $this->createApplicationMetadataEntity();
    $settings = $this->service->getFormSettings(123)->toArray();

    // In non-production, settings.json fixture should be read.
    $this->assertArrayHasKey('title', $settings['settings']);
    $this->assertSame('Test application (fixture)', $settings['settings']['title']);
    putenv('APP_ENV');
  }

  /**
   * Tests that settings.json is not loaded when in production.
   */
  public function testFormSettingsDoesNotLoadSettingsInProduction(): void {
    putenv('APP_ENV=production');
    $this->createApplicationMetadataEntity();
    $settings = $this->service->getFormSettings(123)->toArray();

    // In production, settings.json should NOT be included.
    $this->assertArrayHasKey('title', $settings['settings']);
    $this->assertSame('Test application', $settings['settings']['title']);
    putenv('APP_ENV');
  }

  /**
   * Tests that an exception is thrown when settings are not found.
   */
  public function testFormSettings(): void {
    putenv('APP_ENV=production');
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Unable to load settings for form 123.');
    $this->service->getFormSettings(123);
    putenv('APP_ENV');
  }

  /**
   * Helper to create the service with a forced interface language.
   */
  private function createServiceWithLanguage(string $langcode): FormSettingsService {
    $language = new Language(['id' => $langcode]);

    $languageManager = $this->createMock(LanguageManagerInterface::class);
    $languageManager
      ->method('getCurrentLanguage')
      ->with($this->anything())
      ->willReturn($language);

    return new FormSettingsService(
      $this->container->get('entity_type.manager'),
      $this->container->get('extension.list.module'),
      $languageManager,
      $this->fixturesDir,
      $this->fixturesDir,
    );
  }

  /**
   * Helper function to create an application metadata entity.
   */
  private function createApplicationMetadataEntity(): void {
    // Create an entity with all fields populated.
    /** @var \Drupal\grants_application\Entity\ApplicationMetadata $entity */
    $entity = $this->container->get('entity_type.manager')
      ->getStorage('application_metadata')
      ->create([
        'label' => 'Test application',
        'status' => TRUE,
        'uid' => 1,
        'application_type_id' => 123,
      ]);
    $entity->save();
  }

}
