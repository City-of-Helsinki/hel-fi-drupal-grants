<?php

declare(strict_types=1);

namespace Drupal\grants_application\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\file\Entity\File;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\helfi_av\AntivirusException;
use Drupal\helfi_av\AntivirusService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

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
    private readonly HelfiAtvService $helfiAtvService,
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
   *   The application number.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function uploadFile(string $id, Request $request): JsonResponse {
    $file = $request->files->get('file');
    if (!$file || !$id) {
      return new JsonResponse(status: 400);
    }

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
      'uid' => $this->currentUser()->id(),
    ]);

    $file_entity->setFileUri($file->getRealPath());

    /** @var \Drupal\grants_application\Entity\ApplicationSubmission $submission */
    $submission = $this->entityTypeManager()
      ->getStorage('application_submission')
      ->loadByProperties(['application_number' => $id]);

    $submission = reset($submission);

    if (!$submission) {
      return new JsonResponse(status: 400);
    }

    try {
      $result = $this->helfiAtvService->addAttachment(
        $submission->document_id->value,
        $file->getClientOriginalName(),
        $file_entity
      );
    }
    catch (\Exception $e) {
      // @todo Log exception message.
      return new JsonResponse(['File upload failed for some reason.'], 500);
    }

    if (!$result) {
      return new JsonResponse(status: 500);
    }

    // @todo Check that events are added as normally.
    $file_entity->delete();
    $response = [
      'filename' => $result['filename'],
      'file_id' => $result['id'],
      'href' => $result['href'],
      'size' => $result['size'],
    ];

    return new JsonResponse($response);
  }

  /**
   * Delete the file from application.
   *
   * @param string $id
   *   The application number.
   * @param string $file_id
   *   The file id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   response object.
   */
  public function deleteFile(string $id, string $file_id): JsonResponse {
    if (!$id || !$file_id) {
      return new JsonResponse(status: 400);
    }

    /** @var \Drupal\grants_application\Entity\ApplicationSubmission $submission */
    $submission = $this->entityTypeManager()
      ->getStorage('application_submission')
      ->loadByProperties(['application_number' => $id]);
    $submission = reset($submission);

    if (!$submission || !$submission->draft->value) {
      // May not delete file if not draft.
      return new JsonResponse(data: ['Cannot remove file from non-draft application.'], status: 503);
    }

    try {
      $this->helfiAtvService->removeAttachment(
        $submission->document_id->value,
        $file_id
      );
    }
    catch (\Exception $e) {
      return new JsonResponse(status: 500);
    }

    return new JsonResponse(status: 200);
  }

}
