<?php

namespace Drupal\autologout_extend\EventSubscriber;

use DateTime;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber class to check autologout session refresh.
 *
 * @package Drupal\autologout_extend\EventSubscriber
 */
class RequestEventSubscriber implements EventSubscriberInterface {

  /**
   * Constructs the event subscriber.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   Current route match.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $userData
   *   Helsinki profiili user data service.
   */
  public function __construct(
    private CurrentRouteMatch $routeMatch,
    private RequestStack $requestStack,
    private HelsinkiProfiiliUserData $userData) {
  }

  /**
   * KernelEvent::Request callback.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Request event.
   */
  public function checkRefresh(RequestEvent $event) {

    $currentUser = \Drupal::currentUser();
    $currentUserRoles = $currentUser->getRoles();

    if (!in_array('helsinkiprofiili', $currentUserRoles)) {
      return;
    }

    $session = $this->requestStack->getCurrentRequest()->getSession();
    $sessionExpire = $session->get('openid_connect_expire');
    $timeStamp = time();

    $refreshThreshold = $sessionExpire - 900;

    if ($timeStamp > $refreshThreshold) {
      $hello = 1;
      $this->userData->refreshTokens();
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkRefresh'];
    return $events;
  }

}
