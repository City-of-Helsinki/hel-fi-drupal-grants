<?php

namespace Drupal\grants_admin_applications\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\EventException;
use Drupal\grants_handler\Helpers;
use Drupal\grants_handler\MessageService;
use Drupal\helfi_atv\AtvService;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
   * Access to ATV.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Message service.
   *
   * @var \Drupal\grants_handler\MessageService
   */
  protected MessageService $messageService;

  /**
   * Constructs a new GrantsProfileForm object.
   */
  public function __construct(
    AtvService $atvService,
    MessageService $messageService
  ) {
    $this->atvService = $atvService;
    $this->messageService = $messageService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ResendApplicationsForm|static {
    return new static(
      $container->get('helfi_atv.atv_service'),
      $container->get('grants_handler.message_service')
    );
  }

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

      if ($messages) {
        $this->buildMessages($messages, $form);
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
   */
  private static function getDocument($applicationId) {
    $sParams = [
      'transaction_id' => $applicationId,
      'lookfor' => 'appenv:' . Helpers::getAppEnv(),
    ];

    $res = \Drupal::service('helfi_atv.atv_service')->searchDocuments($sParams);
    return reset($res);
  }

  /**
   * Resend messages submit handler.
   */
  public static function resendMessages(array $form, FormStateInterface $formState): void {
    $values = $formState->getValues();
    $eventService = \Drupal::service('grants_handler.events_service');
    $messenger = \Drupal::service('messenger');

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

      $atvDoc = self::getDocument($values['applicationId']);
      $documentContent = $atvDoc->getContent();
      $atvMessages = $documentContent['messages'];
      $filteredAtvMessages = array_filter($atvMessages, function ($message) use ($messagesToBeResent) {
        return in_array($message['messageId'], $messagesToBeResent);
      });

      $dt = new \DateTime();
      $dt->setTimezone(new \DateTimeZone('Europe/Helsinki'));

      foreach ($filteredAtvMessages as $message) {
        // Resend events - old ids.
        $eventService->logEvent(
          $values['applicationId'],
          'MESSAGE_RESEND',
          t('Message resent: @messageId.',
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
        self::resendMessage($message, $values['applicationId']);
      }
    }

    $messenger->addStatus(t(
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
   */
  private static function resendMessage(array $messageData, string $applicationNumber): void {

    $httpClient = \Drupal::service('http_client');
    $eventService = \Drupal::service('grants_handler.events_service');
    $logger = self::getLoggerChannel();

    $endpoint = getenv('AVUSTUS2_MESSAGE_ENDPOINT');
    $username = getenv('AVUSTUS2_USERNAME');
    $password = getenv('AVUSTUS2_PASSWORD');
    $messageDataJson = Json::encode($messageData);

    $res = $httpClient->post($endpoint, [
      'auth' => [$username, $password, "Basic"],
      'body' => $messageDataJson,
    ]);

    if ($res->getStatusCode() == 200) {
      try {
        $event = $eventService->logEvent(
          $applicationNumber,
          'MESSAGE_APP',
          t('New message for @applicationNumber.',
            ['@applicationNumber' => $applicationNumber],
            ['context' => 'grants_handler']
          ),
          $messageData['messageId']
        );

        $logger->info(
          'MSG id: %nextId, message sent. Event logged: %eventId',
          [
            '%nextId' => $messageData['messageId'],
            '%eventId' => $event['eventID'],
          ]);

      }
      catch (EventException $e) {
        // Log event error.
        $logger->error('%error', ['%error' => $e->getMessage()]);
      }
    }

  }

  /**
   * GetStatus submit handler.
   */
  public static function getStatus(array $form, FormStateInterface $formState): void {
    $messenger = \Drupal::service('messenger');
    $logger = self::getLoggerChannel();

    /** @var \Drupal\grants_handler\MessageService $messageService */
    $messageService = \Drupal::service('grants_handler.message_service');

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

      $messages = $messageService->parseMessages($atvDoc->getContent(), FALSE, TRUE);

      $messenger->addStatus(t('Application found: @applicationId', $placeholders));
      $statusArray = $atvDoc->getStatusArray();

      if (!empty($statusArray)) {
        $formState->setValue('status', $statusArray);
        $formState->setValue('atvdocument', $atvDoc->toJson());
        $formState->setValue('messages', $messages);
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
  public static function resendApplicationCallback(array $form, FormStateInterface $formState): void {
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

}
