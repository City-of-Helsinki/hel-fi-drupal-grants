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

    $uuid = $form_state->getValue('uuid');
    $appEnv = $form_state->getValue('appEnv');

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

    $form['profileData'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Documents in ATV / @appEnv: @id', ['@id' => $uuid, '@appEnv' => $appEnv]),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#prefix' => '<div id="profile-data">',
      '#suffix' => '</div>',
    ];

    if ($uuid) {
      $this->buildApplicationList($uuid, $appEnv, $form_state, $form);
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
    return $form['profileData'];
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

    /** @var \Drupal\helfi_atv\AtvDocument $docToDelete */
    foreach ($docsToDelete as $docToDelete) {
      $transId = $docToDelete->getTransactionId();
      try {
        $this->atvService->deleteDocument($docToDelete);
        $this->messenger()->addStatus("Document $transId deleted");
      }
      catch (AtvDocumentNotFoundException | AtvFailedToConnectException | TokenExpiredException | GuzzleException $e) {
        $this->messenger()->addError("Deleting document $transId failed." . $e->getMessage());
      }
    }

  }

  /**
   * Build Application list based on selections.
   *
   * @param mixed $uuid
   * @param mixed $appEnv
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param array $form
   */
  public function buildApplicationList(mixed $uuid, mixed $appEnv, FormStateInterface $form_state, array &$form): void {
    try {
      $searchParams = ['user_id' => $uuid];
      if ($appEnv) {
        $searchParams['lookfor'] = 'appenv:' . $appEnv;
      }
      $userDocuments = $this->atvService->searchDocuments($searchParams);

      $form_state->setStorage(['userdocs' => $userDocuments]);

      $form['profileData']['applications'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Applications by type'),
        // Added.
        '#collapsible' => TRUE,
        // Added.
        '#collapsed' => FALSE,
      ];

      $sortedByType = [];
      /** @var \Drupal\helfi_atv\AtvDocument $document */
      foreach ($userDocuments as $document) {
        if ($document->getStatus() === 'DRAFT') {
          $sortedByType[$document->getType()][] = $document;
        }
      }

      foreach ($sortedByType as $type => $applications) {
        $form['profileData']['applications'][$type] = [
          '#type' => 'fieldset',
          '#title' => $type,
          // Added.
          '#collapsible' => TRUE,
          // Added.
          '#collapsed' => FALSE,
        ];
        $typeOptions = [];
        if (!empty($applications)) {
          /** @var \Drupal\helfi_atv\AtvDocument $application */
          foreach ($applications as $application) { // NOSONAR
            $typeOptions[$application->getId()] = $application->getTransactionId();
          }
          $form['profileData']['applications'][$type]['selectedDelete'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Select to delete'),
            '#options' => $typeOptions,
          ];
        }
        $form['profileData']['applications']['selectAll'] = [
          '#type' => 'multiple_select',
          '#value' => $this->t('Delete selected'),
        ];
      }
    }
    catch (AtvDocumentNotFoundException|AtvFailedToConnectException|GuzzleException $e) {
    }
  }

}
