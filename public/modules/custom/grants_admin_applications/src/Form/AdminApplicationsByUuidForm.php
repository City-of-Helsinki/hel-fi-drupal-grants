<?php

namespace Drupal\grants_admin_applications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a grants_admin_applications form.
 *
 */
class AdminApplicationsByUuidForm extends FormBase {

  /**
   * Access to ATV.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Constructs a new GrantsProfileForm object.
   */
  public function __construct(AtvService $atvService) {
    $this->atvService = $atvService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AdminApplicationsByBusinessIdForm|static {
    return new static(
      $container->get('helfi_atv.atv_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'grants_admin_applications_admin_applications';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    if (str_contains(strtolower(ApplicationHandler::getAppEnv()), 'prod')) {
      $this->messenger()->addError('No deleting profiles in PROD environment');
    }

    $input = $form_state->getUserInput();

    $uuid = $input['uuid'] ?? null;
    $status = $input['status'] ?? null;
    $appEnv = $input['appEnv'] ?? null;

    $form['uuid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UUID'),
      '#required' => TRUE,
      '#default_value' => $uuid ?? '13cb60ae-269a-46da-9a43-da94b980c067',
    ];

    $form['appEnv'] = [
      '#type' => 'textfield',
      '#title' => $this->t('appEnv'),
      '#required' => TRUE,
      '#default_value' => $appEnv ?? 'TEST',
    ];

    $form['status'] = array(
      '#type' => 'radios',
      '#title' => t('Application status'),
      '#options' => [
        'all' => 'All',
        'DRAFT' => 'Draft',
        'RECEIVED' => 'Received',
        'SUBMITTED' => 'Submitted',
      ],
      '#default_value' => 'all',
    );

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
      '#title' => $this->t('Documents in ATV / @appEnv: @id', ['@id' => $uuid, '@appEnv' => $appEnv]),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#prefix' => '<div id="profile-data">',
      '#suffix' => '</div>',
    ];

    if ($uuid && $status) {
      $this->buildApplicationList($uuid, $appEnv, $status, $form_state, $form);
    }

    $form['actions']['delete_selected'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete selected'),
    ];

    $form['actions']['delete_all'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete all above'),
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
    $userDocuments = $storage['userdocs'];
    $docsToDelete = [];

    if (str_contains($triggeringElement['#id'], 'delete-all')) {
      $docsToDelete = $userDocuments;
    }
    elseif (str_contains($triggeringElement['#id'], 'delete-selected')) {
      // Get form values & profile data.
      $values = $form_state->getValue('selectedDelete');
      $docsToDelete = array_filter($userDocuments, function ($item) use ($values) {
        return in_array($item->getId(), $values);
      });
    }

    $chunk_size = 10;
    $chunks = array_chunk($docsToDelete, $chunk_size);
    $num_chunks = count($chunks);

    $operations = [];
    for ($batch_id = 0; $batch_id < $num_chunks; $batch_id++) {
      $operations[] = [
        '\Drupal\grants_admin_applications\Form\AdminApplicationsByUuidForm::batchDeleteDocuments',
        [ $batch_id + 1, $chunks[$batch_id] ],
      ];
    }

    $batch = [
      'title' => $this->t('Deleting documents'),
      'init_message' => $this->t('Starting to process documents.'),
      'progress_message' => $this->t('Completed @current out of @total batches.'),
      'finished' => '\Drupal\grants_admin_applications\Form\AdminApplicationsByUuidForm::finishBatchDeleteDocuments',
      'error_message' => $this->t('Document processing has encountered an error.'),
      'operations' => $operations,
    ];

    batch_set($batch);
  }

  /**
   * The batchDeleteDocuments function.
   *
   * This function deletes ATV documents in a batch.
   *
   * @param int $batch_id
   *   The batch ID.
   * @param array $docsToDelete
   *   The documents to delete this batch.
   * @param array $context
   *   The batch context.
   */
  public static function batchDeleteDocuments(int $batch_id, array $docsToDelete, array &$context): void {
    if (!isset($context['results']['updated'])) {
      $context['results']['process'] = 'Delete documents';
      $context['results']['updated'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['progress'] = 0;
      $context['results']['deleted_documents'] = [];
    }

    $context['results']['progress'] += count($docsToDelete);

    $context['message'] = t('Processing batch #@batch_id with a batch size of @batch_size.', [
      '@batch_id' => number_format($batch_id),
      '@batch_size' => number_format(count($docsToDelete)),
    ]);

    /** @var \Drupal\helfi_atv\AtvDocument $docToDelete */
    foreach ($docsToDelete as $docToDelete) {
      $transId = $docToDelete->getTransactionId();

      try {
        $atvService = \Drupal::service('helfi_atv.atv_service');
        $atvService->deleteDocument($docToDelete);

        $context['results']['deleted_documents'][] = $transId;
        $context['results']['updated']++;
      }
      catch (AtvDocumentNotFoundException | AtvFailedToConnectException | TokenExpiredException | GuzzleException $e) {
        $context['results']['failed']++;
        continue;
      }
    }
  }

  /**
   * The finishBatchDeleteDocuments function.
   *
   * This functions logs messages after batchDeleteDocuments
   * has finished execution.
   *
   * @param bool $success
   *   TRUE if all batch API tasks were completed successfully.
   * @param array $results
   *   An array of processed documents.
   * @param array $operations
   *   A list of the operations that had not been completed.
   * @param string $elapsed
   *   The elapsed processing time in seconds.
   */
  public static function finishBatchDeleteDocuments(bool $success, array $results, array $operations, string $elapsed): void {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(
        t('Processed @count documents. Deleted documents: @updated. Failed deletions: @failed. Elapsed time: @elapsed.', [
        '@count' => $results['progress'],
        '@updated' => $results['updated'],
        '@failed' => $results['failed'],
        '@elapsed' => $elapsed,
      ]));

      $messenger->addMessage(t('The deleted documents: @documents.', [
        '@documents' => implode(', ', $results['deleted_documents']),
      ]));

      \Drupal::logger('grants_admin_applications')->info(
        'Processed @count documents. Deleted documents: @updated. Failed deletions: @failed. Elapsed time: @elapsed.', [
        '@count' => $results['progress'],
        '@updated' => $results['updated'],
        '@failed' => $results['failed'],
        '@elapsed' => $elapsed,
      ]);

      \Drupal::logger('grants_admin_applications')->info(
        'The deleted documents: @documents.', [
        '@documents' => implode(', ', $results['deleted_documents']),
      ]);
    }
    else {
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      $messenger->addError($message);
    }
  }

  /**
   * Build Application list based on selections.
   *
   * @param mixed $uuid
   * @param mixed $appEnv
   * @param mixed $status
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $form
   */
  public function buildApplicationList(mixed $uuid, mixed $appEnv, mixed $status, FormStateInterface $form_state, array &$form): void {
    try {
      $searchParams = ['user_id' => $uuid];
      if ($appEnv) {
        $searchParams['lookfor'] = 'appenv:' . $appEnv;
      }
      if ($status && $status !== 'all') {
        $searchParams['status'] = $status;
      }
      $userDocuments = $this->atvService->searchDocuments($searchParams);

      $sortedByType = [];
      /** @var \Drupal\helfi_atv\AtvDocument $document */
      foreach ($userDocuments as $document) {
        $sortedByType[$document->getType()][$document->getStatus()][] = $document;
      }

      $form_state->setStorage(['userdocs' => $userDocuments]);

      foreach ($sortedByType as $type => $applicationsType) {
        $form['appData'][$type] = [
          '#type' => 'details',
          '#title' => $this->t('Application: ' . $type),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ];

        foreach ($applicationsType as $status => $applications) {
          $form['appData'][$type][$status] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Status: ' . $status),
            '#collapsible' => TRUE,
            '#collapsed' => FALSE,
          ];

          if (empty($applications)) {
            continue;
          }

          $statusOptions = [];
          /** @var \Drupal\helfi_atv\AtvDocument $application */
          foreach ($applications as $application) {
            $statusOptions[$application->getId()] = $application->getTransactionId();
          }
          $form['appData'][$type][$status]['selectedDelete'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Select to delete'),
            '#options' => $statusOptions,
          ];
        }
      }
    }
    catch (AtvDocumentNotFoundException|AtvFailedToConnectException|GuzzleException $e) {

    }
  }

}
