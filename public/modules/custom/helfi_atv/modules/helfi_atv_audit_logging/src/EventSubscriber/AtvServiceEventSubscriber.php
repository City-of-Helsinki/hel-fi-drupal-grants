<?php

declare(strict_types=1);

namespace Drupal\helfi_atv_audit_logging\EventSubscriber;

use Drupal\helfi_atv\Event\AtvServiceExceptionEvent;
use Drupal\helfi_atv\Event\AtvServiceOperationEvent;
use Drupal\helfi_api_base\AuditLog\AuditLogService;
use Drupal\helfi_api_base\AuditLog\Event\AuditLogEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Monitors submission view events and logs them to audit log.
 */
class AtvServiceEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(private AuditLogService $auditLogService) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AtvServiceExceptionEvent::EVENT_ID][] = ['onException'];
    $events[AtvServiceOperationEvent::EVENT_ID][] = ['onOperation'];
    return $events;
  }

  /**
   * Audit log the exception.
   *
   * @param \Drupal\helfi_atv\Event\AtvServiceExceptionEvent $event
   *   An exception event.
   */
  public function onException(AtvServiceExceptionEvent $event) {
    $exception = $event->getException();
    $this->auditLogService->logOperation(new AuditLogEvent(
      operation: 'ATV_QUERY',
      message: 'EXCEPTION',
      target: [
        'name' => $exception->getMessage(),
        'type' => get_class($exception),
      ],
    ));
  }

  /**
   * Audit log the operation.
   *
   * @param \Drupal\helfi_atv\Event\AtvServiceOperationEvent $event
   *   An operation event.
   */
  public function onOperation(AtvServiceOperationEvent $event) {
    $method = $event->getMethod();
    $url = $event->getUrl();
    $this->auditLogService->logOperation(new AuditLogEvent(
      operation: 'ATV_QUERY',
      message: 'SUCCESS',
      target: [
        'name' => $url,
        'type' => $method,
      ],
    ));
  }

}
