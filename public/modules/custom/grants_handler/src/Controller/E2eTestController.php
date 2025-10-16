<?php

namespace Drupal\grants_handler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\helfi_atv\AtvService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * E2e test controller.
 */
class E2eTestController extends ControllerBase {

  public function __construct(
    private AtvService $atvService,
    #[Autowire(service: 'logger.channel.grants_e2e')]
    protected LoggerInterface $logger,
  ) {
  }

  /**
   * Send ATV-request to remove the applications created by E2E-tests.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function fetchLatestProfileByType(string $id, string $profileType): JsonResponse {
    if (getenv('APP_ENV') === 'production') {
      $this->logger->error('Test data removal should not be done in production environment.');
      return new JsonResponse('', 403);
    }

    try {
      $documents = $this->queryDocuments($id, $profileType);
    }
    catch (\Throwable $e) {
      $this->logger->error('Exception while searching for test documents: ' . $e->getMessage());
      return new JsonResponse(['Exception while searching for test documents'], 500);
    }

    if (empty($documents)) {
      $this->logger->error(
        'E2E-test requested for latest profile but cannot find any'
      );
      return new JsonResponse('Document not found.', 404);
    }

    return new JsonResponse($documents[0], 200);
  }

  /**
   * Send ATV-request to remove user-related documents created by E2E-tests.
   *
   * @param string $id
   *   The document uuid.
   * @param string $profileType
   *   The profile type.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function removeTestUserDocuments(string $id, string $profileType): JsonResponse {
    if (getenv('APP_ENV') === 'production') {
      $this->logger->error('Test data removal should not be done in production environment.');
      return new JsonResponse('', 403);
    }

    try {
      $documents = $this->queryDocuments($id, $profileType);
    }
    catch (\Throwable $e) {
      $this->logger->error('Exception while removing for documents: ' . $e->getMessage());
      return new JsonResponse(['Exception while removing test documents'], 500);
    }

    // Don't know if requesting for already deleted documents is 500 or 200.
    // This should not happen but impossible to know before letting the
    // E2E-tests run.
    if (empty($documents)) {
      $this->logger->error(
        'Requested for document deletion but documents does not exist.'
      );
      return new JsonResponse(['No documents found, nothing to delete'], 200);
    }

    /** @var \Drupal\helfi_atv\AtvDocument $document */
    foreach ($documents as $document) {
      try {
        $this->atvService->deleteDocument($document);
      }
      catch (\Throwable $e) {
        $this->logger->error('Exception while deleting test document: ' . $document->getId() . ' ' . $e->getMessage());
      }
    }

    return new JsonResponse('Documents deleted', 200);
  }

  /**
   * Query atv for documents.
   *
   * @param string $userid
   *   The user uuid.
   * @param string $profileType
   *   The profile type.
   *
   * @return array
   *   The documents.
   */
  private function queryDocuments(string $userid, string $profileType) {
    $lookFor = [
      'appenv' => $this->getAppEnvForAtv(getenv('APP_ENV')),
      'profile_type' => $profileType,
    ];

    $parameters = [
      'lookfor' => $lookFor,
      'user_id' => $userid,
      'type' => 'grants_profile',
      'sort' => 'updated_at',
    ];

    return $this->atvService->searchDocuments($parameters, TRUE);
  }

  /**
   * Drupal app env to ATV app env.
   *
   * @param string $appEnv
   *   The app env.
   *
   * @return string
   *   ATV -app-env.
   */
  private function getAppEnvForAtv(string $appEnv): string {
    return match($appEnv) {
      'development' => 'DEV',
      'testing' => 'TEST',
      'staging' => 'STAGE',
      default => strtoupper($appEnv),
    };
  }

}
