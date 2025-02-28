<?php

declare(strict_types=1);

namespace Drupal\grants_admin_applications\Form;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_admin_applications\Service\ApplicationCleaner;
use Drupal\grants_admin_applications\Service\HandleDocumentsBatchService;
use Drupal\grants_handler\Helpers;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a grants_admin_applications form.
 *
 * @phpstan-consistent-constructor
 */
final class DeleteApplicationsForm extends FormBase {

  use AutowireTrait;

  /**
   * Class constructor.
   *
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   The AtvService service.
   * @param \Drupal\grants_admin_applications\Service\HandleDocumentsBatchService $handleDocumentsBatchService
   *   The HandleDocumentsBatchService.
   * @param \Drupal\grants_admin_applications\Service\ApplicationCleaner $applicationCleaner
   *   The application cleaner.
   */
  public function __construct(
    #[Autowire(service: 'helfi_atv.atv_service')]
    private readonly AtvService $atvService,
    private readonly HandleDocumentsBatchService $handleDocumentsBatchService,
    private readonly ApplicationCleaner $applicationCleaner,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'grants_admin_applications_delete_applications';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (str_contains(strtolower(Helpers::getAppEnv()), 'prod')) {
      $this->messenger()->addError('No deleting profiles in PROD environment');
      return [];
    }

    // Get user inputs.
    $input = $form_state->getUserInput();
    $uuid = $input['uuid'] ?? NULL;
    $type = $input['type'] ?? NULL;
    $businessId = $input['businessId'] ?? NULL;
    $status = $input['status'] ?? NULL;
    $appEnv = $input['appEnv'] ?? NULL;

    // Get the third party options.
    $config = $this->config('grants_metadata.settings');
    $thirdPartyOptions = $config->get('third_party_options');

    // Build the form.
    $form['uuid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UUID'),
      '#required' => FALSE,
      '#default_value' => $uuid ?? '',
      '#description' => $this->t('Enter a users UUID, e.g. 13cb60ae-269a-46da-9a43-da94b980c067'),
    ];

