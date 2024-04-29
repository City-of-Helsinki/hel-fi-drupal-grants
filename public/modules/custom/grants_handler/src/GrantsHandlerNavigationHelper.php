<?php

namespace Drupal\grants_handler;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines a helper class for the webform navigation module.
 */
class GrantsHandlerNavigationHelper {

  /**
   * Name of the table where log entries are stored.
   */
  protected const TABLE = 'grants_handler_log';

  /**
   * Name of the error operation.
   */
  public const ERROR_OPERATION = 'errors';

  /**
   * Name of the page visited operation.
   */
  protected const PAGE_VISITED_OPERATION = 'page visited';

  /**
   * Name of the navigation handler.
   */
  protected const HANDLER_ID = 'grants_handler_navigation';

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The time service.
   */
  protected readonly TimeInterface $timeInterface;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * Access to profile data.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData;

  /**
   * DB result cache.
   *
   * @var array
   */
  protected array $cache;

  /**
   * AutosaveHelper constructor.
   */
  public function __construct(
    Connection $datababse,
    MessengerInterface $messenger,
    EntityTypeManagerInterface $entity_type_manager,
    FormBuilderInterface $form_builder,
    HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
    TimeInterface $timeInterface,
  ) {

    $this->timeInterface = $timeInterface;
    $this->database = $datababse;
    $this->messenger = $messenger;
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->helsinkiProfiiliUserData = $helsinkiProfiiliUserData;

    $this->cache = [];
  }

  /**
   * Gets the current submission page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   A webform submission entity.
   *
   * @return string
   *   The current submission page ID.
   */
  public function getCurrentPage(WebformSubmissionInterface $webformSubmission): string {
    $pages = $webformSubmission->getWebform()
      ->getPages('edit', $webformSubmission);
    return empty($webformSubmission->getCurrentPage()) ? array_keys($pages)[0] : $webformSubmission->getCurrentPage();
  }

  /**
   * Has visited page.
   *
   * Saves errors & page visits to db.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   A webform submission entity.
   * @param string $page
   *   The page we're checking.
   *
   * @return bool
   *   TRUE if the user has previously visited the page.
   */
  public function hasVisitedPage(WebformSubmissionInterface $webformSubmission, ?string $page): bool {
    // Get outta here if the submission hasn't been saved yet.
    if (empty($webformSubmission->id())) {
      return FALSE;
    }
    // Set the page to the current page if it is empty.
    if (empty($page)) {
      $page = $this->getCurrentPage($webformSubmission);
    }
    $submissionLog = $this->getPageVisits($webformSubmission);
    $hasVisited = FALSE;
    foreach ($submissionLog as $entry) {
      if ($entry->page == $page) {
        $hasVisited = TRUE;
        break;
      }
    }
    return $hasVisited;
  }

  /**
   * Gets either all errors or errors for a specific page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   A webform submission entity.
   * @param string|null $page
   *   Set to page name if you only want the data for a particular page.
   *
   * @return array
   *   An array of errors.
   */
  public function getErrors(
    WebformSubmissionInterface $webformSubmission,
    string $page = NULL): array {

    if (empty($webformSubmission->id())) {
      return [];
    }
    $webformId = $webformSubmission->getWebform()->id();
    if (isset($this->cache[$webformId]['errors'])) {
      $data = $this->cache[$webformId]['errors'];
    }
    else {
      $query = $this->database->select(self::TABLE, 'l');
      $query->condition('webform_id', $webformId);
      $query->condition('operation', self::ERROR_OPERATION);
      $query->condition('sid', $webformSubmission->id());

      $query->fields('l', [
        'lid',
        'sid',
        'data',
        'page',
      ]);
      $query->orderBy('l.page', 'ASC');
      $submission_log = $query->execute()->fetchAll();
      $data = [];
      foreach ($submission_log as $entry) {
        // phpcs:disable
        $data[$entry->page] = unserialize($entry->data);
        // phpcs:enable
      }
      $this->cache[$webformId]['errors'] = $data;
    }
    return $data[$page] ?? $data;
  }

