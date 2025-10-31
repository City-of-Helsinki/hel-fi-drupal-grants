<?php

namespace Drupal\Tests\helfi_audit_log\Unit;

use Drupal\helfi_audit_log\Event\AuditLogEvent;
use Drupal\Tests\UnitTestCase;

/**
 * Tests AuditLogEvent object.
 *
 * @coversDefaultClass \Drupal\helfi_audit_log\Event\AuditLogEvent
 * @group helfi_audit_log
 */
class AuditLogEventTest extends UnitTestCase {

  /**
   * @covers ::__construct
   * @covers ::getOrigin
   * @covers ::getMessage
   * @covers ::isValid
   * @covers \Drupal\helfi_audit_log\Event\AuditLogEvent::__construct
   */
  public function testCreateEvent() : void {
    $event = new AuditLogEvent(['message']);
    $this->assertEquals($event->getOrigin(), 'DRUPAL');
    $this->assertEquals($event->isValid(), TRUE);
    $this->assertEquals($event->getMessage()[0], 'message');
  }

  /**
   * @covers ::__construct
   * @covers ::getOrigin
   * @covers ::setOrigin
   * @covers \Drupal\helfi_audit_log\Event\AuditLogEvent::__construct
   */
  public function testModifyEventOrigin() : void {
    $event = new AuditLogEvent(['message']);
    $this->assertEquals($event->getOrigin(), 'DRUPAL');
    $event->setOrigin('TEST-MODIFY-EVENT');
    $this->assertEquals($event->getOrigin(), 'TEST-MODIFY-EVENT');
  }

  /**
   * @covers ::__construct
   * @covers ::getMessage
   * @covers ::setMessage
   * @covers \Drupal\helfi_audit_log\Event\AuditLogEvent::__construct
   */
  public function testModifyEventMessage() : void {
    $event = new AuditLogEvent(['message']);
    $this->assertArrayHasKey(0, $event->getMessage());
    $this->assertCount(1, $event->getMessage());
    $newMessage = [
      'key1' => 'value1',
      'key2' => 'value2',
    ];
    $event->setMessage($newMessage);
    $this->assertArrayNotHasKey(0, $event->getMessage());
    $this->assertArrayHasKey('key1', $event->getMessage());
    $this->assertEquals('value1', $event->getMessage()['key1']);
    $this->assertArrayHasKey('key2', $event->getMessage());
    $this->assertEquals('value2', $event->getMessage()['key2']);
    $this->assertCount(2, $event->getMessage());
  }

  /**
   * @covers ::__construct
   * @covers ::setValid
   * @covers ::isValid
   * @covers \Drupal\helfi_audit_log\Event\AuditLogEvent::__construct
   */
  public function testModifyEventValidity() : void {
    $event = new AuditLogEvent(['message']);
    $this->assertEquals($event->isValid(), TRUE);
    $event->setValid(FALSE);
    $this->assertEquals($event->isValid(), FALSE);
  }

}
