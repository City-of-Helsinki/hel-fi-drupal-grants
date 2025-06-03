<?php

declare(strict_types=1);

namespace Drupal\grants_application\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
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
    private readonly CsrfTokenGenerator $csrfTokenGenerator,
  ) {
  }

  /**
   * Render the forms react app.
   *
   * @param string $id
   *   The application number.
   *
   * @return array
   *   The resulting array
   */
  public function formsApp(string $id): array {
    return [
      '#theme' => 'forms_app',
      '#attached' => [
        'drupalSettings' => [
          'grants_react_form' => [
            'application_number' => $id,
            'token' => $this->csrfTokenGenerator->get('rest'),
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
    /** @var \Symfony\Component\HttpFoundation\File\File $file */
    $file = $request->files->get('file');
    if (!$file || !$id) {
      return new JsonResponse(status: 400);
    }

    // @phpstan-ignore-next-line
    $file_original_name = $file->getClientOriginalName();

    try {
      $this->antivirusService->scan([
        $file_original_name => file_get_contents($file->getRealPath()),
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
        $file_original_name,
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

    // @todo Check that events are added as normally HANDLER_ATT_OK.
    // Https://helsinkisolutionoffice.atlassian.net/wiki/spaces/KAN/pages/
    // 8671232440/Hakemuksen+elinkaaren+tapahtumat+Eventit.
    $file_entity->delete();
    $response = [
      'fileName' => $result['filename'],
      'fileId' => $result['id'],
      'href' => $result['href'],
      'size' => $result['size'],
    ];

    return new JsonResponse($response);
  }

}
