<?php

declare(strict_types=1);

namespace Drupal\grants_application\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\file\Entity\File;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_av\AntivirusException;
use Drupal\helfi_av\AntivirusService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for application actions.
 */
final class ApplicationController extends ControllerBase {

  use AutowireTrait;

  /**
   * Constructs a new instance.
   */
  public function __construct(
    private readonly AntivirusService $antivirusService,
    #[Autowire(service: 'helfi_atv.atv_service')]
    private readonly AtvService $atvService,
    private readonly HelfiAtvService $helfiAtvService
  ) {
  }

  /**
   * Render the forms react app.
   */
  public function formsApp(string $id): array {
    return [
      '#theme' => 'forms_app',
      '#attached' => [
        'drupalSettings' => [
          'grants_react_form' => [
            'application_number' => $id,
          ],
        ],
      ],
    ];
  }

  /**
   * Upload file handler.
   *
   * @param string $id
   *   The application id.
   * @param Request $request
   *   The request.
   *
   * @return JsonResponse
   *   The response.
   */
  public function uploadFile(string $id, Request $request): JsonResponse {
    $file = $request->files->get('file');
    if (!$file || !$id) {
      return new JsonResponse(status: 406);
    }

    // @todo Check file type.
    try {
      $this->antivirusService->scan([
        $file->getClientOriginalName() => file_get_contents($file->getRealPath()),
      ]);
    }
    catch (AntivirusException $e) {
      return new JsonResponse(status: 400);
    }

    $file_entity = File::create([
      'filename' => basename($file->getFilename()),
      'status' => 0,
      'uid' => \Drupal::currentUser()->id(),
    ]);

    $file_entity->setFileUri($file->getRealPath());

    $submission = \Drupal::entityTypeManager()
      ->getStorage('application_submission')
      ->loadByProperties(['application_number' => $id]);

    $submission = reset($submission);

    $result = $this->atvService->uploadAttachment(
      $submission->document_id->value,
      $file->getClientOriginalName(),
      $file_entity,
    );

    if (!$result) {
      return new JsonResponse(status: 500);
    }

    // todo I guess we must add the events as well.

    $file_entity->delete();

    // The $result['filename'] is the actual filename.
    // File id is more meaningful.
    return new JsonResponse($result);
  }

  /**
   * @param string $id
   *   The application id.
   * @param string $file_id
   *   The file id.
   * @param Request $request
   *   The request.
   *
   * @return JsonResponse
   *   response object.
   */
  public function deleteFile(string $id, string $file_id, Request $request) {
    // Do not delete if application is not draft
    $submission = \Drupal::entityTypeManager()
      ->getStorage('application_submission')
      ->loadByProperties(['application_number' => $id]);

    $submission = reset($submission);

    if (!$submission->draft->value) {
      return new JsonResponse(status: 503);
    }

    try {
      // todo Maybe use helfi atv service only.
      $this->atvService->deleteAttachment($submission->document_id->value, $file_id);
    }
    catch (\Exception $e) {
      return new JsonResponse(status: 500);
    }

    return new JsonResponse(status: 200);
  }

}
