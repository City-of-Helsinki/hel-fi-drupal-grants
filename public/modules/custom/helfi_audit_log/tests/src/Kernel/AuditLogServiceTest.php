<?php

namespace Drupal\Tests\helfi_audit_log\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the AuditLogService.
 *
 * @group helfi_audit_log
 */
class AuditLogServiceTest extends KernelTestBase implements ServiceModifierInterface {
  /**
   * The service under test.
   *
   * @var \Drupal\helfi_audit_log\AuditLogServiceInterface
   */
  protected $auditLogService;
  /**
   * The modules to load to run the test.
   *
   * @var array
   */
  protected static $modules = [
    'helfi_audit_log',
    'helfi_audit_log_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('helfi_audit_log', ['helfi_audit_logs']);
    $this->auditLogService = \Drupal::service('helfi_audit_log.audit_log');
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $container
      ->getDefinition('helfi_audit_log.audit_log')
      ->setClass('Drupal\\helfi_audit_log_test\\AuditLogServiceTest');
  }

  /**
   * Test that message is passed all the way to audit log service.
   */
  public function testDatabaseWrite() {
    // Dispatch audit log event.
    $this->auditLogService->dispatchEvent([
      'key1' => 'value1',
      'key2' => 'value2',
    ]);
    // Get the values that was used to call audit log service.
    $dataBaseValues = $this->auditLogService->getValues();
    $this->assertEquals('DRUPAL', $dataBaseValues['origin']);
    $this->assertEquals('value1', $dataBaseValues['message']['key1']);
    $this->assertEquals('value2', $dataBaseValues['message']['key2']);
  }

}
