<?php

namespace Drupal\helfi_helsinki_profiili_audit_logging\EventSubscriber;

use Drupal\helfi_audit_log\AuditLogService;
use Drupal\helfi_helsinki_profiili\Event\HelsinkiProfiiliExceptionEvent;
use Drupal\helfi_helsinki_profiili\Event\HelsinkiProfiiliOperationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Monitors submission view events and logs them to audit log.
 */
final readonly class HelsinkiProfiiliEventSubscriber implements EventSubscriberInterface {

  public function __construct(
    private AuditLogService $auditLogService,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HelsinkiProfiiliExceptionEvent::class => ['onException'],
      HelsinkiProfiiliOperationEvent::class => ['onOperation'],
    ];
  }

  /**
   * Audit log the exception.
   */
  public function onException(HelsinkiProfiiliExceptionEvent $event): void {
    $exception = $event->getException();
    $message = [
      'operation' => 'HELSINKI_PROFIILI_QUERY',
      'status' => 'EXCEPTION',
      'target' => [
        'name' => $exception->getMessage(),
        'type' => get_class($exception),
      ],
    ];

    $this->auditLogService->dispatchEvent($message);
  }

  /**
   * Audit log the operation.
   */
  public function onOperation(HelsinkiProfiiliOperationEvent $event): void {
    $name = $event->getName();
    $message = [
      'operation' => 'HELSINKI_PROFIILI_QUERY',
      'status' => 'SUCCESS',
      'target' => [
        'name' => $name,
        'type' => $name,
      ],
    ];

    $this->auditLogService->dispatchEvent($message);
  }

}
