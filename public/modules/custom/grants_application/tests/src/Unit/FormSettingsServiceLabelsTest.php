<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the FormSettingsService::getLabels method.
 *
 * @group grants_application
 */
final class FormSettingsServiceLabelsTest extends UnitTestCase {

  /**
   * Builds a test instance of FormSettingsService with the specified language.
   *
   * @param string $langcode
   *   The language code to set for the test service.
   *
   * @return \Drupal\grants_application\Form\FormSettingsService
   *   A configured FormSettingsService instance for testing.
   */
  private function buildService(string $langcode): FormSettingsService {
    $reflectionClass = new \ReflectionClass(FormSettingsService::class);
    /** @var \Drupal\grants_application\Form\FormSettingsService $service */
    $service = $reflectionClass->newInstanceWithoutConstructor();

    // Mocks.
    $language = $this->createMock(LanguageInterface::class);
    $language->method('getId')->willReturn($langcode);

    $languageManager = $this->createMock(LanguageManagerInterface::class);
    $languageManager->method('getCurrentLanguage')->willReturn($language);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $moduleExtensionList = $this->createMock(ModuleExtensionList::class);

    // Inject promoted props.
    foreach ([
      'entityTypeManager' => $entityTypeManager,
      'moduleExtensionList' => $moduleExtensionList,
      'languageManager' => $languageManager,
    ] as $propName => $value) {
      $prop = $reflectionClass->getProperty($propName);
      $prop->setValue($service, $value);
    }

    // Avoid notices if ever accessed.
    $formTypes = $reflectionClass->getProperty('formTypes');
    $formTypes->setValue($service, [123 => ['id' => 'TEST123APPLICATION']]);

    return $service;
  }

  /**
   * Tests the getLabels with single label sections and fallback behavior.
   */
  public function testGetLabelsSingleWithFallbacks(): void {
    $service = $this->buildService('fi');

    $section = ['labels' => ['fi' => 'FI', 'en' => 'EN', 'und' => 'UND']];
    $this->assertSame('FI', $service->getLabels($section));

    $serviceSv = $this->buildService('sv');
    $this->assertSame('EN', $serviceSv->getLabels($section));

    $sectionUndOnly = ['labels' => ['und' => 'UND only']];
    $this->assertSame('UND only', $serviceSv->getLabels($sectionUndOnly));

    $sectionNoMatch = ['labels' => ['de' => 'DE']];
    $this->assertSame('', $service->getLabels($sectionNoMatch));

    $this->assertSame('', $service->getLabels(NULL));
  }

  /**
   * Tests the getLabels method with multiple label sections.
   *
   * Verifies that:
   * - The method correctly handles multiple sections with different
   *   language preferences.
   * - The array keys are preserved in the returned result.
   * - Language fallback works as expected.
   */
  public function testGetLabelsMultiplePreservesKeys(): void {
    $service = $this->buildService('fi');

    $section = [
      'a' => ['labels' => ['en' => 'A EN']],
      'b' => ['labels' => ['und' => 'B UND']],
      'c' => ['labels' => ['fi' => 'C FI', 'en' => 'C EN']],
    ];

    $expected = [
      'a' => 'A EN',
      'b' => 'B UND',
      'c' => 'C FI',
    ];

    $this->assertSame($expected, $service->getLabels($section));
  }

}
