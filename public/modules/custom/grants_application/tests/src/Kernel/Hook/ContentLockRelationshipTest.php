<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel\Hook;

use Drupal\Tests\grants_application\Kernel\KernelTestBase;
use Drupal\user\Entity\User;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Tests the Views relationship joining application_submission to content_lock.
 *
 * @group grants_application
 */
final class ContentLockRelationshipTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('application_submission');
    $this->installSchema('content_lock', ['content_lock']);
  }

  /**
   * Tests that the relationship joins by entity_id + entity_type.
   */
  public function testRelationshipJoinAndResults(): void {
    $u1 = User::create(['name' => 'u1']);
    $u1->save();
    $u2 = User::create(['name' => 'u2']);
    $u2->save();

    $storage = $this->container
      ->get('entity_type.manager')
      ->getStorage('application_submission');

    // Create two submissions.
    $s1 = $storage->create(['uid' => $u1->id(), 'status' => 1]);
    $s1->save();

    $s2 = $storage->create(['uid' => $u2->id(), 'status' => 1]);
    $s2->save();

    // Insert locks.
    $db = $this->container->get('database');

    $db->insert('content_lock')->fields([
      'entity_id' => (int) $s1->id(),
      'entity_type' => 'application_submission',
      'form_op' => '*',
      'langcode' => 'und',
      'uid' => (int) $u1->id(),
      'timestamp' => 1770899065,
    ])->execute();

    $db->insert('content_lock')->fields([
      'entity_id' => (int) $s2->id(),
      'entity_type' => 'application_submission',
      'form_op' => '*',
      'langcode' => 'und',
      'uid' => (int) $u2->id(),
      'timestamp' => 1770902113,
    ])->execute();

    // A lock that must not join to submissions.
    $db->insert('content_lock')->fields([
      'entity_id' => 999,
      'entity_type' => 'node',
      'form_op' => '*',
      'langcode' => 'und',
      'uid' => (int) $u1->id(),
      'timestamp' => 1770029265,
    ])->execute();

    // Define a view that uses the relationship from hook_views_data():
    $view = View::create([
      'id' => 'test_locked_applications',
      'label' => 'Test Locked Applications',
      'base_table' => 'application_submission',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_title' => 'Default',
          'position' => 0,
          'display_options' => [
            'relationships' => [
              'content_lock_for_submission' => [
                'id' => 'content_lock_for_submission',
                'table' => 'application_submission',
                'field' => 'content_lock_for_submission',
                'plugin_id' => 'standard',
                'required' => TRUE,
              ],
            ],
            'fields' => [
              'id' => [
                'id' => 'id',
                'table' => 'application_submission',
                'field' => 'id',
                'plugin_id' => 'numeric',
              ],
              'timestamp' => [
                'id' => 'timestamp',
                'table' => 'content_lock',
                'field' => 'timestamp',
                'relationship' => 'content_lock_for_submission',
                'plugin_id' => 'date',
              ],
            ],
            'sorts' => [
              'timestamp' => [
                'id' => 'timestamp',
                'table' => 'content_lock',
                'field' => 'timestamp',
                'relationship' => 'content_lock_for_submission',
                'plugin_id' => 'date',
                'order' => 'DESC',
              ],
            ],
          ],
        ],
      ],
    ]);
    $view->save();

    $executable = Views::getView('test_locked_applications');
    $this->assertNotNull($executable);

    $executable->setDisplay('default');
    $executable->execute();

    // Assert that results have exactly 2 rows, matches the two submissions
    // and is sorted by timestamp DESC.
    $this->assertCount(2, $executable->result);
    // @phpstan-ignore property.notFound
    $this->assertSame((string) $s2->id(), (string) $executable->result[0]->id);
    // @phpstan-ignore property.notFound
    $this->assertSame((string) $s1->id(), (string) $executable->result[1]->id);

    $executable->build();
    $sql = (string) $executable->query->query();

    // Assert that the SQL contains join on entity_id and the entity_type.
    $this->assertStringContainsString('content_lock', $sql);
    $this->assertStringContainsString('entity_id', $sql);
    $this->assertStringContainsString('entity_type', $sql);
    $this->assertStringContainsString('application_submission', $sql);
  }

}
