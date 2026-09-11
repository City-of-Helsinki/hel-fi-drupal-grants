<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_industries\Kernel\Hook;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the form alterations of grants_industries.
 */
#[Group('grants_industries')]
final class FormHooksTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'language',
    'grants_industries',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installConfig(['user']);

    foreach (['admin', 'grants_admin', 'helsinkiprofiili'] as $id) {
      Role::create(['id' => $id, 'label' => $id])->save();
    }

    $this->setUpCurrentUser();
  }

  /**
   * Tests that the alter runs after other form alters.
   */
  public function testItRunsAfterEveryOtherFormAlter(): void {
    $order = [];
    $this->container->get('module_handler')
      ->invokeAllWith('form_alter', function (callable $hook, string $module) use (&$order): void {
        $order[] = $module;
      });

    $this->assertContains('language', $order, 'Expected another form alter to compare the order against.');
    $this->assertSame('grants_industries', end($order), 'Industry form alter should run last.');
  }

  /**
   * Tests that webform settings are hidden from users without an admin role.
   */
  public function testItHidesWebformSettingsFromNonAdmins(): void {
    $this->setCurrentUser($this->createUserWithRoles(['helsinkiprofiili']));

    $form = $this->alter($this->webformSettingsForm(), 'webform_settings_form');

    foreach ([
      'general_settings',
      'page_settings',
      'ajax_settings',
      'author_information',
      'share_settings',
      'advanced_settings',
    ] as $group) {
      $this->assertFalse($form[$group]['#access'], "Setting group $group should be hidden.");
    }
    foreach ([
      'applicationTypeSelect',
      'applicationType',
      'applicationTypeID',
      'applicationIndustry',
      'applicantTypes',
      'applicationTypeTerms',
    ] as $setting) {
      $this->assertTrue($form['third_party_settings']['grants_metadata'][$setting]['#disabled'], "Third party setting $setting should be disabled.");
    }
  }

  /**
   * Tests that webform settings stay editable for users with an admin role.
   */
  public function testItLeavesWebformSettingsEditableForAdmins(): void {
    $this->setCurrentUser($this->createUserWithRoles(['grants_admin']));

    $form = $this->alter($this->webformSettingsForm(), 'webform_settings_form');

    $this->assertArrayNotHasKey('#access', $form['general_settings'], 'Setting group should stay visible.');
    $this->assertArrayNotHasKey('#disabled', $form['third_party_settings']['grants_metadata']['applicationType'], 'Third party setting should stay editable.');
  }

  /**
   * Tests that the industry field is locked for non admin profile users.
   */
  public function testItDisablesTheIndustryFieldForProfileUsers(): void {
    $this->setCurrentUser($this->createUserWithRoles(['helsinkiprofiili']));

    $form = $this->alter($this->userForm(), 'user_form');

    $this->assertTrue($form['field_industry']['#disabled'], 'Industry field should be disabled.');
  }

  /**
   * Tests that the industry field stays editable for admins.
   */
  public function testItLeavesTheIndustryFieldEditableForAdmins(): void {
    $this->setCurrentUser($this->createUserWithRoles(['helsinkiprofiili', 'grants_admin']));

    $form = $this->alter($this->userForm(), 'user_form');

    $this->assertArrayNotHasKey('#disabled', $form['field_industry'], 'Industry field should stay editable.');
  }

  /**
   * Tests that the industry field is untouched without the profile role.
   */
  public function testItIgnoresUsersWithoutTheProfileRole(): void {
    $this->setCurrentUser($this->createUserWithRoles([]));

    $form = $this->alter($this->userForm(), 'user_form');

    $this->assertArrayNotHasKey('#disabled', $form['field_industry'], 'Industry field should stay editable.');
  }

  /**
   * Run every form alter against the given form.
   *
   * @param array $form
   *   The form to alter.
   * @param string $formId
   *   The form id.
   *
   * @return array
   *   Returns the altered form.
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  private function alter(array $form, string $formId): array {
    $formState = new FormState();
    $this->container->get('module_handler')->alter('form', $form, $formState, $formId);
    return $form;
  }

  /**
   * Create a user with the given roles.
   *
   * @param array $roles
   *   The role ids to assign.
   *
   * @return \Drupal\user\UserInterface
   *   Returns the created user.
   *
   * @phpstan-param array<int, string> $roles
   */
  private function createUserWithRoles(array $roles): UserInterface {
    return $this->createUser([], NULL, FALSE, ['roles' => $roles]);
  }

  /**
   * Build the parts of the webform settings form that the alter touches.
   *
   * @return array
   *   Returns the form.
   *
   * @phpstan-return array<string, mixed>
   */
  private function webformSettingsForm(): array {
    return [
      'general_settings' => [],
      'page_settings' => [],
      'ajax_settings' => [],
      'author_information' => [],
      'share_settings' => [],
      'advanced_settings' => [],
      'third_party_settings' => [
        'grants_metadata' => [
          'applicationTypeSelect' => [],
          'applicationType' => [],
          'applicationTypeID' => [],
          'applicationIndustry' => [],
          'applicantTypes' => [],
          'applicationTypeTerms' => [],
        ],
      ],
    ];
  }

  /**
   * Build the part of the user form that the alter touches.
   *
   * @return array
   *   Returns the form.
   *
   * @phpstan-return array<string, mixed>
   */
  private function userForm(): array {
    return ['field_industry' => []];
  }

}
