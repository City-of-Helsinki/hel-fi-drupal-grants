<?php

namespace Drupal\grants_handler\EventSubscriber;

use Drupal\grants_handler\Event\UserLogoutEvent;
use Drupal\openid_connect_logout_redirect\Service\RedirectService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * RedirectAfterLogoutSubscriber event subscriber.
 *
 * @package Drupal\redirect_after_logout\EventSubscriber
 */
class RedirectAfterLogoutSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(protected RedirectService $redirectService) {}

  /**
   * Redirect user to front page after logout.
   *
   * @param \Drupal\grants_handler\Event\UserLogoutEvent $event
   *   Event.
   */
  public function checkRedirection(UserLogoutEvent $event) {
    return $this->redirectService->getLogoutRedirectUrl();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[UserLogoutEvent::EVENT_NAME][] = ['checkRedirection'];
    return $events;
  }

}
