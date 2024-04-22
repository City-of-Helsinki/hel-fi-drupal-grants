<?php

namespace Drupal\grants_admin_applications\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_admin_applications\Service\HandleDocumentsBatchService;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
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
   * Document batch processing service.
   *
   * @var \Drupal\grants_admin_applications\Service\HandleDocumentsBatchService
   */
  protected HandleDocumentsBatchService $handleDocumentsBatchService;

  /**
   * Constructs a new GrantsProfileForm object.
   */
  public function __construct(AtvService $atvService, HandleDocumentsBatchService $handleDocumentsBatchService) {
    $this->atvService = $atvService;
    $this->handleDocumentsBatchService = $handleDocumentsBatchService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AdminApplicationsByBusinessIdForm|static {
    return new static(
      $container->get('helfi_atv.atv_service'),
      $container->get('grants_admin_applications.handle_documents_batch_service'),
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
      return [];
    }

    // Get user inputs.
    $input = $form_state->getUserInput();
    $uuid = $input['uuid'] ?? null;
    $type = $input['type'] ?? null;
    $businessId = $input['businessId'] ?? null;
    $status = $input['status'] ?? null;
    $appEnv = $input['appEnv'] ?? null;

    // Get the third party options.
    $config = \Drupal::config('grants_metadata.settings');
    $thirdPartyOpts = $config->get('third_party_options');

    // Get and sort application types.
    $applicationTypes = $thirdPartyOpts['application_types'];
    $applicationTypeOptions = [];
    foreach ($applicationTypes as $applicationId => $values) {
      if (isset($values['code'])) {
        $applicationTypeOptions[$values['code']] = sprintf('%s (%s)', $values['code'], $applicationId);
      }
    }
    ksort($applicationTypeOptions);
    $applicationTypeOptions = ['all' => $this->t('All')] + $applicationTypeOptions;

    // Get and sort application statuses.
    $applicationStatuses = $thirdPartyOpts['application_statuses'];
    ksort($applicationStatuses);
    $applicationStatusOptions = ['all' => $this->t('All')] + $applicationStatuses;

    $form['uuid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('UUID'),
      '#required' => FALSE,
      '#default_value' => $uuid ?? '13cb60ae-269a-46da-9a43-da94b980c067',
    ];

    $form['appEnv'] = [
      '#type' => 'textfield',
      '#title' => $this->t('appEnv'),
      '#required' => FALSE,
      '#default_value' => $appEnv ?? 'TEST',
    ];

    $form['businessId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Business ID'),
      '#required' => FALSE,
      '#default_value' => $businessId ?? '7009192-1',
    ];

    $form['type'] = [
      '#title' => $this->t('Application type'),
      '#type' => 'select',
      '#options' => $applicationTypeOptions,
      '#default_value' => 'all',
    ];

    $form['status'] = [
      '#title' => $this->t('Application status'),
      '#type' => 'select',
      '#options' => $applicationStatusOptions,
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
      '#title' => $this->t('Documents in ATV / @appEnv: @id', ['@id' => $uuid, '@appEnv' => $appEnv]),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#prefix' => '<div id="profile-data">',
      '#suffix' => '</div>',
    ];

    if ($uuid || $businessId) {
      $this->buildApplicationList($uuid, $businessId, $appEnv, $type, $status, $form_state, $form);
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

    $allDocuments = $storage['documents'];
    $documentsToDelete = [];

    if (str_contains($triggeringElement['#id'], 'delete-all')) {
      $documentsToDelete = $allDocuments;
    }
    if (str_contains($triggeringElement['#id'], 'delete-selected')) {
      $selectOptions = $form_state->getValue('selectedDelete');

      // Filter and collect applications to delete (checked checkboxes).
      $selectedToDelete = array_keys(array_filter($selectOptions, function($value) {
        return $value;
      }));

      // Filter and collect applications to keep (unchecked checkboxes).
      $selectedToKeep = array_keys(array_filter($selectOptions, function($value) {
        return !$value;
      }));

      // Get the documents to delete based on the selections.
      $documentsToDelete = array_filter($allDocuments, function (AtvDocument $item) use ($selectedToDelete, $selectedToKeep) {
        if (!in_array($item->getId(), $selectedToDelete)) {
          return FALSE;
        }
        if ($item->getType() == 'grants_profile' && $selectedToKeep) {
          $profileDeletionError = "Skipped profile deletion: {$item->getId()}. Cannot delete profile while applications for it exist.";
          $this->messenger()->addError($profileDeletionError);
          return FALSE;
        }
        return TRUE;
      });
    }

    $this->handleDocumentsBatchService->run($documentsToDelete);
  }

  /**
   * Build Application list based on selections.
   *
   * @param mixed $uuid
   * @param mixed $businessId
   * @param mixed $appEnv
   * @param mixed $type
   * @param mixed $status
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $form
   */
  public function buildApplicationList(
    mixed $uuid,
    mixed $businessId,
    mixed $appEnv,
    mixed $type,
    mixed $status,
    FormStateInterface $form_state,
    array &$form): void {
    try {

      // Apply the search params and search for documents.
      $searchParams = array_filter([
        'user_id' => $uuid ?: null,
        'business_id' => $businessId ?: null,
        'lookfor' => $appEnv ? "appenv:$appEnv" : null,
        'type' => ($type && $type !== 'all') ? $type : null,
        'status'=> ($status && $status !== 'all') ? $status : null,
      ], function($value) {
        return !is_null($value);
      });
      $documents = $this->atvService->searchDocuments($searchParams);

      // If we can't find any documents, display an error.
      if (!$documents) {
        $formattedParams = implode(', ', array_map(
          function ($key, $value) { return "$key: $value"; },
          array_keys($searchParams),
          array_values($searchParams)
        ));
        $form['appData']['error'] = [
          '#markup' => "<p>No documents found with parameters: $formattedParams.<p>",
        ];
        return;
      }

      // Filter out any production documents and store the documents.
      $documents = array_filter($documents, function (AtvDocument $item) {
        $meta = $item->getMetadata();
        if ($meta['appenv'] === 'production') {
          return FALSE;
        }
        return TRUE;
      });
      $form_state->setStorage(['documents' => $documents]);

      // Group the documents by type.
      $sortedByType = [];
      /** @var \Drupal\helfi_atv\AtvDocument $document */
      foreach ($documents as $document) {
        $sortedByType[$document->getType()][$document->getStatus()][] = $document;
      }

      // Sort by type, and within each type, by status.
      ksort($sortedByType);
      foreach ($sortedByType as $documentType => $documentStatuses) {
        ksort($sortedByType[$documentType]);
      }

      // Form elements by type.
      foreach ($sortedByType as $type => $applicationsType) {
        $form['appData'][$type] = [
          '#type' => 'details',
          '#title' => $this->t('Application: ' . $type),
          '#collapsible' => TRUE,
          '#collapsed' => TRUE,
        ];

        // Form elements by status.
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
          // Sort the transaction IDs.
          asort($statusOptions);

          // Add transaction ID checkbox options.
          $form['appData'][$type][$status]['selectedDelete'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Select to delete'),
            '#options' => $statusOptions,
          ];
        }
      }
    }
    catch (AtvDocumentNotFoundException|AtvFailedToConnectException|GuzzleException $e) {
      $this->messenger()->addError('Failed fetching applications.');
      $this->messenger()->addError($e->getMessage());
    }
  }

}
