<?php

namespace Drupal\grants_profile;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Login And Logout Redirect Per Role helper service.
 */
class UserRedirect implements UserRedirectInterface {
  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $currentRequest;

  /**
   * Constructs a new Login And Logout Redirect Per Role service object.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   The current user.
   */
  public function __construct(
    protected RequestStack $request_stack,
    protected AccountProxyInterface $currentUser,
  ) {
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function setLoginRedirection(string $url, AccountInterface $account = NULL): void {
    $this->prepareDestination($url);
  }

  /**
   * {@inheritdoc}
   */
  public function setLogoutRedirection(string $url, AccountInterface $account = NULL) {
    $this->prepareDestination($url);
  }

  /**
   * Set "destination" parameter to do redirect.
   *
   * @param string $redirect_url
   *   Configuration key (login or logout).
   */
  protected function prepareDestination(string $redirect_url) {

    $loggedin_user_roles = array_reverse($this->currentUser->getRoles());

    if (in_array('helsinkiprofiili', $loggedin_user_roles)) {

    }
    if ($redirect_url) {
      if (UrlHelper::isExternal($redirect_url)) {
        $response = new TrustedRedirectResponse($redirect_url);
        $response->send();
      }
      else {
        $url = Url::fromUserInput($redirect_url);
        if ($url instanceof Url) {
          $this->currentRequest->query->set('destination', $url->toString());
        }
      }
    }
  }

}
