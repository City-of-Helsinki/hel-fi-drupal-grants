<?php

namespace Drupal\grants_handler\EventSubscriber;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Grants Handler event subscriber.
 */
class ForceCompanyAuthorisationSubscriber implements EventSubscriberInterface {

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Grants profile access.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   The profile service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The profile service.
   */
  public function __construct(
    MessengerInterface $messenger,
    GrantsProfileService $grantsProfileService,
    AccountProxyInterface $currentUser,
  ) {
    $this->messenger = $messenger;
    $this->grantsProfileService = $grantsProfileService;
    $this->currentUser = $currentUser;
  }

  /**
   * Check if user login is required.
   *
   * We do not want to redirect to mandate page if so.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Event from request.
   *   str_replace('/' . $lang, '', $requestUri)
   *
   * @return bool
   *   If needs redirect or not.
   */
  public function needsRedirectToLogin(RequestEvent $event): bool {
    // Login can be required only for anonymous users.
    if ($this->currentUser->isAuthenticated()) {
      return FALSE;
    }
    $requestUri = $event->getRequest()->getRequestUri();
    $urlObject = Url::fromUserInput(trim(rawurldecode($requestUri)));
    if ($urlObject->access(User::getAnonymousUser()) === FALSE) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * If user needs to be redirected to mandate page.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Event from request.
   *
   * @return bool
   *   If needs redirect or not.
   */
  public function needsRedirectToMandate(RequestEvent $event): bool {

    $currentUserRoles = $this->currentUser->getRoles();

    // Redirect only authenticated users with helsinkiprofiili.
    if (!$this->currentUser->isAuthenticated() ||
      !in_array('helsinkiprofiili', $currentUserRoles)) {
      return FALSE;
    }
    // Do not redirect if user already has a mandate.
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    if ($selectedCompany !== NULL) {
      return FALSE;
    }
    $requestUri = $event->getRequest()->getRequestUri();
    $urlObject = Url::fromUserInput(trim(rawurldecode($requestUri)));
    $routeName = $urlObject->getRouteName();

    $nodeType = '';
    if ($routeName == 'entity.node.canonical') {
      $node = Node::load($urlObject->getRouteParameters()['node']);
      $nodeType = $node->getType();
    }
    // & we are on form page.
    $isFormPage = ($nodeType == 'form_page');
    // If not on form_page, we want to allow mandate routes.
    $isMandateRoute = str_contains($routeName, 'grants_mandate');
    // But require mandate in all other grants routes.
    $isGrantsRoute = str_contains($routeName, 'grants_');
    $redirectToMandatePage = ($isFormPage || (!$isMandateRoute && $isGrantsRoute));
    return $redirectToMandatePage;
  }

  /**
   * Kernel request event handler.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   Response event.
   */
  public function onKernelRequest(RequestEvent $event) {

    // admin, no checks.
    if ($this->currentUser->id() == 1) {
      return;
    }

    if (!$this->needsRedirectToLogin($event) &&
      $this->needsRedirectToMandate($event)) {
      $redirectUrl = Url::fromRoute('grants_mandate.mandateform');
      $redirect = new RedirectResponse($redirectUrl->toString());
      $event->setResponse(
        $redirect
      );
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onKernelRequest'],
    ];
  }

}
