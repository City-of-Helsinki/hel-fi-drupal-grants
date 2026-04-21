<?php

declare(strict_types=1);

namespace Drupal\helfi_helsinki_profiili\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Helsinki-profiili event subscriber.
 *
 * @fixme Do we need this? Event is called only when the exception is not
 * caught elsewhere. This might hide bugs elsewhere.
 */
readonly class TokenExpiredExceptionSubscriber implements EventSubscriberInterface {

  public function __construct(private MessengerInterface $messenger) {
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   Response event.
   */
  public function onKernelException(ExceptionEvent $event): void {
    $exception = $event->getThrowable();

    // Logout if the token is expired and redirect the user to the front page.
    // TokenExpiredException is thrown by HelsinkiProfiiliUserData.
    if ($exception instanceof TokenExpiredException) {
      user_logout();
      $this->messenger->addError('Session timeout, please relogin');
      $url = Url::fromRoute('<front>');
      $response = new RedirectResponse($url->toString());
      $event->setResponse($response);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      KernelEvents::EXCEPTION => ['onKernelException'],
    ];
  }

}
