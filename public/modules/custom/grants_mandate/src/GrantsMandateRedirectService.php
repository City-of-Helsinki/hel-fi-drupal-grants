<?php

namespace Drupal\grants_mandate;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\grants_profile\GrantsProfileService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Mandate redirect service class.
 */
class GrantsMandateRedirectService {

  const SESSION_KEY = 'roleselection_redirect';

  /**
   * Construct the service class.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request Stack.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Grants profile service.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $routeMatch
   *   Route match.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected GrantsProfileService $grantsProfileService,
    protected CurrentRouteMatch $routeMatch,
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
   * Set current service page as redirect uri.
   *
   * Set current service page as redirect uri. If the user has not
   * selected a role, or the role is not correct one for the application.
   */
  public function maybeSaveServicePage() {
    $request = $this->requestStack->getCurrentRequest();
    $node = $this->routeMatch->getParameter('node');

    if (empty($node) || $node->getType() !== 'service') {
      return;
    }

    $applicantTypes = $node->get('field_hakijatyyppi')->getValue();
    $currentRole = $this->grantsProfileService->getSelectedRoleData();
    $currentRoleType = NULL;

    if ($currentRole) {
      $currentRoleType = $currentRole['type'];
    }

    $isCorrectApplicantType = FALSE;

    foreach ($applicantTypes as $applicantType) {
      if (in_array($currentRoleType, $applicantType)) {
        $isCorrectApplicantType = TRUE;
      }
    }

    if ($isCorrectApplicantType) {
      return;
    }

    $uri = $request->getRequestUri();
    $session = $this->getSession();
    $session->set('last_service_page_uri', $uri);
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
  private function clearSessionVariables() {
    $session = $this->getSession();
    $session->remove(self::SESSION_KEY);
    $session->remove('last_service_page_uri');
  }

  /**
   * Set service page as role selection redirection and clear it from session.
   */
  public function handlePossibleServicePageRedirection() {
    $request = $this->requestStack->getCurrentRequest();
    $session = $this->getSession();
    $param = $request->query->get('redirect_to_service_page');
    $uri = $session->get('last_service_page_uri');

    if ($param && !empty($uri)) {
      $this->setRedirectUri($uri);
      $session->remove('last_service_page_uri');
    }
  }

  /**
   * Gets the redirect for after profile selection redirection.
   *
   * @param \Symfony\Component\HttpFoundation\RedirectResponse $defaultRedirect
   *   Default redirect if there is none in the session data.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function getRedirect(RedirectResponse $defaultRedirect) {
    $session = $this->getSession();
    $redirectUri = $session->get(self::SESSION_KEY);

    if (!empty($redirectUri)) {
      $this->clearSessionVariables();
      return new RedirectResponse($redirectUri);
    }

    return $defaultRedirect;
  }

}
