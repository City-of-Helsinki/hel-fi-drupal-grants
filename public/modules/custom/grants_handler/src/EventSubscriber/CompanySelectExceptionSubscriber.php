<?php

namespace Drupal\grants_handler\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\grants_mandate\GrantsMandateRedirectService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Grants Handler event subscriber.
 */
class CompanySelectExceptionSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The redirect service.
   *
   * @var \Drupal\grants_mandate\GrantsMandateRedirectService
   */
  protected $redirectService;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\grants_mandate\GrantsMandateRedirectService $redirectService
   *   Redirect service.
   */
  public function __construct(
    MessengerInterface $messenger,
    GrantsMandateRedirectService $redirectService,
    ) {
    $this->messenger = $messenger;
    $this->redirectService = $redirectService;
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
    if ($exceptionClass === 'Drupal\grants_mandate\CompanySelectException') {
      // @codingStandardsIgnoreStart
      $this->messenger->addError($this->t($ex->getMessage()));
      // @codingStandardsIgnoreEnd

      $this->redirectService->setCurrentPageAsRedirectUri();

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
