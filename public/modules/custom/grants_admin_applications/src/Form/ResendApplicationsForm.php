<?php

namespace Drupal\grants_admin_applications\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\helfi_atv\AtvService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a grants_admin_resend_applications form.
 */
class ResendApplicationsForm extends AtvFormBase {

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
  public static function create(ContainerInterface $container): AdminApplicationsForm|static {
    return new static(
      $container->get('helfi_atv.atv_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grants_admin_applications_resend_applications';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $applicationId = trim($form_state->getValue('applicationId'));

    $prefilledNumber = \Drupal::request()->query->get('transaction_id');

    if (empty($applicationId) && $prefilledNumber) {
      $applicationId = $prefilledNumber;
    }

    $form['status_messages'] = [
      '#type' => 'status_messages',
    ];

    $form['applicationId'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Application ID'),
      '#required' => TRUE,
      '#default_value' => $applicationId,
    ];

    $form['resendApplicationCallback'] = [
      '#type' => 'submit',
      '#value' => $this->t('Resend application'),
      '#name' => 'getdata',
      '#submit' => ['::resendApplicationCallback'],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'disable-refocus' => FALSE,
        'wrapper' => 'profile-data',
        // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Fetching data...'),
        ],
      ],
    ];

    $form['getStatus'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get status'),
      '#name' => 'getStatus',
      '#submit' => ['::getStatus'],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'disable-refocus' => FALSE,
        'wrapper' => 'profile-data',
        // This element is updated with this AJAX callback.
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Fetching data...'),
        ],
      ],
    ];

    $status = $form_state->getValue('status');
    $document = $form_state->getValue('atvdocument');

    if ($status) {
      $form['status']['state'] = [
        '#title' => 'Status',
        '#type' => 'textfield',
        '#value' => $status['value'],
        '#disabled' => TRUE,
      ];

      $form['status']['state2'] = [
        '#title' => 'Timestamp',
        '#type' => 'textfield',
        '#value' => $status['timestamp'],
        '#disabled' => TRUE,
      ];
      $form['status']['state3'] = [
        '#title' => 'The JSON',
        '#type' => 'textarea',
        '#value' => $document,
        '#disabled' => TRUE,

      ];
    }
    return $form;
  }

  /**
   * Searches and returns ATV document with given id.
   */
  private static function getDocument($applicationId) {
    $sParams = [
      'transaction_id' => $applicationId,
      'lookfor' => 'appenv:' . ApplicationHandler::getAppEnv(),
    ];

    $res = \Drupal::service('helfi_atv.atv_service')->searchDocuments($sParams);
    return reset($res);
  }

  /**
   * GetStatus submit handler.
   */
  public static function getStatus(array $form, FormStateInterface $formState) {
    $messenger = \Drupal::service('messenger');
    $logger = self::getLoggerChannel();

    try {
      $applicationId = $formState->getValue('applicationId');
      $placeholders = ['@applicationId' => $applicationId];
      $logger->info('Status check init for: @applicationId', $placeholders);

      /** @var \Drupal\helfi_atv\AtvDocument $atvDoc */
      $atvDoc = self::getDocument($applicationId);

      if (!$atvDoc) {
        $messenger->addWarning(t('No application found for id: @applicationId', $placeholders));
        $logger->warning('No application found for id: @applicationId', $placeholders);
        return;
      }

      $messenger->addStatus(t('Application found: @applicationId', $placeholders));
      $statusArray = $atvDoc->getStatusArray();

      if (!empty($statusArray)) {
        $formState->setValue('status', $statusArray);
        $formState->setValue('atvdocument', $atvDoc->toJson());
        $formState->setRebuild();
      }
    }
    catch (\Exception $e) {
      $messenger->addError($e->getMessage());
      $logger->error(
        'Error: status check: @error',
        ['@error' => $e->getMessage()]
      );
    }
  }

  /**
   * Resend application callback submit handler.
   */
  public static function resendApplicationCallback(array $form, FormStateInterface $formState) {
    $logger = self::getLoggerChannel();
    $messenger = \Drupal::service('messenger');

    $formState->setValue('status', NULL);

    try {
      $applicationId = trim($formState->getValue('applicationId'));
      $placeholders = ['@applicationId' => $applicationId];
      $logger->info('Application resend init for: @applicationId', $placeholders);
      $atvDoc = self::getDocument($applicationId);

      if (!$atvDoc) {
        $messenger->addWarning(t('No application found for id: @applicationId', $placeholders));
        $logger->warning('No application found for id: @applicationId', $placeholders);

        $formState->setRebuild();
        return;
      }

      $messenger->addStatus(t('Application found: @applicationId', $placeholders));
      self::sendApplicationToIntegrations($atvDoc, $applicationId);
      $formState->setRebuild();
    }
    catch (\Exception $e) {
      $messenger->addError($e->getMessage());
      $logger->error(
        'Error: Admin application forms - Resend error: @error',
        ['@error' => $e->getMessage()]
      );
    }
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
  public function ajaxCallback(array &$form, FormStateInterface $form_state, Request $request): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('.grants-admin-applications-resend-applications', $form));
    return $response;
  }

}
