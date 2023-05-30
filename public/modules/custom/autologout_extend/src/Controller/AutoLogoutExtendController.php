<?php

namespace Drupal\autologout_extend\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Override controller function for autologout route.
 */
class GrantsProfileController extends ControllerBase {

  /**
   * Alternative logout.
   */
  public function altLogout() {
    $url = Url::fromRoute('user.logout');
    return new RedirectResponse($url->toString());
  }

}
