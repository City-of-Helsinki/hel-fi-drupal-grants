<?php

namespace Drupal\grants_profile;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface defining Login And Logout Redirect Per Role helper service.
 */
interface UserRedirectInterface {

  /**
   * Config key for Login configuration.
   */
  const KEY_LOGIN = 'login';

  /**
   * Config key for Logout configuration.
   */
  const KEY_LOGOUT = 'logout';

  /**
   * Set Login destination parameter to do redirect.
   *
   * @param string $url
   *   Url.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account to set destination for.
   */
  public function setLoginRedirection(string $url, ?AccountInterface $account = NULL);

  /**
   * Set Logout destination parameter to do redirect.
   *
   * @param string $url
   *   Url.
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account to set destination for.
   */
  public function setLogoutRedirection(string $url, ?AccountInterface $account = NULL);

}
