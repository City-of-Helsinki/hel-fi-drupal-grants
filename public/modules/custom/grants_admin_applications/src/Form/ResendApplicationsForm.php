<?php

namespace Drupal\grants_admin_applications\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_attachments\AttachmentHandlerHelper;
use Drupal\grants_handler\EventException;
use Drupal\grants_handler\Helpers;
use Drupal\helfi_atv\AtvDocument;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a grants_admin_resend_applications form.
 *
 * @phpstan-consistent-constructor
 */
class ResendApplicationsForm extends AtvFormBase {

  /**
   * Translation options.
   *
   * @var array
   */
  protected static $tOpts = [
    'context' => 'grants_admin_applications',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'grants_admin_applications_resend_applications';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $applicationId = trim($form_state->getValue('applicationId') ?? '');

    $prefilledNumber = $this->getRequest()->query->get('transaction_id');

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
    $messages = $form_state->getValue('messages');

    $storage = $form_state->getStorage();

    if ($document) {
      $documentArray = json_decode($document, TRUE);
      $prettyJson = json_encode($documentArray, JSON_PRETTY_PRINT);
      $document = $prettyJson;
    }

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

      $form['status']['messageList'] = [
        '#type' => 'table',
        '#caption' => $this->t('Messages'),
        '#header' => [
          $this->t('ID'),
          $this->t('Timestamp'),
          $this->t('Content'),
          $this->t('Attachments'),
          $this->t('Sent by', [], self::$tOpts),
          $this->t('Has been resent', [], self::$tOpts),
          $this->t('Resend this message', [], self::$tOpts),
        ],
      ];

      $form['status']['attachmentList'] = [
        '#type' => 'table',
        '#caption' => $this->t('Attachments'),
        '#header' => [
          $this->t('Field'),
          $this->t('Created'),
          $this->t('Filename'),
          $this->t('IntegrationID'),
          $this->t('Form OK', [], self::$tOpts),
          $this->t('Handler OK', [], self::$tOpts),
          $this->t('Avus2 OK', [], self::$tOpts),
        ],
      ];

      if ($messages) {
        $this->buildMessages($messages, $form);
      }

      if ($storage['atvDocument']) {
        $this->buildAttachments($storage['atvDocument'], $form);
      }

      $form['disclaimer'] = [
        '#prefix' => '<p>',
        '#suffix' => '<p>',
        '#markup' => $this->t(
          '* Please note that older applications might not have avus2 message received status available.',
          [], self::$tOpts),
      ];

      $form['resendMessages'] = [
        '#type' => 'submit',
        '#value' => $this->t('Resend messages', [], self::$tOpts),
        '#name' => 'resendMessages',
        '#submit' => ['::resendMessages'],
        '#ajax' => [
          'callback' => '::ajaxCallback',
          'disable-refocus' => FALSE,
          'wrapper' => 'profile-data',
          // This element is updated with this AJAX callback.
          'progress' => [
            'type' => 'throbber',
            'message' => $this->t('Resending messages...', [], self::$tOpts),
          ],
        ],
      ];

      // Attach css library.
      $form['#attached']['library'][] = 'grants_admin_applications/grants_admin_applications.resend_application';
    }
    return $form;
  }

  /**
   * Searches and returns ATV document with given id.
   *
   * @param string $applicationId
   *   The application id.
   *
   * @return \Drupal\helfi_atv\AtvDocument|false
   *   The document or false if not found.
   */
  private function getDocument(string $applicationId): AtvDocument|false {
    $sParams = [
      'transaction_id' => $applicationId,
      'lookfor' => 'appenv:' . Helpers::getAppEnv(),
    ];

    try {
      $res = $this->atvService->searchDocuments($sParams);
    }
    catch (GuzzleException | \Exception $e) {
      $this->logger(self::LOGGER_CHANNEL)
        ->error('Error: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
    return reset($res);
  }

  /**
   * Resend messages submit handler.
   *
   * @throws \Drupal\grants_handler\EventException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function resendMessages(array $form, FormStateInterface $formState): void {
    $values = $formState->getValues();

    if (isset($values['messageList']) && is_array($values['messageList'])) {
      $messagesToBeResent = array_filter($values['messageList'], function ($message) {
        return $message['resendMessage'];
      });

      $messagesToBeResent = array_map(function ($message) {
        return $message['resendMessage'];
      }, $messagesToBeResent);

      if (empty($messagesToBeResent)) {
        return;
      }

      $atvDoc = $this->getDocument($values['applicationId']);
      $documentContent = $atvDoc->getContent();
      $atvMessages = $documentContent['messages'];
      $filteredAtvMessages = array_filter($atvMessages, function ($message) use ($messagesToBeResent) {
        return in_array($message['messageId'], $messagesToBeResent);
      });

      $dt = new \DateTime();
      $dt->setTimezone(new \DateTimeZone('Europe/Helsinki'));

      foreach ($filteredAtvMessages as $message) {
        // Resend events - old ids.
        $this->eventsService->logEvent(
          $values['applicationId'],
          'MESSAGE_RESEND',
          $this->t('Message resent: @messageId.',
            [
              '@messageId' => $message['messageId'],
            ],
            ['context' => 'grants_handler']
          ),
          $message['messageId']
        );

        $message['messageId'] = Uuid::uuid4()->toString();
        // Need to add one second, otherwise messages parsing
        // will override the message due timestamp index.
        $dt->add(\DateInterval::createFromDateString('1 second'));
        $message['sendDateTime'] = $dt->format('Y-m-d\TH:i:s');
        $this->resendMessage($message, $values['applicationId']);
      }
    }

    $this->messenger()->addStatus($this->t(
      'Selected messages has been resent, processing the messages might take a few moments',
      [], self::$tOpts));
    $formState->setRebuild();
  }

  /**
   * Resend messages submit handler.
   *
   * @param array $messageData
   *   The message data.
   * @param string $applicationNumber
   *   The application number.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function resendMessage(array $messageData, string $applicationNumber): void {
    $endpoint = getenv('AVUSTUS2_MESSAGE_ENDPOINT');
    $username = getenv('AVUSTUS2_USERNAME');
    $password = getenv('AVUSTUS2_PASSWORD');
    $messageDataJson = Json::encode($messageData);

    $res = $this->httpClient->post($endpoint, [
      'auth' => [$username, $password, "Basic"],
      'body' => $messageDataJson,
    ]);

    if ($res->getStatusCode() == 200) {
      try {
        $event = $this->eventsService->logEvent(
          $applicationNumber,
          'MESSAGE_APP',
          $this->t('New message for @applicationNumber.',
            ['@applicationNumber' => $applicationNumber],
            ['context' => 'grants_handler']
          ),
          $messageData['messageId']
        );

        $this->logger(self::LOGGER_CHANNEL)->info(
          'MSG id: %nextId, message sent. Event logged: %eventId',
          [
            '%nextId' => $messageData['messageId'],
            '%eventId' => $event['eventID'],
          ]);
      }
      catch (EventException $e) {
        // Log event error.
        $this->logger(self::LOGGER_CHANNEL)
          ->error('%error', ['%error' => $e->getMessage()]);
      }
    }
  }

  /**
   * Get status submit handler.
   *
   * @param array $form
   *   The form object.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object.
   */
  public function getStatus(array $form, FormStateInterface $formState): void {
    try {
      $applicationId = $formState->getValue('applicationId');
      $placeholders = ['@applicationId' => $applicationId];
      $this->logger(self::LOGGER_CHANNEL)
        ->info('Status check init for: @applicationId', $placeholders);

      /** @var \Drupal\helfi_atv\AtvDocument $atvDoc */
      $atvDoc = $this->getDocument($applicationId);

      if (!$atvDoc) {
        $this->messenger()
          ->addWarning($this->t('No application found for id: @applicationId', $placeholders));
        $this->logger(self::LOGGER_CHANNEL)
          ->warning('No application found for id: @applicationId', $placeholders);
        return;
      }

      $messages = $this->messageService->parseMessages($atvDoc->getContent(), FALSE, TRUE);

      $this->messenger()
        ->addStatus($this->t('Application found: @applicationId', $placeholders));
      $statusArray = $atvDoc->getStatusArray();

      if (!empty($statusArray)) {
        $formState->setValue('status', $statusArray);
        $formState->setValue('atvdocument', $atvDoc->toJson());
        $formState->setValue('messages', $messages);

        $storage = $formState->getStorage();
        $storage['atvDocument'] = $atvDoc;
        $formState->setStorage($storage);

        $formState->setRebuild();
      }
    }
    catch (\Exception $e) {
      $uuid = Uuid::uuid4()->toString();
      $this->messenger()
        ->addError('Error has occured and has been logged. ID: @uuid', ['@uuid' => $uuid]);
      $this->logger(self::LOGGER_CHANNEL)->error(
        'Error: status check: @error, ID: @uuid',
        ['@error' => $e->getMessage(), '@uuid' => $uuid]
      );
    }
  }

  /**
   * Resend application submit handler.
   *
   * @param array $form
   *   The form object.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state object.
   */
  public function resendApplicationCallback(array $form, FormStateInterface $formState): void {
    $formState->setValue('status', NULL);

    try {
      $applicationId = trim($formState->getValue('applicationId'));
      $this->logApplicationResendInit($applicationId);

      $atvDoc = $this->getDocument($applicationId);

      if (!$atvDoc) {
        $this->handleApplicationNotFound($applicationId, $formState);
        return;
      }

      $this->attachmentFixerService->fixAttachmentsOnApplication($atvDoc);
      $this->sendApplicationToIntegrations($atvDoc, $applicationId);
      $formState->setRebuild();
    }
    catch (GuzzleException | \Exception $e) {
      $this->handleException($e);
    }
  }

  /**
   * Log application resend init.
   *
   * @param string $applicationId
   *   The application id.
   */
  private function logApplicationResendInit(string $applicationId): void {
    $placeholders = ['@applicationId' => $applicationId];
    $this->logger(self::LOGGER_CHANNEL)
      ->info('Application resend init for: @applicationId', $placeholders);
  }

  /**
   * Handle situation when application is not found.
   *
   * @param string $applicationId
   *   Application id.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   *
   * @return void
   *   Void.
   */
  private function handleApplicationNotFound(string $applicationId, FormStateInterface $formState): void {
    $this->messenger()
      ->addWarning($this->t('No application found for id: @applicationId',
        ['applicationId' => $applicationId]));
    $this->logger(self::LOGGER_CHANNEL)
      ->warning('No application found for id: @applicationId',
        ['applicationId' => $applicationId]);

    $formState->setRebuild();
  }

  /**
   * Handle exceptions.
   *
   * @param \Exception $e
   *   The exception.
   *
   * @return void
   *   Void.
   */
  private function handleException(\Exception $e): void {
    $uuid = Uuid::uuid4()->toString();
    $this->messenger()
      ->addError('Error has occurred and has been logged. ID: @uuid', ['@uuid' => $uuid]);
    $this->logger(self::LOGGER_CHANNEL)->error(
      'Error: Admin application forms - Resend error: @error, ID: @uuid',
      ['@error' => $e->getMessage(), '@uuid' => $uuid]
    );
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
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Must return AjaxResponse object or render array.
   *   Never return NULL or invalid render arrays. This
   *   could/will break your forms.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state, Request $request): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('.grants-admin-applications-resend-applications', $form));
    return $response;
  }

  /**
   * Build message list.
   *
   * @param mixed $messages
   *   Loaded messages.
   * @param array $form
   *   Form object.
   */
  public function buildMessages(mixed $messages, array &$form): void {
    foreach ($messages as $message) {
      $resent = isset($message['resent']) && $message['resent'];
      $senderIsAvus2 = isset($message['sentBy']) && $message['sentBy'] === 'Avustusten kasittelyjarjestelma';

      $rowElement = [
        'id' => [
          '#markup' => $message['messageId'],
        ],
        'timestamp' => [
          '#markup' => $message['sendDateTime'],
        ],
        'body' => [
          '#markup' => $message['body'],
        ],
        'attachments' => (function () use ($message) {
          if (isset($message['attachments'])) {
            $attachment = reset($message['attachments']);
            return [
              '#markup' => $attachment['fileName'] . ' - ' . $attachment['description'],
            ];
          }
          return [
            '#markup' => '-',
          ];
        })(),
        'sentBy' => [
          '#markup' => $message['sentBy'],
        ],
        'hasBeenResent' => [
          '#markup' => $resent
            ? $this->t('Yes', [], self::$tOpts)
            : $this->t('No', [], self::$tOpts),
        ],
        'resendMessage' => !$senderIsAvus2 ? [
          '#type' => 'checkbox',
          '#return_value' => $message['messageId'],
        ] : ['#markup' => ''],
        '#attributes' => [
          'class' => $senderIsAvus2 ? ['from-avus2'] : ['from-author'],
        ],
      ];

      $form['status']['messageList'][] = $rowElement;
    }
  }

  /**
   * Build attachment list.
   *
   * @param \Drupal\helfi_atv\AtvDocument $atvDocument
   *   The ATV document.
   * @param array $form
   *   The form object.
   */
  public function buildAttachments(AtvDocument $atvDocument, array &$form): void {
    $attachments = $atvDocument->getAttachments();
    $appEnv = $atvDocument->getMetadata()['appenv'];
    $content = $atvDocument->getContent();
    $events = $content['events'];
    $attachmentInfo = $content['attachmentsInfo']['attachmentsArray'];

    foreach ($attachments as $attachment) {
      $attOk = $this->attachmentFixerService->areAttachmentsOk($events, $attachment, $attachmentInfo, $appEnv);

      // Get field info.
      $fieldInfo = Helpers::findByFilename($attachment, $attachmentInfo);
      // Get label for form, use "description" or empty string.
      $fieldLabel = (string)Helpers::extractFieldValue($fieldInfo, 'description');

      $rowElement = [
        'field' => [
          '#markup' => $fieldLabel,
        ],
        'created' => [
          '#markup' => $attachment['created_at'],
        ],
        'filename' => [
          '#markup' => $attachment['filename'],
        ],
        'integrationId' => [
          '#markup' => '/' . $appEnv . AttachmentHandlerHelper::cleanIntegrationId($attachment['href']),
        ],
        'formOk' => [
          '#markup' => $attOk['form']
            ? $this->t('Yes', [], self::$tOpts)
            : $this->t('No', [], self::$tOpts),
        ],
        'handlerOk' => [
          '#markup' => $attOk['handler']
            ? $this->t('Yes', [], self::$tOpts)
            : $this->t('No', [], self::$tOpts),
        ],
        'avus2Ok' => [
          '#markup' => $attOk['avus2']
            ? $this->t('Yes', [], self::$tOpts)
            : $this->t('No', [], self::$tOpts),
        ],
      ];

      $form['status']['attachmentList'][] = $rowElement;
    }
  }

}
