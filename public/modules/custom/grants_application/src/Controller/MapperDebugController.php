<?php

declare(strict_types=1);

namespace Drupal\grants_application\Controller;

use Drupal\grants_application\Mapper\JsonMapper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for mapping tool.
 */
class MapperDebugController {

  public function __construct() {
  }

  /**
   * Simple endpoint used for testing new mappings & schemas.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function map(Request $request): JsonResponse {
    $forbidden = ['prod', 'production', 'staging', 'stage', 'test', 'dev'];
    if (in_array(getenv('APP_ENV'), $forbidden)) {
      return new JsonResponse(['forbidden'], 403);
    }

    $requestData = json_decode($request->getContent(), TRUE);

    $data = $requestData['data'];
    $mapping = $requestData['mapping'];

    try {
      $mapper = new JsonMapper($mapping);
      $mappedData = $mapper->map($data);
    }
    catch (\Exception $e) {
      return new JsonResponse([$e->getMessage()], 500);
    }

    return new JsonResponse(['data' => $mappedData]);
  }

}
