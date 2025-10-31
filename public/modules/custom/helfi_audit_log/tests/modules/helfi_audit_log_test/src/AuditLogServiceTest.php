<?php

namespace Drupal\helfi_audit_log_test;

use Drupal\helfi_audit_log\AuditLogService;

/**
 * AuditLog service with mocked logOperation function.
 */
class AuditLogServiceTest extends AuditLogService {

  /**
   * Operation that logs the message to database.
   *
   * @param array $message
   *   Message that is merged with generic data and logged to database.
   * @param string $origin
   *   String identifying the source for the audit log message.
   */
  public function logOperation(array $message, string $origin): void {
    $this->message = $message;
    $this->origin = $origin;
  }

  /**
   * Helper funtion to return values used in logOperation call.
   */
  public function getValues(): array {
    return [
      'message' => $this->message,
      'origin' => $this->origin,
    ];
  }

}