  /**
   * Get errors for all pages any status.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   Submission object.
   *
   * @return array
   *   All errors.
   */
  public function getAllErrors(WebformSubmissionInterface $webformSubmission): array {
    /** @var \Drupal\webform\Entity\Webform $webform */
    $webform = $webformSubmission->getWebform();

    // If called without saved submission, let's not even try to get errors.
    if (!$webformSubmission->id()) {
      return [];
    }
    // Get pages.
    $pages = $webform->getPages('edit', $webformSubmission);
    $all_errors = $this->getErrors($webformSubmission);
    foreach ($pages as $name => $page) {
      if (!in_array($name, ['webform_confirmation', 'webform_preview'], TRUE) && !empty($all_errors[$name])) {
        $all_errors[$name] += ['title' => $page['#title']];
      }
    }
    return $all_errors;
  }

  /**
   * Filter page visits from stored data.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   Submission in question. Either saved or non saved one.
   *
   * @return array
   *   Stored page visits.
   */
  public function getPageVisits(WebformSubmissionInterface $webformSubmission): array {
    if (!$webformSubmission->id()) {
      return [];
    }
    $query = $this->database->select(self::TABLE, 'l');
    $query->condition('sid', $webformSubmission->id());
    $cacheKey = $webformSubmission->getWebform()->id();
    if (isset($this->cache[$cacheKey]['visits'])) {
      $submission_log = $this->cache[$cacheKey]['visits'];
    }
    else {
      $query->condition('webform_id', $webformSubmission->getWebform()->id());

      $query->condition('operation', self::PAGE_VISITED_OPERATION);
      $query->fields('l', [
        'lid',
        'sid',
        'page',
        'data',
      ]);
      $query->orderBy('l.lid', 'DESC');
      $submission_log = $query->execute()->fetchAll();
      $this->cache[$cacheKey]['visits'] = $submission_log;
    }

    return $submission_log;
  }

  /**
   * Logs the current submission page.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   A webform submission entity.
   * @param ?string $page
   *   The page to log.
   *
   * @throws \Exception
   */
  public function logPageVisit(WebformSubmissionInterface $webformSubmission, ?string $page) {
    // Set the page to the current page if it is empty.
    if (empty($page)) {
      $page = $this->getCurrentPage($webformSubmission);
    }
    $hasVisitedPage = $this->hasVisitedPage($webformSubmission, $page);

    // If submission is not saved, just return with nothing.
    if (empty($webformSubmission->id())) {
      // And return to stop execution.
      return;
    }

    $data = $webformSubmission->getData();
    // Only log the page if they haven't already visited it.
    if (!$hasVisitedPage) {
      $userData = $this->helsinkiProfiiliUserData->getUserData();
      $fields = [
        'webform_id' => $webformSubmission->getWebform()->id(),
        'sid' => $webformSubmission->id(),
        'operation' => self::PAGE_VISITED_OPERATION,
        'handler_id' => self::HANDLER_ID,
        'application_number' => $data['application_number'] ?? '',
        'uid' => $this->helsinkiProfiiliUserData->getCurrentUser()->id(),
        'user_uuid' => $userData['sub'] ?? '',
        'data' => $page,
        'page' => $page,
        'timestamp' => (string) $this->timeInterface->getRequestTime(),
      ];
      $this->cache[$webformSubmission->getWebform()->id()]['visits'] = NULL;
      $query = $this->database->insert(self::TABLE, $fields);
      $query->fields($fields)->execute();
    }
  }

  /**
   * Logs the current submission errors.
   *
   * And if no errors on current page, then remove item form database to mark.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   A webform submission entity.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form's form_state.
   *
   * @throws \Exception
   */
  public function logPageErrors(WebformSubmissionInterface $webformSubmission, FormStateInterface $form_state) {
    // Get form errors for this page.
    $form_errors = $form_state->getErrors();
    $current_page = $this->getCurrentPage($webformSubmission);
    if (empty($form_errors)) {
      $this->deleteSubmissionLogs($webformSubmission, self::ERROR_OPERATION, $current_page);
    }
    else {
      $this->logErrors($webformSubmission, $form_errors, $current_page);
    }
    return $form_errors;
  }

