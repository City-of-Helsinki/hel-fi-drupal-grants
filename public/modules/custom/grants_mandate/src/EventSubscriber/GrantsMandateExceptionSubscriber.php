<?php

namespace Drupal\grants_mandate\EventSubscriber;

use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\helfi_audit_log\AuditLogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Grants Handler event subscriber.
 */
class GrantsMandateExceptionSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $logger;

  /**
   * Audit logger.
   *
   * @var \Drupal\helfi_audit_log\AuditLogService
   */
  protected AuditLogService $auditLogService;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Logger.
   * @param \Drupal\helfi_audit_log\AuditLogService $auditLogService
   *   Audit log mandate errors.
   */
  public function __construct(
    MessengerInterface $messenger,
    LoggerChannelFactory $loggerFactory,
    AuditLogService $auditLogService
  ) {
    $this->messenger = $messenger;
    $this->logger = $loggerFactory->get('grants_mandate');
    $this->auditLogService = $auditLogService;
  }

  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   Response event.
   */
  public function onException(ExceptionEvent $event) {
    $ex = $event->getThrowable();
    $exceptionClass = get_class($ex);
    if (str_contains($exceptionClass, 'GrantsMandateException')) {
      $this->messenger->addError(t('Mandate process failed, error has been logged'));
      $this->logger->error('Error getting mandate: @error', ['@error' => $ex->getMessage()]);

      $message = [
        "operation" => "GRANTS_MANDATE_VALIDATE",
        "status" => "ERROR",
        "target" => [
          "id" => "GRANTS_MANDATE",
          "type" => "USER",
          "name" => "MANDATE_ERROR",
        ],
      ];
      $this->auditLogService->dispatchEvent($message);

      // Redirect back to mandate form.
      $url = Url::fromRoute('grants_mandate.mandateform');
      $response = new RedirectResponse($url->toString());
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::EXCEPTION => ['onException'],
    ];
  }

}
