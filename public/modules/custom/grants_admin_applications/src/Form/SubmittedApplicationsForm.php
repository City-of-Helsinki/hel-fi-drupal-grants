<?php

namespace Drupal\grants_admin_applications\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a grants_admin_applications form.
 */
class SubmittedApplicationsForm extends AtvFormBase {

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
  public static function create(ContainerInterface $container): SubmittedApplicationsForm|static {
    return new static(
      $container->get('helfi_atv.atv_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grants_admin_applications_admin_applications_status';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['status_messages'] = [
      '#type' => 'status_messages',
    ];

    $config = \Drupal::config('grants_metadata.settings');
    $thirdPartyOpts = $config->get('third_party_options');

    $form['filters']['status'] = [
      '#title' => $this->t('Application status'),
      '#type' => 'select',
      '#options' => $thirdPartyOpts['application_statuses'],
      '#default_value' => 'SUBMITTED',
    ];

    $form['filters']['created_at'] = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => [
          'container-inline',
        ],
      ],
    ];

    $form['filters']['created_at']['created_after'] = [
      '#title' => $this->t('Created after'),
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
    ];

    $form['filters']['created_at']['created_before'] = [
      '#title' => $this->t('Created before'),
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
    ];

    $form['filters']['created_at']['updated_after'] = [
      '#title' => $this->t('Updated after'),
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
    ];

    $form['filters']['created_at']['updated_before'] = [
      '#title' => $this->t('Updated before'),
      '#type' => 'datetime',
      '#date_date_element' => 'date',
      '#date_time_element' => 'none',
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

    $documents = $form_state->get('documents');

    if ($documents) {
      $form['documents'] = [
        '#type' => 'table',
        '#caption' => $this->t('Applications'),
        '#header' => [
          $this->t('Application number'),
          $this->t('Status'),
          $this->t('Status history'),
          $this->t('Timestamps'),
          $this->t('Resend'),
          $this->t('Status page'),
        ],
      ];

      foreach ($documents as $document) {

        $url = Url::fromRoute(
          'grants_admin_applications.resend_applications',
          ['transaction_id' => $document['transaction_id']],
          ['attributes' => ['target' => '_blank']]
        );

        $tableRow = [
          'transaction_id' => [
            '#markup' => $document['transaction_id'],
            '#wrapper_attributes' => [
              'colspan' => 1,
            ],
          ],
          'status' => [
            '#markup' => $document['status'],
          ],
          'status_history' => [
            '#type' => 'textarea',
            '#value' => $this->printStatusHistory($document['status_history']),
          ],
          'timestamps' => [
            '#markup' => "<strong>Created at</strong> {$document['created_at']} <br/>" .
            "<strong>Updated at</strong> {$document['updated_at']}",
          ],
          'resend' => [
            '#type' => 'submit',
            '#value' => 'Resend to integration',
            '#name' => 'resend_' . $document['transaction_id'],
            '#id' => $document['transaction_id'],
            '#submit' => ['::resendApplicationCallback'],
            '#ajax' => [
              'callback' => '::ajaxCallback',
              'disable-refocus' => FALSE,
                // This element is updated with this AJAX callback.
              'progress' => [
                'type' => 'throbber',
                'message' => $this->t('Fetching data...'),
              ],
            ],
          ],
          'status_link' => [
            '#type' => 'link',
            '#title' => 'Status page',
            '#url' => $url,
          ],
        ];

        $form['documents'][] = $tableRow;
      }

    }

    return $form;
  }

  /**
   * Resend application callback submit handler.
   */
  public static function resendApplicationCallback(array $form, FormStateInterface $formState) {
    $logger = self::getLoggerChannel();
    $messenger = \Drupal::service('messenger');
    $triggeringElement = $formState->getTriggeringElement();
    try {

      $transactionId = $triggeringElement['#id'];

      $sParams = [
        'transaction_id' => $transactionId,
        'lookfor' => 'appenv:' . ApplicationHandler::getAppEnv(),
      ];

      $res = \Drupal::service('helfi_atv.atv_service')->searchDocuments($sParams);
      $atvDoc = reset($res);

      if (!$atvDoc) {
        return;
      }

      self::sendApplicationToIntegrations($atvDoc, $transactionId);
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
   * Format status_history to readable form.
   *
   * @param array $history
   *   Status history array.
   *
   * @return string
   *   The formatted string.
   */
  public function printStatusHistory(array $history):string {
    $retVal = '';

    foreach ($history as $record) {

      $value = $record['value'] ?? '';
      $timeStamp = $record['timestamp'] ?? '';

      if (!empty($timeStamp)) {
        $timeStamp = strtotime($timeStamp);
        $timeStamp = date('d-m-Y H:i:s', $timeStamp);
      }

      $retVal .= $value . ' ' . $timeStamp . PHP_EOL;
    }

    return $retVal;
  }

  /**
   * GetStatus submit handler.
   */
  public static function getStatus(array $form, FormStateInterface $formState) {
    $messenger = \Drupal::service('messenger');
    $logger = self::getLoggerChannel();

    $options = [];

    $values = $formState->getValues();

    if (!empty($values['status'])) {
      $options['status'] = $values['status'];
    }

    $dateTimeValues = [
      'created_after',
      'created_before',
      'updated_after',
      'updated_before',
    ];

    foreach ($dateTimeValues as $dateTimeValue) {
      if (!isset($values[$dateTimeValue])) {
        continue;
      }

      $timestamp = $values[$dateTimeValue]->format('Y-m-d');
      $options[$dateTimeValue] = $timestamp;

    }

    try {
      /** @var Drupal\helfi_atv\AtvDocument[] $docs */
      $docs = self::getDocuments($options);

      // Filter out grants profiles from documents.
      $documents = array_filter(
        array_map(function (AtvDocument $doc) {
          return [
            'type' => $doc->getType(),
            'status' => $doc->getStatus(),
            'status_history' => $doc->getStatusHistory(),
            'transaction_id' => $doc->getTransactionId(),
            'updated_at' => $doc->getUpdatedAt(),
            'created_at' => $doc->getCreatedAt(),
          ];
        }, $docs),
        function (array $doc) {
          return $doc['type'] !== 'grants_profile';
        }
      );

      if (empty($documents)) {
        $messenger->addWarning(t('No documents found.'));
      }

      $formState->set('documents', $documents);
      $formState->setRebuild();
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
    $response->addCommand(new ReplaceCommand('.grants-admin-applications-admin-applications-status', $form));
    return $response;
  }

  /**
   * Searches and returns ATV document with given id.
   */
  private static function getDocuments($options = []) {

    $defaultOptions = [
      'status' => 'SUBMITTED',
    ];

    $activeOptions = array_merge($defaultOptions, $options);

    $sParams = [
      ...['lookfor' => 'appenv:' . ApplicationHandler::getAppEnv()],
      ...$activeOptions,
    ];

    return \Drupal::service('helfi_atv.atv_service')->searchDocuments($sParams);
  }

}
