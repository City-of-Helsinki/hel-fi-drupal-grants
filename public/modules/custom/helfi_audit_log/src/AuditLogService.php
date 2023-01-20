<?php

namespace Drupal\helfi_audit_log;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Database\Connection;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Session\AccountProxyInterface;
use Exception;

/**
 * AuditLog service.
 */
class AuditLogService {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * Constructs a AuditLogService object.
   */
  public function __construct(AccountProxyInterface $accountProxy, Connection $connection, TimeInterface $time, RequestStack $requestStack) {
    $this->currentUser = $accountProxy;
    $this->connection = $connection;
    $this->time = $time;
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * Operation that logs the message to database.
   *
   * @param array $message
   *   Message that is merged with generic data and logged to database.
   * @param string $origin
   *   String identifying the source for the audit log message.
   */
  public function logOperation(array $message, string $origin) {

    $current_timestamp = $this->time->getCurrentMicroTime();

    // Determine user role based on if user has admin role.
    $role = in_array("admin", $this->currentUser->getRoles()) ? "ADMIN" : "USER";

    $operation_data = [
      "origin" => $origin,
      "source" => "DRUPAL",
      "date_time" => floor($current_timestamp * 1000),
      // Format should be yyyy-MM-ddThh:mm:ss.SSSZ.
      "date_time_epoch" =>
      date("Y-m-d\TH:i:s", floor($current_timestamp)) .
      "." .
      str_pad(floor(($current_timestamp - floor($current_timestamp)) * 1000), 3, "0", STR_PAD_LEFT) .
      "Z",
      "actor" => [
        "role" => $role,
        "user_id" => $this->currentUser->id(),
        "ip_address" => $this->request->getClientIp(),
      ],
    ];

    // Merge message and generic operation data.
    $operation_data = array_merge($operation_data, $message);

    try {
      $result = $this->connection->insert('helfi_audit_logs')
        ->fields([
          'created_at' => $this->time->getRequestTime(),
          'is_sent' => 0,
          'message' => Json::encode(['audit_event' => $operation_data]),
        ])
        ->execute();
    }
    catch (Exception $e) {
      \Drupal::logger('helfi_audit_log')
      ->error(t('Unable to write log message to database.'));
    }

  }
}
