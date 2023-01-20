<?php

namespace Drupal\helfi_audit_log;

/**
 * Abstract base class for Audit Log Provider.
 */
abstract class AuditLogProvider {

  /**
   * Name of the data origin.
   */
  abstract public function getOrigin() : string;

  /**
   * Structure for log data.
   */
  abstract public function getLogStructure() : array;


  protected function validateKeysRecursive(array $message, array $structure) : bool {
    $isValid = true;
    foreach ($message as $key => $value) {
      if (!isset($structure[$key])) {
        $isValid = false;
        break;
      };
      if (is_array($value)) {
        if (!is_array($structure[$key])) {
          $isValid = false;
          break;
        }
        $isValid = $this->validateKeysRecursive($value, $structure[$key]);
        if (!$isValid) {
          break;
        }
      }
    }
    return $isValid;
  } 
  /**
   * Message validation.
   */
  public function validateMessage(array $message) : bool {
    $structure = $this->getLogStructure();

    $isValid = $this->validateKeysRecursive($message, $structure);
    return $isValid;
  }

  /**
   * Log data
   */
  public function logData(array $logData) : AuditLogOperation {
    return new AuditLogOperation($this, $logData);
  }

}
