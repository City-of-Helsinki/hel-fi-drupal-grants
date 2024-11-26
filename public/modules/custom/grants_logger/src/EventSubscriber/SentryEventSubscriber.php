<?php

declare(strict_types=1);

namespace Drupal\grants_logger\EventSubscriber;

use Drupal\helfi_atv\Event\AtvServiceExceptionEvent;
use Drupal\helfi_helsinki_profiili\Event\HelsinkiProfiiliExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Forwards events to Sentry.
 */
final class SentryEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      AtvServiceExceptionEvent::EVENT_ID => 'onAtvException',
      HelsinkiProfiiliExceptionEvent::EVENT_ID => 'onHelsinkiProfiiliException',
    ];
  }

  /**
   * Logs the event to sentry.
   *
   * @param \Drupal\helfi_atv\Event\AtvServiceExceptionEvent $event
   *   An exception event.
   */
  public function onAtvException(AtvServiceExceptionEvent $event): void {
    // Consider ignoring the event if $event is instanceof GuzzleException
    // and http error status code is _some_status_code_ if, for example, 404
    // errors cause too much error spam here.
    \Sentry\captureException($event->getException());
  }

  /**
   * Logs the event to sentry.
   *
   * @param \Drupal\helfi_helsinki_profiili\Event\HelsinkiProfiiliExceptionEvent $event
   *   An exception event.
   */
  public function onHelsinkiProfiiliException(HelsinkiProfiiliExceptionEvent $event): void {
    \Sentry\captureException($event->getException());
  }

}
