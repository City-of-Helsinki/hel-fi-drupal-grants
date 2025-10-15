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
  public function removeTestApplicationDocuments(string $id): JsonResponse {
    if (getenv('APP_ENV') === 'production') {
      $this->logger->error('Test data removal should not be done in production environment.');
      return new JsonResponse('', 403);
    }

    try {
      $document = $this->atvService->getDocument($id);
      $this->atvService->deleteDocument($document);
    }
    catch (\Throwable $e) {
      $this->logger->error('Exception while deleting e2e test document: $id');
      return new JsonResponse('', 500);
    }

    return new JsonResponse('Document deleted.', 200);
  }

  /**
   * Send ATV-request to remove user-related documents created by E2E-tests.
   *
   * @param string $uuid
   *   The document uuid.
   * @param string $profileType
   *   The profile type.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function removeTestUserDocuments(string $uuid, string $profileType): JsonResponse {
    if (getenv('APP_ENV') === 'production') {
      $this->logger->error('Test data removal should not be done in production environment.');
      return new JsonResponse('', 403);
    }

    $appEnvForAtv = '';

    $lookfor = [
      'appenv' => $appEnvForAtv,
      'profile_type' => $profileType,
    ];

    $parameters = [
      'lookfor' => $lookfor,
      'user_id' => $uuid,
      'type' => 'grants_profile',
      'service_name' => 'AvustushakemusIntegraatio',
      'profile_type' => $profileType,
    ];

    try {
      $documents = $this->atvService->searchDocuments($parameters, TRUE);
    }
    catch (\Throwable $e) {
      $this->logger->error('Exception while searching for documents: ' . $e->getMessage());
      return new JsonResponse(['Exception while searching for test documents'], 500);
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

}