    $form['businessId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Business ID'),
      '#required' => FALSE,
      '#default_value' => $businessId ?? '',
      '#description' => $this->t('Enter a business ID, e.g. 7009192-1'),
    ];

    $form['appEnv'] = [
      '#type' => 'textfield',
      '#title' => $this->t('appEnv'),
      '#required' => FALSE,
      '#default_value' => $appEnv ?? '',
      '#description' => $this->t('Enter a app env, e.g. TEST'),
    ];

    $form['type'] = [
      '#title' => $this->t('Application type'),
      '#type' => 'select',
      '#options' => $this->buildApplicationTypeOptions($thirdPartyOptions),
      '#default_value' => 'all',
    ];

    $form['status'] = [
      '#title' => $this->t('Application status'),
      '#type' => 'select',
      '#options' => $this->buildApplicationStatusOptions($thirdPartyOptions),
      '#default_value' => 'all',
    ];

    $form['getData'] = [
      '#type' => 'button',
      '#value' => $this->t('Get Data'),
      '#name' => 'getdata',
      '#ajax' => [
        'callback' => '::getDataAtv',
        'disable-refocus' => FALSE,
        // Or TRUE to prevent re-focusing on the triggering element.
        'event' => 'click',
        'wrapper' => 'profile-data',
        // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Fetching data...'),
        ],
      ],
    ];

    $form['appData'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Documents in ATV for: @appEnv @uuid @bid @type @status', [
        '@uuid' => $uuid,
        '@appEnv' => $appEnv,
        '@bid' => $businessId,
        '@type' => $type,
        '@status' => $status,
      ]),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#prefix' => '<div id="profile-data">',
      '#suffix' => '</div>',
    ];

    // Build the application listing form elements.
    if ($uuid || $businessId) {
      try {
        $this->buildApplicationList($uuid, $businessId, $appEnv, $type, $status, $form_state, $form);
      }
      catch (\Throwable $e) {
        $this->messenger()->addError($e->getMessage());
      }
    }

    $form['actions']['delete_selected'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected'),
    ];

    $form['actions']['delete_all'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete all above'),
      '#attributes' => ['onclick' => 'if(!confirm("Delete ALL above?")){return false;}'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $form_state->clearErrors();
  }

  /**
   * Ajax callback event.
   *
   * @param array $form
   *   The triggering form render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state of current form.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object, holding current path and request uri.
   *
   * @return array
   *   Must return AjaxResponse object or render array.
   *   Never return NULL or invalid render arrays. This
   *   could/will break your forms.
   */
  public function getDataAtv(array &$form, FormStateInterface $form_state, Request $request): array {
    return $form['appData'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $triggeringElement = $form_state->getTriggeringElement();
    $storage = $form_state->getStorage();

    if (!isset($storage['documents'])) {
      $noDocumentsMessage = $this->t('No documents to delete.');
      $this->messenger()->addError($noDocumentsMessage);
      return;
    }

    $allDocuments = $storage['documents'];

    if (str_contains($triggeringElement['#id'], 'delete-all')) {
      $this->deleteDraftDocuments($allDocuments);
    }

    if (str_contains($triggeringElement['#id'], 'delete-selected')) {
      $selectOptions = $form_state->getValue('selectedDelete');
      $documents = $this->filterBySelection($allDocuments, $selectOptions);
      $this->deleteDraftDocuments($documents);
    }
  }

  /**
   * The filterBySelection function.
   *
   * This function filters all the passed in documents
   * so that only the ones that are found in the selected
   * $selectOptions are returned. Additionally, any documents
   * of the type grants_profile are filtered out, if every
   * document hasn't been selected.
   *
   * @param array $documents
   *   An array of ATV documents.
   * @param array $selectOptions
   *   An array of selected documents to be deleted.
   *
   * @return array
   *   An array of ATV documents that passes the filtering.
   */
  private function filterBySelection(array $documents, array $selectOptions): array {
    // Filter and collect applications to delete (checked checkboxes).
    $selectedToDelete = array_keys(array_filter($selectOptions, function ($value) {
      return $value;
    }));

    // Get the documents to delete based on the selections.
    return array_filter($documents, function (AtvDocument $document) use ($selectedToDelete, $documents) {
      // Check if the document is selected for deletion.
      if (!in_array($document->getId(), $selectedToDelete)) {
        return FALSE;
      }

      // Check if the document is a profile, which can only
      // be deleted if everything is deleted.
      if ($document->getType() == 'grants_profile' && count($selectedToDelete) < count($documents)) {
        $failedDeletionMessage = $this->t(
          'Skipped profile deletion: @tranId. Cannot delete profile while applications for it exist.', [
            '@tranId' => $document->getTransactionId(),
          ]
        );
        $this->messenger()->addWarning($failedDeletionMessage);
        $this->logger('grants_admin_applications')
          ->notice($failedDeletionMessage);
        return FALSE;
      }

      return TRUE;
    });
  }

  /**
   * The deleteDraftDocuments function.
   *
   * This function calls the handleDocumentsBatchService
   * service and deleted any passed in documents that have their
   * status set to DRAFT. Otherwise, a warning is displayed.
   *
   * @param array $documents
   *   An array of ATV documents.
   */
  private function deleteDraftDocuments(array $documents): void {
    $documentsToDelete = [];
    $documentsToKeep = [];

    /** @var \Drupal\helfi_atv\AtvDocument $document */
    foreach ($documents as $document) {
      if ($document->getStatus() === 'DRAFT') {
        $documentsToDelete[] = $document;
        continue;
      }
      if ($document->getType() !== 'grants_profile') {
        $documentsToKeep[] = $document->getTransactionId();
      }
    }

    if ($documentsToKeep) {
      $failedDeletionMessage = $this->t(
        'The following documents cannot be deleted since they are NOT marked as DRAFT. Delete them from Avus2 first: @tranIds.', [
          '@tranIds' => implode(', ', $documentsToKeep),
        ]
      );
      $this->messenger()->addWarning($failedDeletionMessage);
      $this->logger('grants_admin_applications')
        ->notice($failedDeletionMessage);
    }

    $this->handleDocumentsBatchService->run($documentsToDelete);
  }

  /**
   * The buildApplicationList function.
   *
   * This function builds a list of applications
   * based on the passed in parameters.
   *
   * @param mixed $uuid
   *   A users UUID.
   * @param mixed $businessId
   *   A business ID.
   * @param mixed $appEnv
   *   An app env.
   * @param mixed $type
   *   An applications' type.
   * @param mixed $status
   *   An applications' status.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $form
   *   The form.
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  private function buildApplicationList(
    mixed $uuid,
    mixed $businessId,
    mixed $appEnv,
    mixed $type,
    mixed $status,
    FormStateInterface $form_state,
    array &$form,
  ): void {
    try {
      $searchParams = $this->assembleSearchParams($uuid, $businessId, $appEnv, $type, $status);
      $documents = $this->atvService->searchDocuments($searchParams);

      if (empty($documents)) {
        $this->handleNoDocumentsFound($form, $searchParams);
        return;
      }

      // Extract the transaction_id values and join them into a string.
      $transactionIds = array_map(static fn ($document) => $document->getTransactionId(), $documents);

      $applicationNumberList = implode(',', $transactionIds);

      $form_state->setStorage(['documents' => $documents]);
      $documentsByType = $this->sortDocuments($documents);

      // Form elements by type.
      foreach ($documentsByType as $type => $applicationStatuses) {
        $form['appData'][$type] = [
          '#type' => 'details',
          '#title' => $this->t('Application: @type', ['@type' => $type]),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ];

        // Form elements by status.
        foreach ($applicationStatuses as $status => $documents) {
          $form['appData'][$type][$status] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Status: @status', ['@status' => $status]),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
          ];

          // Add transaction ID checkbox options.
          $form['appData'][$type][$status]['selectedDelete'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Select to delete'),
            '#options' => $this->buildSelectDeleteOptions($documents),
          ];
        }
      }

      // Add the application numbers list to a form element.
      $form['appData']['application_numbers'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Application numbers in copyable list.'),
        '#value' => $applicationNumberList,
        '#description' => $this->t('Total number of results: @count', ['@count' => count($transactionIds)]),
        '#disabled' => TRUE,
      ];
    }
    catch (AtvDocumentNotFoundException | AtvFailedToConnectException | GuzzleException $e) {
      $this->messenger()->addError('Failed fetching applications.');
      $this->messenger()->addError($e->getMessage());
    }
  }

  /**
   * The assembleSearchParams function.
   *
   * This function assembles an array of search parameters
   * based on the passed in values. If a value is not set,
   * then they key is omitted from the final search parameters
   * array.
   *
   * @param mixed $uuid
   *   A users UUID.
   * @param mixed $businessId
   *   A business ID.
   * @param mixed $appEnv
   *   An app env.
   * @param mixed $type
   *   An applications' type.
   * @param mixed $status
   *   An applications' status.
   *
   * @return array
   *   An associative array of search params, prefixed with a key.
   */
  private function assembleSearchParams(mixed $uuid, mixed $businessId, mixed $appEnv, mixed $type, mixed $status): array {
    return array_filter([
      'user_id' => $uuid ?: NULL,
      'business_id' => $businessId ?: NULL,
      'lookfor' => $appEnv ? "appenv:$appEnv" : NULL,
      'type' => ($type && $type !== 'all') ? $type : NULL,
      'status' => ($status && $status !== 'all') ? $status : NULL,
    ], function ($value) {
      return !is_null($value);
    });
  }

  /**
   * The handleNoDocumentsFound function.
   *
   * This documents displays an error message to the users
   * if no documents were found with the used search parameters.
   *
   * @param array $form
   *   The form.
   * @param array $searchParams
   *   The used search parameters.
   */
  private function handleNoDocumentsFound(array &$form, array $searchParams): void {
    $formattedParams = implode(', ', array_map(
      fn($key, $value) => "$key: $value",
      array_keys($searchParams),
      array_values($searchParams)
    ));
    $form['appData']['error'] = [
      '#markup' => "<p>No documents found with parameters: $formattedParams.<p>",
    ];
  }

  /**
   * The sortDocuments function.
   *
   * This function sorts ATV documents that are displayed
   * on the form. The documents are fist sorted by their
   * type, and then each status inside ecah type is sorted.
   *
   * @param array $documents
   *   An array of ATV documents.
   *
   * @return array
   *   An array of sorted ATV documents.
   */
  private function sortDocuments(array $documents): array {
    // Group and sort documents by type.
    $sortedByType = [];
    foreach ($documents as $document) {
      $sortedByType[$document->getType()][$document->getStatus()][] = $document;
    }
    ksort($sortedByType);

    // Sort statuses inside each type.
    foreach ($sortedByType as $documentType => $documentStatuses) {
      ksort($sortedByType[$documentType]);
    }
    return $sortedByType;
  }

  /**
   * The buildSelectDeleteOptions function.
   *
   * This function builds an array associative array
   * with application IDs and transactions IDs. These
   * values are used to construct checkboxes for the form.
   *
   * @param array $documents
   *   An array of ATV documents.
   *
   * @return array
   *   The constructed options.
   */
  private function buildSelectDeleteOptions(array $documents): array {
    $statusOptions = [];
    /** @var \Drupal\helfi_atv\AtvDocument $document */
    foreach ($documents as $document) {
      $statusOptions[$document->getId()] = $document->getTransactionId();
    }
    // Sort the transaction IDs.
    asort($statusOptions);
    return $statusOptions;
  }

  /**
   * The buildApplicationTypeOptions function.
   *
   * This function constructs the values for the application
   * type dropdown.
   *
   * @param array $thirdPartyOptions
   *   An array of third party settings.
   *
   * @return array
   *   An array of application type options.
   */
  private function buildApplicationTypeOptions(array $thirdPartyOptions): array {
    $applicationTypes = $thirdPartyOptions['application_types'];
    $applicationTypeOptions = [];
    foreach ($applicationTypes as $applicationId => $values) {
      if (isset($values['id'])) {
        $applicationTypeOptions[$values['id']] = sprintf('%s (%s)', $values['id'], $applicationId);
      }
    }
    ksort($applicationTypeOptions);
    return ['all' => $this->t('All')] + $applicationTypeOptions;
  }

  /**
   * The buildApplicationStatusOptions function.
   *
   * This function constructs the values for the application
   * status dropdown.
   *
   * @param array $thirdPartyOptions
   *   An array of third party settings.
   *
   * @return array
   *   An array of application status options.
   */
  private function buildApplicationStatusOptions(array $thirdPartyOptions): array {
    $applicationStatuses = $thirdPartyOptions['application_statuses'];
    ksort($applicationStatuses);
    return ['all' => $this->t('All')] + $applicationStatuses;
  }

}
