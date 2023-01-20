<?php

namespace Drupal\grants_audit_log;

use Drupal\helfi_audit_log\AuditLogProvider;

/**
 *
 */
class GrantsAuditLogProvider extends AuditLogProvider {

  const AUDIT_LOG_PROVIDER_ORIGIN = 'HELFI-GRANTS';

  public function getOrigin(): string {
    return self::AUDIT_LOG_PROVIDER_ORIGIN;
  }
  
  /**
   *
   */
  public function getLogStructure(): array {
    return [
      'operation' => 1,
      'status' => 1,
      'target' => [
        'id' => 1,
        'type' => 1,
        'name' => 1,
        'diff' => 1,
      ],
    ];
  }

}
