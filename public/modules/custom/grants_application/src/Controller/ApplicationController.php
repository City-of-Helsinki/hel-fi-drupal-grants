<?php

declare(strict_types=1);

namespace Drupal\grants_application\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\helfi_av\AntivirusException;
use Drupal\helfi_av\AntivirusService;
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
  public function __construct(private readonly AntivirusService $antivirusService) {
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
   */
  public function uploadFile(string $id, Request $request): Response {
    $file = $request->files->get('file');
    if (!$file) {
      return new Response(status: 201);
    }

    try {
      $this->antivirusService->scan([
        $file->getClientOriginalName() => file_get_contents($file->getRealPath()),
      ]);
    }
    catch (AntivirusException $e) {
      return new Response(status: 400);
    }

    return new Response(status: 200);
  }

}
