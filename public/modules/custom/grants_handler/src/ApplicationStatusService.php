<?php

namespace Drupal\grants_handler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to handle application statuses.
 */
final class ApplicationStatusService implements ContainerInjectionInterface {

  use DebuggableTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Log errors.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * The application statuses.
   *
   * @var array
   */
  protected array $applicationStatuses;

  /**
   * New status header.
   *
   * @var string
   */
  private string $newStatusHeader;

  /**
   * Constructs a new ApplicationStatusService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerChannelFactory
   *   The logger channel factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $loggerChannelFactory) {
    $this->configFactory = $config_factory;
    $this->logger = $loggerChannelFactory->get('ApplicationStatusService');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new ApplicationStatusService(
      $container->get('config.factory'),
      $container->get('logger.factory')
    );
  }

  /**
   * Get application statuses from config.
   *
   * @return array
   *   Application statuses parsed from active config.
   */
  public function getApplicationStatuses(): array {
    if (!isset($this->applicationStatuses)) {
      $config = $this->configFactory->get('grants_metadata.settings');
      $thirdPartyOpts = $config->get('third_party_options');
      $this->applicationStatuses = (array) $thirdPartyOpts['application_statuses'];
    }

    return $this->applicationStatuses;
  }

  /**
   * Check if given submission is allowed to be edited.
   *
   * @param \Drupal\webform\Entity\WebformSubmission|null $submission
   *   Submission in question.
   * @param string $status
   *   If no object is available, do text comparison.
   *
   * @return bool
   *   Is submission editable?
   */
  public function isSubmissionEditable(
    ?WebformSubmission $submission,
    string $status = '',
  ): bool {
    if (NULL === $submission) {
      $submissionStatus = $status;
    }
    else {
      $data = $submission->getData();
      $submissionStatus = $data['status'];

    }

    if (in_array($submissionStatus, [
      $this->applicationStatuses['DRAFT'],
      $this->applicationStatuses['SUBMITTED'],
      $this->applicationStatuses['RECEIVED'],
      $this->applicationStatuses['PREPARING'],
    ])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if given submission is allowed to have changes.
   *
   * User should be allowed to edit their submission, even if the
   * application period is over, unless handler has changed the status
   * to processing or something else.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Submission in question.
   *
   * @return bool
   *   Is submission editable?
   */
  public function isSubmissionChangesAllowed(
    WebformSubmission $webform_submission,
  ): bool {

    $submissionData = $webform_submission->getData();
    $status = $submissionData['status'];

    $isOpen = $this->isApplicationOpen($webform_submission->getWebform());
    if (!$isOpen && $status === $this->getApplicationStatuses()['DRAFT']) {
      return FALSE;
    }

    return $this->isSubmissionEditable($webform_submission);
  }

  /**
   * Check if application is open.
   *
   * In reality check if given date is between other dates.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform.
   *
   * @return bool
   *   Is or not open.
   */
  public function isApplicationOpen(Webform $webform): bool {

    $thirdPartySettings = $webform->getThirdPartySettings('grants_metadata');
    $applicationContinuous = $thirdPartySettings["applicationContinuous"] == 1;

    try {
      $now = new \DateTime();
      $from = new \DateTime($thirdPartySettings["applicationOpen"]);
      $to = new \DateTime($thirdPartySettings["applicationClose"]);
    }
    catch (\Exception $e) {

      $this->logger
        ->error('isApplicationOpen date error: @error', ['@error' => $e->getMessage()]);
      return $applicationContinuous;
    }

    $status = $this->getWebformStatus($webform);
    $appEnv = Helpers::getAppEnv();
    $isProd = Helpers::isProduction($appEnv);

    if (
      ($isProd && $status !== 'released') ||
      $status === 'archived'
    ) {
      return FALSE;
    }

    // If today is between open & close dates return true.
    if ($now->getTimestamp() > $from->getTimestamp() && $now->getTimestamp() < $to->getTimestamp()) {
      return TRUE;
    }
    // Otherwise return true if is continuous, false if not.
    return $applicationContinuous;

  }

  /**
   * Check if given submission status can be set to SUBMITTED.
   *
   * Ie, will submission be sent to Avus2 by integration. Currently only DRAFT
   * -> SUBMITTED is allowed for end user.
   *
   * @param \Drupal\webform\Entity\WebformSubmission|null $submission
   *   Submission in question.
   * @param string|null $status
   *   If no object is available, do text comparison.
   *
   * @return bool
   *   Is submission editable?
   */
  public function canSubmissionBeSubmitted(
    ?WebformSubmission $submission,
    ?string $status,
  ): bool {
    if (NULL === $submission) {
      $submissionStatus = $status;
    }
    else {
      $data = $submission->getData();
      $submissionStatus = $data['status'];
    }

    if (in_array($submissionStatus, [
      'DRAFT',
    ])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if given submission is allowed to be edited.
   *
   * @param array|null $submission
   *   An array of submission data.
   * @param string $status
   *   If no object is available, do text comparison.
   *
   * @return bool
   *   Is submission editable?
   */
  public function isSubmissionFinished(?array $submission, string $status = ''): bool {
    if (NULL === $submission) {
      $submissionStatus = $status;
    }
    else {
      $submissionStatus = $submission['status'];
    }

    $applicationStatuses = $this->getApplicationStatuses();

    if (in_array($submissionStatus, [
      $applicationStatuses['READY'],
      $applicationStatuses['DONE'],
      $applicationStatuses['DELETED'],
      $applicationStatuses['CANCELED'],
      $applicationStatuses['CANCELLED'],
      $applicationStatuses['CLOSED'],
    ])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Figure out status for new or updated application submission.
   *
   * @param string $triggeringElement
   *   Element clicked.
   *   Form specs.
   *   State of form.
   * @param array $submittedFormData
   *   Submitted data.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Submission object.
   *
   * @return string
   *   Status for application, unchanged if no specific update done.
   */
  public function getNewStatus(
    string $triggeringElement,
    array $submittedFormData,
    WebformSubmissionInterface $webform_submission,
  ): string {
    $status = $submittedFormData['status'] ?? $this->applicationStatuses['DRAFT'];

    if ($triggeringElement == '::submitForm') {
      $status = $this->applicationStatuses['DRAFT'];
    }
    elseif ($triggeringElement == '::submit' && $this->canSubmissionBeSubmitted($webform_submission, NULL)) {
      if ($status == 'DRAFT' || $status == '') {
        $status = $this->applicationStatuses['SUBMITTED'];
      }
    }

    $this->newStatusHeader = $status;
    return $this->newStatusHeader;
  }

  /**
   * Get updated status header. Empty if no updates.
   *
   * @return string
   *   New status or empty
   */
  public function getNewStatusHeader(): string {
    return $this->newStatusHeader;
  }

  /**
   * Get webform status string from third party settings.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform object to check.
   *
   * @return string
   *   Status string
   */
  public function getWebformStatus(Webform $webform): string {
    $thirdPartySettings = $webform->getThirdPartySettings('grants_metadata');
    $status = $thirdPartySettings['status'] ?? 'development';

    return $status;
  }

}
