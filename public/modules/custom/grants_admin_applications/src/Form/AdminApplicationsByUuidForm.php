<?php

namespace Drupal\grants_admin_applications\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a grants_admin_applications form.
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
  public function getFormId() {
    return 'grants_admin_applications_admin_applications';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

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
      try {
        //$userDocuments = $this->atvService->getUserDocuments($uuid);
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
        /** @var AtvDocument $document */
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
            foreach ($applications as $application) {
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
      catch (AtvDocumentNotFoundException|AtvFailedToConnectException $e) {
      }
      catch (GuzzleException $e) {
      }
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
  public function validateForm(array &$form, FormStateInterface $form_state) {

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

    if (str_contains($triggeringElement['#id'], 'delete-all')) {
      $storage = $form_state->getStorage();
      $userDocuments = $storage['userdocs'];
    }
    elseif (str_contains($triggeringElement['#id'], 'delete-selected')) {
      // Get form values & profile data.
      $values = $form_state->getValues();
    }

    $d = 'asdf';
//
//    $deleteProfile = $values['deleteprofile'] === 1;
//    $deleteApplications = [];
//    $keepApplications = [];
//    foreach ($values["selectedDelete"] as $key => $value) {
//      if ($key === $value) {
//        $deleteApplications[] = $value;
//      }
//      else {
//        $keepApplications[] = $key;
//      }
//    }
//
//    $processed = [];
//
//    foreach ($deleteApplications as $appIdToDelete) {
//      /** @var \Drupal\helfi_atv\AtvDocument $item */
//      $delDocument = array_filter($profileData, function ($item) use ($appIdToDelete) {
//        if ($item->getId() === $appIdToDelete) {
//          return TRUE;
//        }
//        return FALSE;
//      });
//      $delDocument = reset($delDocument);
//      $documentId = $delDocument->getId();
//      $transactionId = $delDocument->getTransactionId();
//      try {
//        // Try to delete documents and show error in failure.
//        if ($this->atvService->deleteDocument($delDocument)) {
//          $processed['deleted'][$documentId] = $transactionId;
//          $this->messenger()
//            ->addStatus($this->t('Document deleted: @id, transaction id: @tr', [
//              '@id' => $documentId,
//              '@tr' => $transactionId,
//            ]));
//        }
//      }
//      catch (AtvDocumentNotFoundException | AtvFailedToConnectException | TokenExpiredException | GuzzleException $e) {
//        $response = $e->getResponse();
//        $body = $response->getBody();
//        $errorMessage = Json::decode($body->getContents());
//
//        $processed['failed'][$documentId] = $transactionId;
//
//        $this->messenger()
//          ->addError($this->t('Document deleting failed (@id,@tr): @message', [
//            '@id' => $documentId,
//            '@tr' => $transactionId,
//            '@message' => $errorMessage["errors"][0]["message"],
//          ]));
//      }
//    }
//
//    if (empty($keepApplications) && $deleteProfile) {
//
//      $grantsProfile = array_filter($profileData, function (AtvDocument $item) {
//        if ($item->getType() == 'grants_profile') {
//          return TRUE;
//        }
//        return FALSE;
//      });
//      $grantsProfile = reset($grantsProfile);
//      $documentId = $grantsProfile->getId();
//      $transactionId = $grantsProfile->getTransactionId();
//
//      try {
//        if ($this->atvService->deleteDocument($grantsProfile)) {
//          $this->messenger()
//            ->addStatus($this->t('Grants Profile Document deleted: @id, transaction id: @tr', [
//              '@id' => $documentId,
//              '@tr' => $transactionId,
//            ]));
//        }
//      }
//      catch (AtvDocumentNotFoundException | AtvFailedToConnectException | TokenExpiredException | GuzzleException $e) {
//        $response = $e->getResponse();
//        $body = $response->getBody();
//        $errorMessage = Json::decode($body->getContents());
//
//        $processed['failed'][$documentId] = $transactionId;
//
//        $this->messenger()
//          ->addError($this->t('Profile Document deleting failed (@id,@tr): @message', [
//            '@id' => $documentId,
//            '@tr' => $transactionId,
//            '@message' => $errorMessage["errors"][0]["message"],
//          ]));
//      }
//    }
//    elseif (!empty($keepApplications) && $deleteProfile) {
//      $this->messenger()
//        ->addStatus('Cannot delete grants profile while applications exist for business id');
//    }
//
//    if (!empty($processed['failed'])) {
//      $failedUuids = array_keys($processed['failed']);
//      $failedApplicationNumbers = array_values($processed['failed']);
//      $this->messenger()
//        ->addStatus(
//          $this->t('Following documents were not deleted, copy paste the
//          list for manual deletion: @failed. And to mark these deleted in Avus2: @numbers',
//            [
//              '@failed' => implode(', ', $failedUuids),
//              '@numbers' => implode(', ', $failedApplicationNumbers),
//            ])
//        );
//    }
//
//    if (!empty($processed['deleted'])) {
//      $failedApplicationNumbers = array_values($processed['failed']);
//      $this->messenger()
//        ->addStatus(
//          $this->t('Following documents were deleted, copy paste the
//          list for marking these deleted in Avus2: @numbers',
//            [
//              '@numbers' => implode(', ', $failedApplicationNumbers),
//            ])
//        );
//    }
  }

}
