<?php

declare(strict_types=1);

namespace Drupal\grants_handler;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\helfi_atv\AtvDocument;

/**
 * Builds the data for the oma-asiointi application list.
 *
 * The data is passed to the application list React app via drupalSettings.
 */
final class ApplicationListItemBuilder {

  use StringTranslationTrait;

  /**
   * Constructs a new ApplicationListItemBuilder.
   *
   * @param \Drupal\grants_handler\ApplicationStatusServiceInterface $applicationStatusService
   *   The application status service.
   * @param \Drupal\grants_handler\ApplicationGetterServiceInterface $applicationGetterService
   *   The application getter service.
   * @param \Drupal\grants_application\Form\FormSettingsServiceInterface $formSettingsService
   *   The form settings service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The config factory.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $stringTranslation
   *   The string translation service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger.
   */
  public function __construct(
    private readonly ApplicationStatusServiceInterface $applicationStatusService,
    private readonly ApplicationGetterServiceInterface $applicationGetterService,
    private readonly FormSettingsServiceInterface $formSettingsService,
    private readonly LanguageManagerInterface $languageManager,
    private readonly ConfigFactoryInterface $configFactory,
    TranslationInterface $stringTranslation,
    private readonly LoggerChannelInterface $logger,
  ) {
    $this->setStringTranslation($stringTranslation);
  }

  /**
   * Builds the list item data for an application.
   *
   * @param array<string, mixed> $application
   *   An application as returned by
   *   ApplicationGetterService::getCompanyApplications().
   *
   * @return array<string, mixed>|null
   *   The list item data, or NULL if the application can't be listed.
   */
  public function build(array $application): ?array {
    $submissionData = $application['#submission'] ?? NULL;
    $document = $application['#document'] ?? NULL;

    if (!$submissionData || !$document instanceof AtvDocument || empty($application['#submission_id'])) {
      return NULL;
    }

    $item = $application['#webform']
      ? $this->buildWebformApplication($submissionData, $document, (string) $application['#submission_id'])
      : $this->buildReactApplication($submissionData, $document, $application['#submission_entity'] ?? NULL);

    if (!$item) {
      return NULL;
    }

    $item['unreadMessages'] = $this->getUnreadMessagesText($submissionData['messages'] ?? []);

    return $item;
  }

  /**
   * Gets the human-readable status string.
   *
   * @param string $status
   *   The status, for example 'DRAFT'.
   * @param string|null $langcode
   *   The language code, defaults to the current language.
   *
   * @return string
   *   The human-readable status.
   */
  public function getStatusLabel(string $status, ?string $langcode = NULL): string {
    $langcode ??= $this->languageManager->getCurrentLanguage()->getId();
    $statusStrings = $this->configFactory->get('grants_handler.settings')->get('statusStrings');
    return $statusStrings[$langcode][$status] ?? $status;
  }

  /**
   * Builds the list item data for a webform application.
   *
   * @param array<string, mixed> $submissionData
   *   The submission data.
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The ATV document.
   * @param string $submissionId
   *   The webform submission id.
   *
   * @return array<string, mixed>|null
   *   The list item data.
   */
  private function buildWebformApplication(array $submissionData, AtvDocument $document, string $submissionId): ?array {
    $applicationNumber = ($submissionData['application_number'] ?? '') ?: $document->getMetadata()['applicationnumber'];

    if (!isset($submissionData['metadata']['form_uuid'])) {
      $this->logger->error('No form uuid found for submission @submissionId', ['@submissionId' => $applicationNumber]);
      return NULL;
    }

    $webform = $this->applicationGetterService->getWebformFromApplicationNumber($applicationNumber);
    if (!$webform) {
      return NULL;
    }

    // Older submissions may not have the status in the submission data.
    $status = $submissionData['status'] ?? $document->getStatus();
    if (!array_key_exists($document->getStatus(), $this->applicationStatusService->getApplicationStatuses())) {
      $this->logger->info('Application status not in valid statuses: %number', ['%number' => $applicationNumber]);
    }

    $timestamp = $status === 'DRAFT'
      ? ($submissionData['form_timestamp'] ?? NULL)
      : ($submissionData['form_timestamp_submitted'] ?? NULL);
    [$submitted, $submittedSortable] = $this->formatTimestamp($timestamp);

    $thirdPartySettings = $webform->getThirdPartySettings('grants_metadata');
    $isContinuous = !empty($thirdPartySettings['applicationContinuous']);
    $openStart = $this->toTimestamp($thirdPartySettings['applicationOpen'] ?? NULL);
    $openEnd = $this->toTimestamp($thirdPartySettings['applicationClose'] ?? NULL);

    $errorType = $this->getErrorType($isContinuous, $openStart, $openEnd);
    if (!$errorType && $this->applicationStatusService->getWebformStatus($webform) === 'archived') {
      $errorType = 'ARCHIVED';
    }

    return [
      'applicationNumber' => $applicationNumber,
      'formName' => (string) $webform->label(),
      'status' => $status,
      'statusLabel' => $this->getStatusLabel($status),
      'submitted' => $submitted,
      'submittedSortable' => $submittedSortable,
      'errorType' => $errorType,
      'links' => [
        'edit' => Url::fromRoute('grants_handler.edit_application', [
          'webform' => $webform->id(),
          'webform_submission' => $submissionId,
        ])->toString(),
        'delete' => Url::fromRoute('grants_handler.clear-navigations', [
          'submission_id' => $applicationNumber,
        ])->toString(),
        'view' => Url::fromRoute('grants_handler.view_application', [
          'submission_id' => $applicationNumber,
        ])->toString(),
        'print' => Url::fromRoute('grants_webform_print.submission_print', [
          'submission_id' => $applicationNumber,
        ])->toString(),
      ],
    ];
  }

