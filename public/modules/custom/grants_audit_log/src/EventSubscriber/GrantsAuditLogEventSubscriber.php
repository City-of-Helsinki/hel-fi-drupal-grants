<?php

namespace Drupal\grants_audit_log\EventSubscriber;

use Drupal\Core\Http\RequestStack;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\helfi_audit_log\Event\AuditLogEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscribes to AuditLogEvent::LOG events.
 */
class GrantsAuditLogEventSubscriber implements EventSubscriberInterface {

  const AUDIT_LOG_PROVIDER_ORIGIN = 'HELFI-GRANTS';

  /**
   * Constrictor for thee class.
   */
  public function __construct(AccountProxyInterface $accountProxy, RequestStack $requestStack) {
    $this->currentUser = $accountProxy;
    $this->request = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AuditLogEvent::LOG][] = ['validate', 0];
    $events[AuditLogEvent::LOG][] = ['addUser', -10];

    return $events;
  }

  /**
   * Validate message in AuditEvent::LOG event.
   *
   * @param \Drupal\helfi_audit_log\AuditLogEvent $event
   *   Event to validate.
   */
  public function validate(AuditLogEvent $event) {
    if (!$event->isValid()) {
      // Event is invalid based on previous handlers.
      return;
    }
    // Validate message.
    $message = $event->getMessage();
    $isValid = $this->validateMessage($message);
    $event->setValid($isValid);
    // Set origin.
    $event->setOrigin(self::AUDIT_LOG_PROVIDER_ORIGIN);
  }

  /**
   * Add user data to event data.
   *
   * This method is called whenever the AuditEvent::LOG event is
   * dispatched.
   *
   * @param \Drupal\helfi_audit_log\Event\AuditLogEvent $event
   *   Event to handle.
   */
  public function addUser(AuditLogEvent $event) {
    // Determine user role based on if user has admin role.
    $role = in_array("admin", $this->currentUser->getRoles()) ? "ADMIN" : "USER";
    $userId = $this->currentUser->id();
    // Get current user.
    if ($role == 'USER') {
      $isAuthenticatedExternally = \Drupal::service('helfi_helsinki_profiili.userdata')->isAuthenticatedExternally();
      if ($isAuthenticatedExternally) {
        $data = \Drupal::service('helfi_helsinki_profiili.userdata')->getUserData();
        if ($data !== NULL && $data['sid']) {
          $userId = $data['sid'];
        }
      }
    }
    $message = $event->getMessage();
    $message["actor"] = [
      "role" => $role,
      "user_id" => $userId,
      "ip_address" => $this->request->getClientIp(),
    ];
    $event->setMessage($message);
  }

  /**
   * Validate event message.
   *
   * @param array $message
   *   The message array.
   * @param array $structure
   *   Expected keys in message.
   */
  protected function validateKeysRecursive(array $message, array $structure) : bool {
    $isValid = TRUE;
    foreach ($message as $key => $value) {
      if (!isset($structure[$key])) {
        $isValid = FALSE;
        break;
      };
      if (is_array($value)) {
        if (!is_array($structure[$key])) {
          $isValid = FALSE;
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
   *
   * @param array $message
   *   The message array.
   */
  public function validateMessage(array $message) : bool {
    $structure = $this->getLogStructure();

    $isValid = $this->validateKeysRecursive($message, $structure);

    return $isValid;
  }

  /**
   * Return expected keys for event message.
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
