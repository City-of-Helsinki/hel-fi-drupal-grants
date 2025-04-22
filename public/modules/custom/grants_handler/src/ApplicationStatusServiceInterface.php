<?php

namespace Drupal\grants_handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Interface for the application status service.
 */
interface ApplicationStatusServiceInterface {

  /**
   * Gets application status strings from configuration.
   *
   * @return string[]
   *   An associative array of status machine names and their labels.
   */
  public function getApplicationStatuses(): array;

  /**
   * Checks if a given submission is editable based on its status.
   *
   * @param \Drupal\webform\Entity\WebformSubmission|null $submission
   *   The webform submission to check, or NULL.
   * @param string $status
   *   Status to check if submission is not provided.
   *
   * @return bool
   *   TRUE if the submission is editable, FALSE otherwise.
   */
  public function isSubmissionEditable(?WebformSubmission $submission, string $status = ''): bool;

  /**
   * Checks if changes are allowed to a given submission.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   The webform submission.
   *
   * @return bool
   *   TRUE if changes are allowed, FALSE otherwise.
   */
  public function isSubmissionChangesAllowed(WebformSubmission $webform_submission): bool;

  /**
   * Checks if the application associated with a webform is open.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform entity.
   *
   * @return bool
   *   TRUE if the application is open, FALSE otherwise.
   */
  public function isApplicationOpen(Webform $webform): bool;

  /**
   * Determines if a submission can be moved to the SUBMITTED state.
   *
   * @param \Drupal\webform\Entity\WebformSubmission|null $submission
   *   The webform submission or NULL.
   * @param string|null $status
   *   Status to check if submission is not provided.
   *
   * @return bool
   *   TRUE if the submission can be submitted, FALSE otherwise.
   */
  public function canSubmissionBeSubmitted(?WebformSubmission $submission, ?string $status): bool;

  /**
   * Checks if a submission has reached a terminal status.
   *
   * @param array|null $submission
   *   The submission data array or NULL.
   * @param string $status
   *   Fallback status value.
   *
   * @return bool
   *   TRUE if the submission is finished, FALSE otherwise.
   */
  public function isSubmissionFinished(?array $submission, string $status = ''): bool;

  /**
   * Determines the new status for a submission based on a triggering action.
   *
   * @param string $triggeringElement
   *   The triggering form element.
   * @param array $submittedFormData
   *   The submitted form data.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission object.
   *
   * @return string
   *   The new status to set.
   */
  public function getNewStatus(string $triggeringElement, array $submittedFormData, WebformSubmissionInterface $webform_submission): string;

  /**
   * Gets the updated status header.
   *
   * @return string
   *   The new status header, or an empty string if not updated.
   */
  public function getNewStatusHeader(): string;

  /**
   * Gets the current release status of a webform.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   The webform entity.
   *
   * @return string
   *   The release status (e.g., released, archived, etc.).
   */
  public function getWebformStatus(Webform $webform): string;

}
