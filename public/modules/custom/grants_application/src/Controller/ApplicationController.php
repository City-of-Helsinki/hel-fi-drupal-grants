<?php

declare(strict_types=1);

namespace Drupal\grants_application\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_av\AntivirusException;
use Drupal\helfi_av\AntivirusService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    #[Autowire(service: 'grants_handler.application_getter_service')]
    private readonly ApplicationGetterService $applicationGetterService,
    #[Autowire(service: 'helfi_atv.atv_service')]
    private readonly AtvService $atvService,
    private readonly FormSettingsService $formSettingsService,
    #[Autowire(service: 'grants_handler.application_status_service')]
    private readonly ApplicationStatusService $applicationStatusService,
  ) {
  }

  /**
   * Return appropriate translation for form title.
   *
   * @param string $id
   *   The application number.
   *
   * @return string
   *   The form title
   */
  public function getFormTitle(string $id): string {
    try {
      $formSettings = $this->formSettingsService->getFormSettings($id);
    }
    catch (\Exception $e) {
      return '';
    }

    $langcode = $this->languageManager()->getCurrentLanguage()->getId();
    return $formSettings->toArray()['translations'][$langcode]['translation']['form_title'];
  }

  /**
   * Render the forms react app.
   *
   * @param string $id
   *   The application number.
   * @param string|null $application_number
   *   The application number to use for the form.
   * @param bool $use_draft
   *   Whether to use the draft version of the form.
   *
   * @return array
   *   The resulting array
   */
  public function formsApp(string $id, ?string $application_number, bool $use_draft): array|RedirectResponse {
    // Grant terms are stored in block.
    $blockStorage = $this->entityTypeManager()->getStorage('block_content');
    $terms_block = $blockStorage->load(1);

    $submission = $this->getApplicationSubmission($application_number);
    if ($application_number && !$submission) {
      throw new NotFoundHttpException();
    }

    if ($submission && !$submission->isDraft()) {
      try {
        $document = $this->helfiAtvService->getDocument($application_number);

        if (!$this->applicationStatusService->isSubmissionEditable(NULL, $document->getStatus())) {
          $this->messenger()
            ->addError($this->t('The application is being processed. The application cannot be edited or submitted.'));

          return new RedirectResponse($this->getRedirectBackUrl($application_number)->toString());
        }
      }
      catch (\Throwable $e) {
        $this->messenger()
          ->addError($this->t('Your request was not fulfilled due to network error.', [], ['context' => 'grants_handler']));

        return new RedirectResponse($this->getRedirectBackUrl($application_number)->toString());
      }
    }

    return [
      '#theme' => 'forms_app',
      '#attached' => [
        'drupalSettings' => [
          'grants_react_form' => [
            'application_number' => $id,
            'token' => $this->csrfTokenGenerator->get('rest'),
            'list_view_path' => Url::fromRoute('grants_oma_asiointi.applications_list')->toString(),
            'terms' => [
              'body' => $terms_block->get('body')->getValue()[0]['value'],
              'link_title' => $terms_block->get('field_link_title')->getValue()[0]['value'],
            ],
            'use_draft' => $use_draft,
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

  /**
   * Remove an application.
   */
  public function removeApplication(string $id) {
    // @todo The original implementation and this must be done properly.
    $redirectUrl = Url::fromRoute('grants_oma_asiointi.front');
    $tOpts = ['context' => 'grants_handler'];

    try {
      $ids = $this->entityTypeManager()->getStorage('application_submission')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('application_number', $id)
        ->execute();

      if (!$ids) {
        $this->messenger()
          ->addError($this->t('Deleting draft failed. Error has been logged, please contact support.', [], $tOpts));
        $this->getLogger('grants_handler')
          ->error('Error: %error', ['%error' => "Cannot find application number $id"]);
        return new RedirectResponse($redirectUrl->toString());
      }

      $submission = ApplicationSubmission::load(reset($ids));
    }
    catch (\Exception  $e) {
      $this->messenger()
        ->addError($this->t('Deleting draft failed. Error has been logged, please contact support.', [], $tOpts));
      $this->getLogger('grants_handler')
        ->error('Error: %error', ['%error' => $e->getMessage()]);
      return new RedirectResponse($redirectUrl->toString());
    }
    $document = $this->applicationGetterService->getAtvDocument($id);

    if (!$submission || $submission->get('draft')->value !== "1") {
      if ($document->getStatus() !== 'DRAFT') {
        $this->messenger()
          ->addError($this->t('Only DRAFT status submissions are deletable', [], $tOpts));
        return new RedirectResponse($redirectUrl->toString());
      }
    }

    try {
      if ($this->atvService->deleteDocument($document)) {
        $submission->delete();
      }
    }
    catch (\Exception $e) {
      $this->messenger()
        ->addError($this->t('Deleting draft failed. Error has been logged, please contact support.', [], $tOpts));
      $this->getLogger('grants_handler')
        ->error('Error: %error', ['%error' => $e->getMessage()]);
    }

    return new RedirectResponse($redirectUrl->toString());
  }

  /**
   * Print the application.
   */
  public function printApplication() {
  }

  /**
   * Get the application submission entity.
   *
   * @param string $application_number
   *   The application number.
   *
   * @return \Drupal\grants_application\Entity\ApplicationSubmission|null
   *   The application submission entity or null if not found.
   */
  private function getApplicationSubmission(string $application_number): ?ApplicationSubmission {
    /** @var \Drupal\grants_application\Entity\ApplicationSubmission[] $submissions */
    $submissions = $this->entityTypeManager()
      ->getStorage('application_submission')
      ->loadByProperties(['application_number' => $application_number]);

    return $submissions ? reset($submissions) : NULL;
  }

  /**
   * Get the redirect back url.
   *
   * @param string|null $application_number
   *   The application number.
   *
   * @return \Drupal\Core\Url
   *   The redirect url.
   */
  private function getRedirectBackUrl(?string $application_number): Url {
    if ($application_number) {
      return Url::fromRoute('grants_handler.view_application', ['submission_id' => $application_number]);
    }
    return Url::fromRoute('grants_oma_asiointi.front');
  }

}
