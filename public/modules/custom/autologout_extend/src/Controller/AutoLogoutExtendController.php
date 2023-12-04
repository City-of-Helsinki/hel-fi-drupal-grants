<?php

namespace Drupal\autologout_extend\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Override controller function for autologout route.
 */
class AutoLogoutExtendController extends ControllerBase {

  /**
   * Alternative logout.
   */
  public function altLogout() {
    $url = Url::fromRoute('user.logout');
    return new RedirectResponse($url->toString());
  }

}
