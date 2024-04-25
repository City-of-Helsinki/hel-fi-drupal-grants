<?php

namespace Drupal\grants_mandate;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mandate redirect service class.
 */
class GrantsMandateRedirectService {

  const SESSION_KEY = 'roleselection_redirect';

  /**
   * Construct the service class.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  public function __construct(
   protected RequestStack $requestStack,
  ) {}

  /**
   * Get the session.
   *
   * @return \Symfony\Component\HttpFoundation\Session\SessionInterface
   *   Session.
   */
  private function getSession() {
    return $this->requestStack->getSession();
  }

  /**
   * Sets current page which throw CompananySelectException as redirection URI.
   */
  public function setCurrentPageAsRedirectUri() {
    $request = $this->requestStack->getCurrentRequest();
    $this->setRedirectUri($request->getRequestUri());
  }

  /**
   * Sets redirection URI to session data.
   *
   * @param string $uri
   *   Uri where to redirect to.
   */
  private function setRedirectUri(string $uri) {
    $session = $this->getSession();
    $session->set(self::SESSION_KEY, $uri);
  }

  /**
   * Clears possible redirection data from session.
   */
  private function clearRedirectUri() {
    $session = $this->getSession();
    $session->remove(self::SESSION_KEY);
  }

  /**
   * Gets the redirect for after profile selection redirection.
   *
   * @param \Symfony\Component\HttpFoundation\Response $defaultRedirect
   *   Default redirect if there is none in the session data.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The redirect response.
   */
  public function getRedirect(Response $defaultRedirect, $useTrustedRedirect = FALSE) {
    $session = $this->getSession();
    $redirectUri = $session->get(self::SESSION_KEY);

    if (!empty($redirectUri)) {
      $this->clearRedirectUri();
      $redirectResponse = $useTrustedRedirect
       ? new TrustedRedirectResponse($redirectUri)
       : new RedirectResponse($redirectUri);
      return $redirectResponse;
    }

    return $defaultRedirect;
  }

}
