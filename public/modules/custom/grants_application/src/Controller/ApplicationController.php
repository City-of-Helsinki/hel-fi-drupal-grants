<?php

declare(strict_types=1);

namespace Drupal\grants_application\Controller;

use Drupal\content_lock\ContentLock\ContentLockInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\grants_application\ApplicationService;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_events\EventsService;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
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
    private readonly FormSettingsServiceInterface $formSettingsService,
    #[Autowire(service: 'grants_handler.application_status_service')]
    private readonly ApplicationStatusService $applicationStatusService,
    #[Autowire(service: 'grants_events.events_service')]
    private readonly EventsService $eventsService,
    #[Autowire(service: 'Drupal\grants_application\ApplicationService')]
    private readonly ApplicationService $applicationService,
    private readonly ContentLockInterface $contentLock,
    private readonly AccountProxyInterface $accountProxy,
  ) {
  }

  /**
   * Return appropriate translation for form title.
   *
   * @param string $form_identifier
   *   The application number.
   *
   * @return string
   *   The form title
   */
  public function getFormTitle(string $form_identifier): string {
    try {
      $formSettings = $this->formSettingsService->getFormSettingsByFormIdentifier($form_identifier);
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
   * @param string $form_identifier
   *   The form identifier.
   * @param string|null $application_number
   *   The application number to use for the form.
   * @param bool $use_draft
   *   Whether to use the draft version of the form.
   *
   * @return array|RedirectResponse
   *   The resulting array
   */
  public function formsApp(string $form_identifier, ?string $application_number, bool $use_draft): array|RedirectResponse {
    // Grant terms are stored in block.
    $blockStorage = $this->entityTypeManager()->getStorage('block_content');
    $terms_block = $blockStorage->load(1);

    $submission = $this->getApplicationSubmission($application_number);
    if ($application_number && !$submission) {
      throw new NotFoundHttpException();
    }

    // Check the application status, if it's still editable.
    if ($submission && !$submission->isDraft()) {
      try {
        $document = $this->helfiAtvService->getDocument($application_number);

        // @todo Should not use grants handler service to figure out is submission is editable.
        // It works but the implementation should live under this module.
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

    // Handle content locking.
    if ($submission && $this->contentLock->isLockable($submission)) {
      $uid = $this->accountProxy->id();
      $lock = $this->contentLock->fetchLock($submission);

      // Lock has different user.
      if ($lock && $lock->uid !== $uid) {
        $msg = $this->contentLock->displayLockOwner($lock, FALSE);
        $this->messenger()->addMessage($msg);
        return new RedirectResponse(Url::fromRoute('grants_oma_asiointi.front')->toString());
      }

      if (!$lock) {
        $this->contentLock->locking($submission, '*', (int) $uid, FALSE);
      }
    }

    $settings = $this->formSettingsService->getFormSettingsByFormIdentifier($form_identifier);

    // @todo Refactor, return early instead of skipping.
    // When the application doesn't exist yet, we skip all the code
    // and end up here, early return is better.
    return [
      '#theme' => 'forms_app',
      '#attached' => [
        'drupalSettings' => [
          'grants_react_form' => [
            'application_number' => $settings->getFormId(),
            'form_identifier' => $form_identifier,
            'token' => $this->csrfTokenGenerator->get('rest'),
            'list_view_path' => Url::fromRoute('grants_oma_asiointi.applications_list')->toString(),
            'terms' => [
              'body' => $terms_block->get('body')->value ?? '',
              'link_title' => $terms_block->get('field_link_title')->value ?? '',
            ],
            'use_draft' => $use_draft,
          ],
        ],
      ],
    ];
  }

  /**
   * Copy an existing application to a new draft.
   *
   * @param int $application_type_id
   *   The application type ID.
   * @param string $original_id
   *   The original application number to copy from.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function copyApplication(int $application_type_id, string $original_id) {
    try {
      $draft = $this->applicationService->createDraft($application_type_id, $original_id);
    }
    catch (\throwable $e) {
      $this->messenger()
        ->addError($this->t('Failed to copy the application. Please try again later.'));

      return new RedirectResponse(
        Url::fromRoute('grants_oma_asiointi.applications_list')->toString()
          );
    }

    return new RedirectResponse(
      Url::fromRoute(
        'helfi_grants.forms_app',
        [
          'id' => $application_type_id,
          'application_number' => $draft['application_number'],
        ],
        ['absolute' => TRUE],
      )->toString()
    );
  }

  /**
   * Upload file handler.
   *
   * @param string $application_number
   *   The application number.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function uploadFile(string $application_number, Request $request): JsonResponse {
    /** @var \Symfony\Component\HttpFoundation\File\File $file */
    $file = $request->files->get('file');
    if (!$file || !$application_number) {
      $this->getLogger('grants_application')->error("Failed to upload file, application number: $application_number");
      return new JsonResponse(['error' => $this->t('Something went wrong')], 400);
    }

    // @phpstan-ignore-next-line
    $file_original_name = $file->getClientOriginalName();
    if (strlen($file_original_name) >= 100) {
      return new JsonResponse(['error' => $this->t('File name is too long. Please rename the file and try again.')], 500);
    }

    try {
      $this->antivirusService->scan([
        $file_original_name => file_get_contents($file->getRealPath()),
      ]);
    }
    catch (AntivirusException $e) {
      $this->getLogger('grants_application')->error('File upload failed to antivirus check: ' . $e->getMessage());
      return new JsonResponse(
        ['error' => $this->t('File upload failed during antivirus-scan. Please try again in a moment')],
        400
      );
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
      ->loadByProperties(['application_number' => $application_number]);

    $submission = reset($submission);

    if (!$submission) {
      $this->getLogger('grants_application')
        ->error("Application does not exist in database while uploading a file: $application_number");
      return new JsonResponse(['error' => $this->t('Unable to find the application')], 400);
    }

    try {
      $result = $this->helfiAtvService->addAttachment(
        // @phpstan-ignore property.notFound
        $submission->document_id->value,
        $file_original_name,
        $file_entity
      );
    }
    catch (\Exception $e) {
      $this->getLogger('grants_application')->error("Failed to upload file: $file_original_name on application: $application_number");
      return new JsonResponse(['error' => $this->t('Failed to upload the file. Please try again in a moment')], 500);
    }

    if (!$result) {
      return new JsonResponse(status: 500);
    }

    $file_entity->delete();
    $response = [
      'fileName' => $result['filename'],
      'fileId' => $result['id'],
      'href' => $result['href'],
      'size' => $result['size'],
    ];

    // Add an upload event to the ATV-document.
    try {
      $this->eventsService->logEvent(
        $application_number,
        $this->eventsService->getEventTypes()['HANDLER_ATT_OK'],
        "Uploaded a file $file_original_name",
        $file_original_name,
      );
    }
    catch (\Exception $e) {
      // The event system is just a construct for manually tracking the state.
      // Afaik, failing to add an event does not affect the program itself,
      // it just helps admin-users to debug by checking the raw data.
      $this->getLogger('grants_application')
        ->error("Failed to log an event for file $file_original_name, application number: $application_number. Error: {$e->getMessage()}");
    }

    return new JsonResponse($response);
  }

  /**
   * Remove a file from ATV-document.
   *
   * The file cannot be removed if the application has already been submitted.
   *
   * @param string $application_number
   *   The application number.
   * @param string $attachmentId
   *   The attachment id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function removeFile(string $application_number, string $attachmentId): JsonResponse {
    $ids = $this->entityTypeManager()
      ->getStorage('application_submission')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('application_number', $application_number)
      ->execute();

    if (
      !$ids ||
      !$submission = ApplicationSubmission::load(reset($ids))
    ) {
      return new JsonResponse(['error' => $this->t('Application not found')], 404);
    }

    if (!$submission->isDraft()) {
      return new JsonResponse(['error' => $this->t('You are not allowed to remove the attachments any more.')], 403);
    }

    try {
      $deleted = $this->atvService->deleteAttachment($application_number, $attachmentId);
    }
    catch (\throwable $e) {
      // If file is no more present, we can just continue.
      if ($e instanceof AtvDocumentNotFoundException) {
        return new JsonResponse([], 200);
      }

      $this->getLogger('grants_application')
        ->error("Failed to delete attachment $attachmentId on application $application_number: {$e->getMessage()}");
      return new JsonResponse(['error' => $this->t('Failed to delete attachment')], 500);
    }

    if (!$deleted) {
      return new JsonResponse(['error' => $this->t('Failed to delete attachment')], 500);
    }

    // Add an upload event to the ATV-document.
    try {
      $this->eventsService->logEvent(
        $application_number,
        $this->eventsService->getEventTypes()['HANDLER_ATT_DELETED'],
        "Deleted a file $attachmentId",
        $attachmentId,
      );
    }
    catch (\Exception $e) {
      // Failing to add an event is acceptable situation.
      $this->getLogger('grants_application')
        ->error("Failed to log an event for file id $attachmentId, application number: $application_number. Error: {$e->getMessage()}");
    }

    return new JsonResponse([], 200);
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

    // No deleting if the application is locked.
    if ($this->contentLock->isLockable($submission)) {
      $uid = $this->accountProxy->id();
      $lock = $this->contentLock->fetchLock($submission);

      // Lock has different user.
      if ($lock && $lock->uid !== $uid) {
        $msg = $this->contentLock->displayLockOwner($lock, FALSE);
        $this->messenger()->addMessage($msg);
        return new RedirectResponse(Url::fromRoute('grants_oma_asiointi.front')->toString());
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
  private function getApplicationSubmission(?string $application_number): ?ApplicationSubmission {
    if (!$application_number) {
      return NULL;
    }

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
