<?php

namespace Drupal\grants_attachments\Controller;

use Drupal\Core\Access\AccessException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_attachments\Plugin\WebformElement\GrantsAttachments;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\grants_handler\ApplicationUploaderService;
use Drupal\grants_handler\EventsService;
use Drupal\grants_metadata\ApplicationDataService;
use Drupal\helfi_atv\AtvService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for grants_attachments routes.
 *
 * @phpstan-consistent-constructor
 */
class GrantsAttachmentsController extends ControllerBase {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The helfi_atv service.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $helfiAtv;

  /**
   * Create events.
   *
   * @var \Drupal\grants_handler\EventsService
   */
  protected EventsService $eventsService;

  /**
   * Requeststack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $request;

  /**
   * Application status service.
   *
   * @var \Drupal\grants_handler\ApplicationStatusService
   */
  protected ApplicationStatusService $applicationStatusService;

  /**
   * Application data service.
   *
   * @var \Drupal\grants_metadata\ApplicationDataService
   */
  protected ApplicationDataService $applicationDataService;

  /**
   * Application getter service.
   *
   * @var \Drupal\grants_handler\ApplicationGetterService
   */
  protected ApplicationGetterService $applicationGetterService;

  /**
   * Application uploader service.
   *
   * @var \Drupal\grants_handler\ApplicationUploaderService
   */
  protected ApplicationUploaderService $applicationUploaderService;

  /**
   * The controller constructor.
   *
   * @param \Drupal\helfi_atv\AtvService $helfi_atv
   *   The helfi_atv service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Drupal requests.
   * @param \Drupal\grants_handler\EventsService $eventsService
   *   Use submission events productively.
   * @param \Drupal\grants_handler\ApplicationStatusService $applicationStatusService
   *   Application status service.
   * @param \Drupal\grants_metadata\ApplicationDataService $applicationDataService
   *   Application data service.
   * @param \Drupal\grants_handler\ApplicationGetterService $applicationGetterService
   *   Application getter service.
   * @param \Drupal\grants_handler\ApplicationUploaderService $applicationUploaderService
   *   Application uploader service.
   */
  public function __construct(
    AtvService $helfi_atv,
    RequestStack $requestStack,
    EventsService $eventsService,
    ApplicationStatusService $applicationStatusService,
    ApplicationDataService $applicationDataService,
    ApplicationGetterService $applicationGetterService,
    ApplicationUploaderService $applicationUploaderService,
  ) {
    $this->helfiAtv = $helfi_atv;

    $this->request = $requestStack;
    $this->eventsService = $eventsService;
    $this->applicationStatusService = $applicationStatusService;
    $this->applicationDataService = $applicationDataService;
    $this->applicationGetterService = $applicationGetterService;
    $this->applicationUploaderService = $applicationUploaderService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('helfi_atv.atv_service'),
      $container->get('request_stack'),
      $container->get('grants_handler.events_service'),
      $container->get('grants_handler.application_status_service'),
      $container->get('grants_metadata.application_data_service'),
      $container->get('grants_handler.application_getter_service'),
      $container->get('grants_handler.application_uploader_service')
    );
  }

  /**
   * Delete attachment from given application.
   *
   * @param string $submission_id
   *   Submission.
   * @param string $integration_id
   *   Attachment integration id.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect back to form.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function deleteAttachment(string $submission_id, string $integration_id): RedirectResponse {
    $tOpts = ['context' => 'grants_attachments'];

    // Load submission & data.
    try {
      $submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($submission_id);
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
      return new RedirectResponse($this->request->getMainRequest()->get('destination'));
    }
    $submissionData = $submission->getData();
    // Rebuild integration id from url.
    $integrationId = str_replace('_', '/', $integration_id);
    $destination = $this->request->getMainRequest()->get('destination');

    if ($submissionData['status'] != $this->applicationStatusService->getApplicationStatuses()['DRAFT']) {
      throw new AccessException('Only application in DRAFT status allows attachments to be deleted.');
    }

    try {
      // Try to delete attachment directly.
      $attachmentDeleteResult = $this->helfiAtv->deleteAttachmentViaIntegrationId($integrationId);
      // If attachment got deleted.
      if ($attachmentDeleteResult) {
        $this->messenger()
          ->addStatus($this->t('Document file attachment deleted.', [], $tOpts));

        // Remove given attachment from application and store description.
        $attachmentHeaders = GrantsAttachments::$fileTypes;
        $attachmentFieldDescription = "";

        foreach ($submissionData['attachments'] as $key => $attachment) {
          if (
            (isset($attachment["integrationID"]) &&
              $attachment["integrationID"] != NULL) &&
            $attachment["integrationID"] == $integrationId) {
            unset($submissionData['attachments'][$key]);
            $attachmentFieldDescription = $attachmentHeaders[$attachment['fileType']];
          }
        }

        // Create event for deletion.
        $event = $this->eventsService->getEventData(
          'HANDLER_ATT_DELETED',
          $submission_id,
          $this->t('Attachment deleted from the field: @field.',
            ['@field' => $attachmentFieldDescription]
          ),
          $integrationId
        );

        // Add event.
        $submissionData['events'][] = $event;

        // Build data -> should validate ok, since we're
        // only deleting attachments & adding events..
        $applicationData = $this->applicationDataService->webformToTypedData(
          $submissionData);

        // Update in ATV.
        $applicationUploadStatus = $this->applicationUploaderService->handleApplicationUploadToAtv(
          $applicationData,
          $submission_id,
          $submissionData,
        );

        if ($applicationUploadStatus) {
          $this->messenger()->addStatus($this->t('Application updated.', [], $tOpts));

        }

      }
      else {
        $this->messenger()->addError('Attachment deletion failed.');
      }
    }
    catch (\Exception $e) {
      $this->messenger()->addError($e->getMessage());
      $this->getLogger('grants_attachments')
        ->error('Document attachment not found. IntegrationID: %inteId', ['%inteId' => $integrationId]);
    }

    return new RedirectResponse($destination);
  }

}