  /**
   * Builds the list item data for a React form application.
   *
   * @param array<string, mixed> $submissionData
   *   The submission data.
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The ATV document.
   * @param mixed $submissionEntity
   *   The application submission entity.
   *
   * @return array<string, mixed>|null
   *   The list item data.
   */
  private function buildReactApplication(array $submissionData, AtvDocument $document, mixed $submissionEntity): ?array {
    if (!$submissionEntity instanceof ApplicationSubmission) {
      return NULL;
    }

    $language = $this->languageManager->getCurrentLanguage()->getId();
    $settings = $this->formSettingsService
      ->getFormSettings($submissionData['application_type_id'], $submissionData['form_identifier'])
      ->toArray();

    $status = $submissionData['status'] ?? $document->getStatus();
    $title = $settings['settings']['title'] ?? '';
    $isContinuous = (bool) ($settings['settings']['continuous'] ?? FALSE);
    $openStart = $this->toTimestamp($settings['settings']['application_open'] ?? NULL);
    $openEnd = $this->toTimestamp($settings['settings']['application_close'] ?? NULL);

    return [
      'applicationNumber' => $submissionData['application_number'],
      'formName' => $settings['translations'][$language]['translation']['form_title'] ?? $title,
      'status' => $status,
      'statusLabel' => $this->getStatusLabel($status, $submissionEntity->get('langcode')->value),
      'submitted' => $submissionEntity->getSubmittedDateTime('d.m.Y H:i'),
      'submittedSortable' => $submissionEntity->getSubmittedDateTime('Y-m-d\TH:i:s'),
      'errorType' => $this->getErrorType($isContinuous, $openStart, $openEnd),
      'links' => [
        'edit' => $submissionEntity->getEditApplicationLink($title)->getUrl()->toString(),
        'delete' => $submissionEntity->getDeleteApplicationUrl()->toString(),
        'view' => $submissionEntity->getViewApplicationLink($title, TRUE)->toString(),
        'print' => $status !== 'DRAFT' ? $submissionEntity->getPrintApplicationUrl()->toString() : NULL,
      ],
    ];
  }

  /**
   * Gets the error type for an application that can't be continued.
   *
   * @param bool $isContinuous
   *   Whether the application period is continuous.
   * @param int|null $openStart
   *   The application period start timestamp.
   * @param int|null $openEnd
   *   The application period end timestamp.
   *
   * @return string|null
   *   'NOT_OPEN' when the application period is not open, otherwise NULL.
   */
  private function getErrorType(bool $isContinuous, ?int $openStart, ?int $openEnd): ?string {
    $now = time();
    if (!$isContinuous && ($openStart > $now || $openEnd < $now)) {
      return 'NOT_OPEN';
    }
    return NULL;
  }

  /**
   * Converts a date string to a timestamp.
   *
   * @param string|null $date
   *   The date string.
   *
   * @return int|null
   *   The timestamp, or NULL if the date is empty or invalid.
   */
  private function toTimestamp(?string $date): ?int {
    if (empty($date)) {
      return NULL;
    }
    $timestamp = strtotime($date);
    return $timestamp === FALSE ? NULL : $timestamp;
  }

  /**
   * Formats a timestamp for display and sorting.
   *
   * @param string|null $timestamp
   *   The timestamp.
   *
   * @return array{0: string|null, 1: string|null}
   *   The formatted date and the sortable date.
   */
  private function formatTimestamp(?string $timestamp): array {
    if (empty($timestamp)) {
      return [NULL, NULL];
    }

    try {
      $date = new \DateTime($timestamp, new \DateTimeZone('Europe/Helsinki'));
    }
    catch (\Exception) {
      return [NULL, NULL];
    }

    return [$date->format('d.m.Y H:i'), $date->format('Y-m-d\TH:i:s')];
  }

  /**
   * Gets the unread messages text.
   *
   * @param array<int, array<string, mixed>> $messages
   *   The parsed application messages.
   *
   * @return string|null
   *   The text, or NULL if there are no unread messages.
   */
  private function getUnreadMessagesText(array $messages): ?string {
    $unread = array_filter(
      $messages,
      fn (array $message) => $message['sentBy'] == 'Avustusten kasittelyjarjestelma' && $message['messageStatus'] == 'UNREAD',
    );

    if (!$unread) {
      return NULL;
    }

    return (string) $this->formatPlural(count($unread), '1 new message', '@count new messages');
  }

}
