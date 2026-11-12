<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel\Hook;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\grants_application\Entity\ApplicationMetadata;
use Drupal\Tests\grants_application\Kernel\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\UserInterface;
use Drupal\views\Views;

/**
 * Tests the application metadata hooks.
 *
 * @group grants_application
 */
final class ApplicationMetadataHooksTest extends KernelTestBase {

  use UserCreationTrait;

  private const PERMISSIONS = [
    'access application_metadata overview',
    'view application_metadata',
    'update any application_metadata',
    'create application_metadata',
  ];

  /**
   * The application metadata entities.
   *
   * @var array<ApplicationMetadata>
   */
  private array $metadata;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->createUserIndustryField();
    $roles = [
      'grants_producer_industry' => 'Grants producer INDUSTRY',
      'grants_producer' => 'Grants producer',
    ];

    foreach ($roles as $id => $label) {
      Role::create([
        'id' => $id,
        'label' => $label,
        'permissions' => self::PERMISSIONS,
      ])->save();
    }

    $owner = $this->setUpCurrentUser();
    foreach (['KUVA', 'SOTE', 'KASKO'] as $industry) {
      $entity = ApplicationMetadata::create([
        'label' => "$industry application",
        'status' => TRUE,
        'uid' => $owner->id(),
        'application_type' => "{$industry}_TYPE",
        'application_industry' => $industry,
      ]);
      $entity->save();

      $this->metadata[] = $entity;
    }
  }

  /**
   * Test industry user access on application metadata view.
   */
  public function testIndustryUserAccess(): void {
    $industryUser = $this->createIndustryUser('KUVA');

    $view = Views::getView('application_metadata');

    $this->assertTrue(
      $view->access('page_1', $industryUser)
    );

    $this->setCurrentUser($industryUser);
    $view->setDisplay('page_1');
    $view->execute();

    $this->assertCount(1, $view->result);

    $operations = ['view', 'update', 'delete', 'create'];

    foreach ($this->metadata as $entity) {
      if ($entity->getMetadata()['application_industry'] === 'KUVA') {
        foreach ($operations as $operation) {
          $this->assertTrue(
            $entity->access($operation, $industryUser),
            "User has permission to $operation application metadata."
          );
        }
      }
      else {
        foreach ($operations as $operation) {
          $this->assertFalse(
            $entity->access($operation, $industryUser),
            "User does not have permission to $operation application metadata."
          );
        }
      }
    }
  }

  /**
   * Field creation.
   */
  private function createUserIndustryField(): void {
    FieldStorageConfig::create([
      'field_name' => 'field_industry',
      'entity_type' => 'user',
      'type' => 'list_string',
      'cardinality' => FieldStorageConfig::CARDINALITY_UNLIMITED,
      'settings' => [
        'allowed_values' => [
          'KUVA' => 'KUVA',
          'SOTE' => 'SOTE',
          'KASKO' => 'KASKO',
        ],
      ],
    ])->save();

    FieldConfig::create([
      'field_name' => 'field_industry',
      'entity_type' => 'user',
      'bundle' => 'user',
      'label' => 'Industry',
    ])->save();
  }

  /**
   * Create the industry user.
   *
   * @param string $industry
   *   The industry name (KUVA, KASKO, SOTE etc.).
   * @param array<string> $roles
   *   The role of the user.
   *
   * @return \Drupal\user\UserInterface
   *   The user.
   */
  private function createIndustryUser(string $industry, array $roles = ['grants_producer_industry']): UserInterface {
    return $this->createUser([], NULL, FALSE, [
      'roles' => $roles,
      'field_industry' => [$industry],
    ]);
  }

}
