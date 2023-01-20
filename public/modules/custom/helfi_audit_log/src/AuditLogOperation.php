<?php

namespace Drupal\helfi_audit_log;

use Drupal;

class AuditLogOperation {

  public function __construct(AuditLogProvider $provider, array $message) {

    $isValid = $provider->validateMessage($message);
    if (!$isValid) {

      // throw new AuditLogException(t("Message has incorrect structure"));
      \Drupal::logger('helfi_audit_log')
      ->error(t('Audit log message validation failed.'));
      return;
    }

    // Message ok, call service
    Drupal::service("helfi_audit_log.audit_log")->logOperation($message, $provider->getOrigin());

  }

}
