<?php

declare(strict_types=1);

namespace Drupal\grants_application\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\grants_application\FormSettingsService;
use Drupal\grants_application\UserInformationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Controller for application actions.
 */
final class ApplicationController extends ControllerBase {

  /**
   * Constructs a new instance.
   */
  public function __construct(
    private FormSettingsService $formSettingsService,
    private UserInformationService $userInformationService,
  ) {
  }

  /**
   * Start filling new application.
   */
  public function application(string $id): JsonResponse {
    try {
      /** @var \Drupal\grants_application\FormSettings $settings */
      $settings = $this->formSettingsService->getFormSettings($id);
    }
    catch (\Exception $e) {
      // Cannot find form.
      return new JsonResponse([], 500);
    }

    if (!$settings->isApplicationOpen()) {
      return new JsonResponse([], 403);
    }

    try {
      $settings->setGrantsProfileData(
        $this->userInformationService->getGrantsProfileData()
      );
      $settings->setUserData(
        $this->userInformationService->getUserData()
      );
    }
    catch (\Exception $e) {
      // Unable to fetch user data.
      return new JsonResponse([], 500);
    }

    // @todo Application uuid logic.
    return new JsonResponse($settings->toArray());
  }

  /**
   * Preview un-editable application form.
   */
  public function preview(string $id): JsonResponse {
    return new JsonResponse(
      $this->formSettingsService->getFormSettings($id)->toArray()
    );
  }

  /**
   * Edit existing draft or submitted application.
   */
  public function editApplication(string $id, string $uuid): JsonResponse {
    $settings = $this->formSettingsService->getFormSettings($id);

    // Get data from ATV.
    $atv_form_data = '{"firstname": "matti"}';
    $settings->setFormData(json_decode($atv_form_data, TRUE));

    return new JsonResponse($settings->toArray());
  }

  /**
   * Send the application to ATV.
   */
  public function submitDraftApplication(Request $request): JsonResponse {
    // Placeholder for saving a draft.
    $content = json_decode($request->getContent(), TRUE);
    [$form_data] = $content;

    if (!$form_data) {
      $form_data = [];
    }

    try {
      // @todo Check around initApplication how to create and send a document.
    }
    catch (\Exception $e) {
      // @todo Log and proper response
      return new JsonResponse([], 500);
    }

    return new JsonResponse([], 200);
  }

  /**
   * Submit application to Avus2.
   */
  public function submitApplication(Request $request): JsonResponse {
    // Placeholder for submitting the application.
    $content = json_decode($request->getContent(), TRUE);
    [$form_data] = $content;

    // @todo original implementation is in ApplicationUploaderService.
    // It requires a lot of modification.
    if (!$form_data) {
      return new JsonResponse([], 500);
    }

    // This->applicationUploaderService->handleApplicationUploadViaIntegration().
    return new JsonResponse([], 200);
  }

}
