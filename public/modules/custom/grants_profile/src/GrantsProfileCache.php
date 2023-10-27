<?php

namespace Drupal\grants_profile;

use Drupal\Core\Http\RequestStack;
use Drupal\helfi_atv\AtvDocument;
use Symfony\Component\HttpFoundation\Session\Session;

class GrantsProfileCache {

  /**
   * Request stack for session access.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * Session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected Session $session;

  /**
   * Constructs a GrantsProfileCache object.
   *
   * @param \Drupal\Core\Http\RequestStack $requestStack
   *   Storage factory for temp store.
   */
  public function __construct(RequestStack $requestStack) {
      $this->requestStack = $requestStack;
  }
  /**
   * Set session instance for the service.
   *
   * This is used for tests .
   *
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   Session object.
   */
  public function setSession(Session $session): void {
    $this->session = $session;
  }

  /**
   * Get session.
   *
   * @return \Symfony\Component\HttpFoundation\Session\Session
   *   Session object
   */
  public function getSession() {
    if (isset($this->session)) {
      return $this->session;
    }
    $session = $this->requestStack->getCurrentRequest()->getSession();
    $this->session = $session;
    return $this->session;
  }

  /**
   * Whether we have made this query?
   *
   * @param string|null $key
   *   Used key for caching.
   *
   * @return bool
   *   Is this cached?
   */
  public function isCached(?string $key): bool {
    $session = $this->getSession();

    $cacheData = $session->get($key);
    return !is_null($cacheData);
  }

  /**
   * Get item from cache.
   *
   * @param string $key
   *   Key to fetch from tempstore.
   *
   * @return array|\Drupal\helfi_atv\AtvDocument|null
   *   Data in cache or null
   */
  public function getFromCache(string $key): array|AtvDocument|null {
    $session = $this->getSession();
    return !empty($session->get($key)) ? $session->get($key) : NULL;
  }

  /**
   * Add item to cache.
   *
   * @param string $key
   *   Used key for caching.
   * @param array|\Drupal\helfi_atv\AtvDocument $data
   *   Cached data.
   *
   * @return bool
   *   Did save succeed?
   */
  public function setToCache(string $key, array|AtvDocument $data): bool {
    $session = $this->getSession();
    $session->set($key, $data);
    return TRUE;
  }
}
