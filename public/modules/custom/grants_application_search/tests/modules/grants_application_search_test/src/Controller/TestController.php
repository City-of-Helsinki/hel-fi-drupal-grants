<?php

declare(strict_types=1);

namespace Drupal\grants_application_search_test\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * The test controller.
 */
final class TestController {

  /**
   * Returns an OK response.
   */
  public function ok(): Response {
    return new Response('OK');
  }

}
