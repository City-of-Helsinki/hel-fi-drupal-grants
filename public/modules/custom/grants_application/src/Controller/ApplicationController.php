<?php

declare(strict_types=1);

namespace Drupal\grants_application\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\grants_application\ApplicationSettingsService;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for application actions.
 */
final class ApplicationController extends ControllerBase {

  /**
   * Constructs a new instance.
   */
  public function __construct(
    private ApplicationSettingsService $applicationSettingsService,
  ) {
  }

  /**
   * Start filling new application.
   */
  public function application(string $id): JsonResponse {
    $settings = $this->applicationSettingsService->getApplicationSettings($id)->toArray();
    return new JsonResponse($settings);
  }

  /**
   * Preview un-editable application form.
   */
  public function preview(string $id): JsonResponse {
    return new JsonResponse(
      $this->applicationSettingsService->getApplicationSettings($id)->toArray()
    );
  }

  /**
   * Edit existing draft or submitted application.
   */
  public function editApplication(string $id, string $uuid): JsonResponse {
    $settings = $this->applicationSettingsService->getApplicationSettings($id);

    // Get data from ATV.
    $atv_form_data = '{"firstname": "matti"}';
    $settings->setFormData(json_decode($atv_form_data, TRUE));

    return new JsonResponse($settings->toArray());
  }

  /**
   * Send the application to ATV.
   */
  public function submitDraftApplication(): void {
    // Redirect somewhere when 200 received from ATV.
  }

  /**
   * Submit application to Avus2.
   */
  public function submitApplication(): void {
    // Redirect somewhere when 200 received from integration.
  }

}
