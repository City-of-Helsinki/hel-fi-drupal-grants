<?php

declare(strict_types=1);

namespace Drupal\grants_application;

use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_handler\ApplicationStatusService;

/**
 * Decides which changes the applicant may make to a React form application.
 *
 * Mirrors the rules the webform-based applications enforce through
 * ApplicationStatusService::isSubmissionChangesAllowed().
 */
final class ApplicationEditPolicy {

  public function __construct(
    private readonly ApplicationStatusService $applicationStatusService,
  ) {
  }

  /**
   * Whether a new draft can be created.
   *
   * @param \Drupal\grants_application\Form\FormSettings $settings
   *   The form settings.
   *
   * @return bool
   *   TRUE if the application round is open.
   */
  public function canCreateDraft(FormSettings $settings): bool {
    return $settings->isApplicationOpen();
  }

  /**
   * Whether an existing application can be saved as a draft.
   *
   * @param \Drupal\grants_application\Form\FormSettings $settings
   *   The form settings.
   * @param string $status
   *   The current status of the stored ATV document.
   *
   * @return bool
   *   TRUE if the application is a draft and the round is open.
   */
  public function canSaveDraft(FormSettings $settings, string $status): bool {
    return $this->isDraft($status) && $settings->isApplicationOpen();
  }

  /**
   * Whether an application can be submitted.
   *
   * A draft can be submitted while the round is open. A SUBMITTED application
   * can be sent again, because a submission that failed in the integration
   * stays SUBMITTED and the applicant must be able to retry it.
   *
   * @param \Drupal\grants_application\Form\FormSettings $settings
   *   The form settings.
   * @param string $status
   *   The current status of the stored ATV document.
   *
   * @return bool
   *   TRUE if the application can be submitted.
   */
  public function canSubmit(FormSettings $settings, string $status): bool {
    if ($this->isDraft($status)) {
      return $settings->isApplicationOpen();
    }
    return $status === $this->applicationStatusService->getApplicationStatuses()['SUBMITTED'];
  }

  /**
   * Whether a submitted application can still be changed.
   *
   * The applicant may change a submitted application, even after the round
   * has closed, until the handler takes it into processing.
   *
   * @param string $status
   *   The current status of the stored ATV document.
   *
   * @return bool
   *   TRUE if the application can be updated.
   */
  public function canUpdateSubmitted(string $status): bool {
    return !$this->isDraft($status) && $this->applicationStatusService->isSubmissionEditable(NULL, $status);
  }

  /**
   * Whether the given status is DRAFT.
   */
  private function isDraft(string $status): bool {
    return $status === $this->applicationStatusService->getApplicationStatuses()['DRAFT'];
  }

}
