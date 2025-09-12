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

}
