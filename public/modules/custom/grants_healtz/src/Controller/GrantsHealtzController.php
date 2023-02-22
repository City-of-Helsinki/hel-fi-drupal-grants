<?php

namespace Drupal\grants_healtz\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Returns responses for grants_healtz routes.
 */
class GrantsHealtzController extends ControllerBase {

  /**
   * Response to readiness probe.
   */
  public function readiness(): JsonResponse {
    return new JsonResponse(['data' => [], 'method' => 'GET', 'status' => 200]);
  }

  /**
   * Response to healthz probe.
   */
  public function healthz(): JsonResponse {
    return new JsonResponse(['data' => [], 'method' => 'GET', 'status' => 200]);
  }

}
