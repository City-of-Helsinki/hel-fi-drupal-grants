<?php

declare(strict_types=1);

namespace Drupal\grants_admin_applications\Service;

use Drupal\helfi_atv\AtvService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Application cleaner service.
 */
class ApplicationCleaner {

  /**
   * Constructs a new instance.
   */
  public function __construct(
    #[Autowire(service: 'helfi_atv.atv_service')]
    private readonly AtvService $atv,
  ) {
  }

  /**
   * The buildApplicationList function.
   *
   * This function builds a list of applications
   * based on the passed in parameters.
   *
   * @param mixed $uuid
   *   A users UUID.
   * @param mixed $businessId
   *   A business ID.
   * @param mixed $appEnv
   *   An app env.
   * @param mixed $type
   *   An applications' type.
   * @param mixed $status
   *   An applications' status.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function searchDocuments(
    mixed $uuid,
    mixed $businessId,
    mixed $appEnv,
    mixed $type,
    mixed $status,
  ): array {
    return $this->atv->searchDocuments($this->assembleSearchParams($uuid, $businessId, $appEnv, $type, $status));
  }

  /**
   * The assembleSearchParams function.
   *
   * This function assembles an array of search parameters
   * based on the passed in values. If a value is not set,
   * then they key is omitted from the final search parameters
   * array.
   *
   * @param mixed $uuid
   *   A users UUID.
   * @param mixed $businessId
   *   A business ID.
   * @param mixed $appEnv
   *   An app env.
   * @param mixed $type
   *   An applications' type.
   * @param mixed $status
   *   An applications' status.
   *
   * @return array
   *   An associative array of search params, prefixed with a key.
   */
  public function assembleSearchParams(mixed $uuid, mixed $businessId, mixed $appEnv, mixed $type, mixed $status): array {
    return array_filter([
      'user_id' => $uuid ?: NULL,
      'business_id' => $businessId ?: NULL,
      'lookfor' => $appEnv ? "appenv:$appEnv" : NULL,
      'type' => ($type && $type !== 'all') ? $type : NULL,
      'status' => ($status && $status !== 'all') ? $status : NULL,
    ], static fn ($value) =>  !is_null($value));
  }

}
