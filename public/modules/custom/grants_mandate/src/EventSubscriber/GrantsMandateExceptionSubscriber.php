<?php

declare(strict_types=1);

namespace Drupal\grants_mandate\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\helfi_api_base\AuditLog\AuditLogService;
use Drupal\helfi_api_base\AuditLog\Event\AuditLogEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Grants Handler event subscriber.
 */
class GrantsMandateExceptionSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\helfi_api_base\AuditLog\AuditLogService $auditLogService
   *   Audit log mandate errors.
   */
  public function __construct(
    protected MessengerInterface $messenger,
    #[Autowire(service: 'logger.channel.grants_mandate')]
    protected LoggerInterface $logger,
    protected AuditLogService $auditLogService,
  ) {
  }

  /**
   * Kernel response event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   Response event.
   */
  public function onException(ExceptionEvent $event): void {
    $ex = $event->getThrowable();
    $exceptionClass = get_class($ex);
    if (str_contains($exceptionClass, 'GrantsMandateException')) {
      $this->messenger->addError($this->t('Mandate process failed, error has been logged'));
      $this->logger->error('Error getting mandate: @error', ['@error' => $ex->getMessage()]);

      $this->auditLogService->logOperation(new AuditLogEvent(
        operation: "GRANTS_MANDATE_VALIDATE",
        message: "ERROR",
        target: [
          "id" => "GRANTS_MANDATE",
          "type" => "USER",
          "name" => "MANDATE_ERROR",
        ],
      ));

      // Redirect back to mandate form.
      $url = Url::fromRoute('grants_mandate.mandateform');
      $response = new RedirectResponse($url->toString());
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::EXCEPTION => ['onException'],
    ];
  }

}
