<?php

namespace Drupal\grants_mandate\EventSubscriber;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\grants_mandate\GrantsMandateRedirectService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Mandate request subscriber.
 */
class GrantsMandateRequestSubscriber implements EventSubscriberInterface {

  protected const ROUTES = [
    'grants_mandate.mandateform',
    'grants_mandate.callback_ypa',
  ];

  /**
   * Construct the event subscriber.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request Stack.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   Current User.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   Route match.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected AccountProxyInterface $account,
    protected CurrentRouteMatch $routeMatch,
  ) {
  }

  /**
   * Removes redirect variable from session if not in allowlisted route.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The event to process.
   */
  public function onRequest(RequestEvent $event): void {
    $isAuthenticated = $this->account->isAuthenticated();
    $routeName = $this->routeMatch->getRouteName();

    if (!$isAuthenticated) {
      return;
    }

    $session = $this->requestStack->getSession();
    $sessionVariable = $session->get(GrantsMandateRedirectService::SESSION_KEY);

    if (!empty($sessionVariable) && !in_array($routeName, self::ROUTES)) {
      $session->remove(GrantsMandateRedirectService::SESSION_KEY);
    }

  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::REQUEST][] = ['onRequest', 30];
    return $events;
  }

}
