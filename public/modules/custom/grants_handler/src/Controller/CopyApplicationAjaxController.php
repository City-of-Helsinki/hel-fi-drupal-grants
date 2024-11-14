<?php

declare(strict_types=1);

namespace Drupal\grants_handler\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Url;
use Drupal\grants_events\EventsService;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationInitService;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\grants_mandate\CompanySelectException;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Grants Handler routes.
 */
final class CopyApplicationAjaxController extends ControllerBase {

  /**
   * Constructs a new CopyApplicationAjaxController object.
   *
   * @param \Drupal\grants_handler\ApplicationGetterService $applicationGetterService
   *   The application getter service.
   * @param \Drupal\grants_handler\ApplicationInitService $applicationInitService
   *   The application init service.
   * @param \Drupal\grants_events\EventsService $eventsService
   *   The events service.
   * @param \Drupal\grants_handler\ApplicationStatusService $applicationStatusService
   *   The application status service.
   */
  public function __construct(
    private readonly ApplicationGetterService $applicationGetterService,
    private readonly ApplicationInitService $applicationInitService,
    private readonly EventsService $eventsService,
    private readonly ApplicationStatusService $applicationStatusService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): CopyApplicationAjaxController {
    return new self(
      $container->get('grants_handler.application_getter_service'),
      $container->get('grants_handler.application_init_service'),
      $container->get('grants_events.events_service'),
      $container->get('grants_handler.application_status_service'),
    );
  }

  /**
   * Builds the response.
   *
   * @param string $submission_id
   *   The submission ID.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  public function __invoke(string $submission_id): RedirectResponse {
    $oldApplicationUrl = $this->getOldApplicationUrl($submission_id);

    try {
      $webform_submission = $this->getWebformSubmission($submission_id);
      $webform = $webform_submission->getWebForm();
      $this->validateApplication($webform);

      $newSubmission = $this->initNewApplication($webform->id(), $webform_submission->getData());
      $this->logEvent($newSubmission, $webform_submission->getData());

      return $this->getNewApplicationRedirect($webform, $newSubmission);
    }
    catch (\Throwable $e) {
      $this->messenger()->addError('Application copying failed.');
      $this->getLogger('grants_handler')
        ->error('Application copying failed: @message', ['@message' => $e->getMessage()]);
      return new RedirectResponse($oldApplicationUrl->toString());
    }
  }

  /**
   * Gets the URL of the old application.
   *
   * @param string $submission_id
   *   The submission ID.
   *
   * @return \Drupal\Core\Url
   *   The URL of the old application.
   */
  private function getOldApplicationUrl(string $submission_id): Url {
    return Url::fromRoute('grants_handler.view_application', ['submission_id' => $submission_id]);
  }

  /**
   * Gets the webform submission object.
   *
   * @param string $submission_id
   *   The submission ID.
   *
   * @return \Drupal\webform\Entity\WebformSubmission
   *   The webform submission object.
   *
   * @throws \RuntimeException
   *   If the webform submission cannot be retrieved.
   */
  private function getWebformSubmission(string $submission_id): WebformSubmission {
    try {
      return $this->applicationGetterService->submissionObjectFromApplicationNumber($submission_id);
    }
    catch (EntityStorageException | CompanySelectException $e) {
      throw new \RuntimeException('Failed to get webform submission.');
    }
  }

  /**
   * Validates the application.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform object.
   *
   * @throws \RuntimeException
   *   If the application cannot be copied.
   */
  private function validateApplication(WebformInterface $webform): void {
    $thirdPartySettings = $webform->getThirdPartySettings('grants_metadata');
    $isApplicationArchived = $thirdPartySettings["status"] === 'archived' ?? TRUE;
    $isApplicationOpen = $this->applicationStatusService->isApplicationOpen($webform);

    if ($thirdPartySettings["disableCopying"] === 1 || $isApplicationArchived || !$isApplicationOpen) {
      throw new \RuntimeException('Application copying disabled.');
    }
  }

  /**
   * Initializes a new application with copied data.
   *
   * @param string $webform_id
   *   The webform ID.
   * @param array $data
   *   The data to initialize the new application with.
   *
   * @return \Drupal\webform\Entity\WebformSubmission
   *   The new webform submission object.
   *
   * @throws \RuntimeException
   *   If the new application cannot be initialized.
   */
  private function initNewApplication(string $webform_id, array $data): WebformSubmission {
    try {
      return $this->applicationInitService->initApplication($webform_id, $data);
    }
    catch (\Throwable $e) {
      throw new \RuntimeException('Failed to initialize new application.');
    }
  }

  /**
   * Logs the event of copying the application.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $newSubmission
   *   The new webform submission object.
   * @param array $oldData
   *   The data of the old application.
   *
   * @throws \Drupal\grants_events\EventException
   */
  private function logEvent(WebformSubmission $newSubmission, array $oldData): void {
    $newData = $newSubmission->getData();
    $this->messenger()
      ->addStatus($this->t('Grant application copied, new id: <span id="saved-application-number">@number</span>', ['@number' => $newData['application_number']], ['context' => 'grants_handler']));
    $this->eventsService->logEvent($newData['application_number'], 'HANDLER_APP_COPIED', $this->t('Application copied from application id: @id', ['@id' => $oldData['application_number']], ['context' => 'grants_handler'])
      ->render(), $newData['application_number']);
  }

  /**
   * Gets the redirect response for the new application.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform object.
   * @param \Drupal\webform\Entity\WebformSubmission $newSubmission
   *   The new webform submission object.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   The redirect response.
   */
  private function getNewApplicationRedirect(WebformInterface $webform, WebformSubmission $newSubmission): RedirectResponse {
    $newApplicationUrl = Url::fromRoute('grants_handler.edit_application', [
      'webform' => $webform->id(),
      'webform_submission' => $newSubmission->id(),
    ]);
    return new RedirectResponse($newApplicationUrl->toString());
  }

}
