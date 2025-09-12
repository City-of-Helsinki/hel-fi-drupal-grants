<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\Tests\grants_application\Kernel\KernelTestBase;

/**
 * Tests the ApplicationMetadataForm.
 *
 * @coversDefaultClass \Drupal\grants_application\Form\ApplicationMetadataForm
 * @group grants_application
 */
final class ApplicationMetadataFormTest extends KernelTestBase {

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

    // Install required modules, schemas and config.
    $this->enableModules(['options']);
    $this->installConfig(['system', 'datetime']);
    $this->installEntitySchema('application_metadata');

    // Point the service to tests/fixtures/ paths for test data.
    $this->fixturesDir = dirname(__DIR__, 3) . '/fixtures';

    $this->service = new FormSettingsService(
      $this->container->get('entity_type.manager'),
      $this->container->get('extension.list.module'),
      $this->container->get('language_manager'),
      $this->fixturesDir,
      $this->fixturesDir,
    );

    // Override the real service so forms resolve this one.
    $this->container->set(FormSettingsService::class, $this->service);
    \Drupal::setContainer($this->container);
  }

  /**
   * Tests the form build process and structure.
   */
  public function testBuildForm(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('application_metadata');
    $entity = $storage->create();

    /** @var \Drupal\Core\Entity\EntityFormBuilderInterface $form_builder */
    $form_builder = $this->container->get('entity.form_builder');

    $form = $form_builder->getForm($entity, 'default');

    // Verify form structure and default values.
    $this->assertArrayHasKey('label', $form);
    $this->assertArrayHasKey('application_type', $form);
    $this->assertArrayHasKey('application_type_id', $form);
    $this->assertArrayHasKey('application_open', $form);
    $this->assertArrayHasKey('application_close', $form);

    // Check read-only attributes.
    $this->assertContains('is-read-only', $form['label']['widget'][0]['value']['#attributes']['class']);
    $this->assertContains('is-read-only', $form['application_type']['widget'][0]['value']['#attributes']['class']);
    $this->assertContains('is-read-only', $form['application_type_id']['widget'][0]['value']['#attributes']['class']);

    // Check revision checkbox is enabled by default.
    $this->assertTrue($form['revision']['#default_value']);
  }

  /**
   * Tests form validation and submit.
   */
  public function testFormValidationAndSubmit(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('application_metadata');
    $entity = $storage->create();

    // Get the form object from the entity type manager.
    $form_builder = $this->container->get('form_builder');
    $form_object = $this->container->get('entity_type.manager')
      ->getFormObject('application_metadata', 'default')
      ->setEntity($entity);

    // Test with empty values. This should fail in form validation.
    $form_state = new FormState();
    $form_builder->buildForm($form_object, $form_state);
    $form_state->setUserInput([]);
    $form_builder->submitForm($form_object, $form_state);
    $this->assertTrue($form_state->hasAnyErrors(), 'Form validation should fail with empty required fields.');

    // Test with valid values.
    $form_state = new FormState();
    $form = $form_builder->buildForm($form_object, $form_state);

    // Use the test123application ID from our fixtures.
    $form_state->setValue('application_type_select', '123');

    // These values should be set by the form's JavaScript based on
    // application_type_select. But for the test, we need to set them directly.
    $form_state->setValue('label', [['value' => 'Test Application']]);
    $form_state->setValue('application_type', [['value' => 'TEST123APPLICATION']]);
    $form_state->setValue('application_type_id', [['value' => '123']]);
    $form_state->setValue('application_industry', 'TESTINDUSTRY');

    // Set multi-value fields.
    $form_state->setValueForElement($form['applicant_types']['widget'], ['test_applicant_type']);
    $form_state->setValueForElement($form['application_subvention_type']['widget'], ['1']);
    $form_state->setValueForElement($form['application_acting_years']['widget'], ['2025']);

    // Set date fields.
    $form_state->setValueForElement($form['application_open']['widget'][0]['value'], [
      'date' => '2025-01-01',
      'time' => '00:00:00',
    ]);
    $form_state->setValueForElement($form['application_close']['widget'][0]['value'], [
      'date' => '2025-12-31',
      'time' => '23:59:59',
    ]);

    // Set boolean fields.
    $form_state->setValueForElement($form['application_continuous']['widget']['value'], TRUE);
    $form_state->setValueForElement($form['disable_copying']['widget']['value'], FALSE);

    // Simulate a submit button click; tell Form API which button was "clicked".
    $actions = $form['actions'];
    $submit_key = isset($actions['save'])
      ? 'save' :
      (isset($actions['submit']) ? 'submit' : NULL);
    $this->assertNotNull($submit_key, 'Expected save/submit button in form actions.');
    $form_state->setTriggeringElement($actions[$submit_key]);

    // Set the 'op' value (what FAPI uses from POST).
    $form_state->setValue('op', (string) $actions[$submit_key]['#value']);

    // Submit the form.
    $form_builder->submitForm($form_object, $form_state);

    // Check for form validation errors.
    $errors = $form_state->getErrors();
    if (!empty($errors)) {
      foreach ($errors as $field => $error) {
        echo "\nForm error on $field: " . (string) $error;
      }
    }
    $this->assertFalse($form_state->hasAnyErrors(), 'Form submit should pass with valid values.');

    // Verify the entity was saved.
    $entities = $storage->loadByProperties(['label' => 'Test Application']);
    $this->assertNotEmpty($entities, 'Form submit could not save the application metadata entity.');

    /** @var \Drupal\grants_application\Entity\ApplicationMetadata $entity */
    $entity = reset($entities);

    // Verify field values match what we submitted.
    $this->assertEquals('TEST123APPLICATION', $entity->get('application_type')->value);
    $this->assertEquals('123', $entity->get('application_type_id')->value);
    $this->assertTrue((bool) $entity->get('application_continuous')->value);
    $this->assertFalse((bool) $entity->get('disable_copying')->value);
  }

  /**
   * Tests the form's attached libraries and settings.
   */
  public function testFormAttachments(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('application_metadata');
    $entity = $storage->create();
    $form_builder = $this->container->get('entity.form_builder');
    $form = $form_builder->getForm($entity, 'default');

    // Verify attached library.
    $this->assertArrayHasKey('#attached', $form);
    $this->assertContains('grants_application/application_metadata_form', $form['#attached']['library']);

    // Verify the attached drupalSettings variables.
    $this->assertArrayHasKey('drupalSettings', $form['#attached']);
    $this->assertArrayHasKey('grants_application', $form['#attached']['drupalSettings']);
    $this->assertArrayHasKey('application_types', $form['#attached']['drupalSettings']['grants_application']);

    // The form should attach application types in the format
    // expected by the frontend.
    $expected_types = [
      '123' => 'Test 123 application',
    ];
    $this->assertEquals(
      $expected_types,
      $form['#attached']['drupalSettings']['grants_application']['application_types'],
      'Application types in drupalSettings match expected structure'
    );
  }

}