  /**
   * Logs errors.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   A webform submission entity.
   * @param array $errors
   *   Array of errors to log.
   * @param string $page
   *   Page for which errors are logged.
   *
   * @throws \Exception
   */
  public function logErrors(WebformSubmissionInterface $webformSubmission, array $errors, string $page) {

    $wfId = $webformSubmission->id();
    // Get out from here if the submission hasn't been saved yet.
    if ($wfId == NULL) {
      return;
    }
    if (!empty($errors)) {

      if (empty($page)) {
        $page = $this->getCurrentPage($webformSubmission);
      }

      $userData = $this->helsinkiProfiiliUserData->getUserData();
      $data = $webformSubmission->getData();
      $fields = [
        'webform_id' => $webformSubmission->getWebform()->id(),
        'sid' => $webformSubmission->id(),
        'operation' => self::ERROR_OPERATION,
        'handler_id' => self::HANDLER_ID,
        'application_number' => $data['application_number'] ?? '',
        'uid' => $this->helsinkiProfiiliUserData->getCurrentUser()->id(),
        'user_uuid' => $userData['sub'] ?? '',
        'data' => serialize($errors),
        'page' => $page,
        'timestamp' => (string) $this->timeInterface->getRequestTime(),
      ];
      $this->database->insert(self::TABLE)->fields($fields)->execute();
      $webformId = $webformSubmission->getWebform()->id();
      $this->cache[$webformId]['errors'] = NULL;
    }
  }

  /**
   * Delete submission logs.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   A webform submission entity.
   * @param string $operation
   *   Operation to be deleted. Either errors or page visits. If omitted, both
   *   will be deleted.
   * @param string $page
   *   Page we want to delete logs from.
   *
   * @return int
   *   Num of rows
   */
  public function deleteSubmissionLogs(
    WebformSubmissionInterface $webformSubmission,
    string $operation = '',
    string $page = ''
  ): int {
    // Get outta here if the submission hasn't been saved yet.
    if (empty($webformSubmission->id())) {
      return 0;
    }

    $data = $webformSubmission->getData();

    $query = $this->database->delete(self::TABLE);
    $query->condition('webform_id', $webformSubmission->getWebform()->id());
    $query->condition('application_number', $data['application_number']);

    // If given page, delete only that, otherwise delete all related to
    // this application.
    if ($operation !== '') {
      $query->condition('operation', $operation);
    }
    if ($page !== '') {
      $query->condition('page', $page);
    }
    return $query->execute();
  }

  /**
   * Gets a page an element is located at.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform entity.
   * @param string $element
   *   A webform element.
   *
   * @return mixed
   *   A page an element belongs to.
   */
  public function getElementPage(WebformInterface $webform, string $element): mixed {
    $element = $webform->getElement($element);
    return !empty($element) && array_key_exists('#webform_parents', $element) ? $element['#webform_parents'][0] : NULL;
  }

  /**
   * Get current page's validation errors parsed to paged error messages.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param \Drupal\webform\WebformSubmissionInterface $webformSubmission
   *   Submission object.
   *
   * @return array
   *   All errors paged.
   */
  public function getPagedErrors(FormStateInterface $form_state, WebformSubmissionInterface $webformSubmission): array {
    // Get form errors for this page.
    $form_errors = $form_state->getErrors();
    $current_page = $webformSubmission->getCurrentPage();

    $paged_errors = [];

    foreach ($form_errors as $element => $error) {
      $base_element = explode('][', $element)[0];
      // application_number.
      $page = $this->getElementPage($webformSubmission->getWebform(), $base_element);
      // Place error on current page if the page is empty.
      if (!empty($page) && is_string($page)) {
        $paged_errors[$page][$element] = $error;
      }
      else {
        $paged_errors[$current_page][$element] = $error;
      }
    }
    return $paged_errors;
  }

}
