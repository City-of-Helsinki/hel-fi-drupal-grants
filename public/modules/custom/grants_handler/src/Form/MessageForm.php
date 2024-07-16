<?php

namespace Drupal\grants_handler\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Render\Renderer;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\grants_attachments\AttachmentHandlerHelper;
use Drupal\grants_attachments\AttachmentRemover;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_handler\MessageService;
use Drupal\helfi_atv\AtvService;
use Drupal\webform\Entity\WebformSubmission;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Provides a Grants Handler form.
 *
 * @phpstan-consistent-constructor
 */
class MessageForm extends FormBase {

  /**
   * Drupal\Core\TypedData\TypedDataManager definition.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected TypedDataManager $typedDataManager;

  /**
   * Communicate messages to integration.
   *
   * @var \Drupal\grants_handler\MessageService
   */
  protected MessageService $messageService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManagerInterface|EntityTypeManager $entityTypeManager;

  /**
   * Handle application tasks.
   *
   * @var \Drupal\grants_handler\ApplicationHandler
   */
  protected ApplicationHandler $applicationHandler;

  /**
   * Access ATV.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Remove attachment files.
   *
   * @var \Drupal\grants_attachments\AttachmentRemover
   */
  protected AttachmentRemover $attachmentRemover;

  /**
   * Print / log debug things.
   *
   * @var bool
   */
  protected bool $debug;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected Renderer $renderer;

  /**
   * Get session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected Session $session;

  /**
   * Constructs a new AddressForm object.
   *
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   Typed data access.
   * @param \Drupal\grants_handler\MessageService $messageService
   *   Send messages.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   Load entities.
   * @param \Drupal\grants_handler\ApplicationHandler $applicationHandler
   *   HAndle application things.
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   Access ATV.
   * @param \Drupal\grants_attachments\AttachmentRemover $attachmentRemover
   *   Remove attachments.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Access to ATV backend.
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   Session data.
   */
  public function __construct(
    TypedDataManager $typed_data_manager,
    MessageService $messageService,
    EntityTypeManager $entityTypeManager,
    ApplicationHandler $applicationHandler,
    AtvService $atvService,
    AttachmentRemover $attachmentRemover,
    Renderer $renderer,
    Session $session
  ) {
    $this->typedDataManager = $typed_data_manager;
    $this->messageService = $messageService;
    $this->entityTypeManager = $entityTypeManager;
    $this->applicationHandler = $applicationHandler;
    $this->atvService = $atvService;
    $this->attachmentRemover = $attachmentRemover;
    $this->renderer = $renderer;
    $this->session = $session;

    $debug = getenv('debug');

    if ($debug == 'true') {
      $this->debug = TRUE;
    }
    else {
      $this->debug = FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): MessageForm|static {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('grants_handler.message_service'),
      $container->get('entity_type.manager'),
      $container->get('grants_handler.application_handler'),
      $container->get('helfi_atv.atv_service'),
      $container->get('grants_attachments.attachment_remover'),
      $container->get('renderer'),
      $container->get('session')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grants_handler_message';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, WebformSubmission $webform_submission = NULL): array {
    $tOpts = ['context' => 'grants_handler'];

    $storage = $form_state->getStorage();
    $storage['webformSubmission'] = $webform_submission;

    $messageSent = $storage['message_sent'] ?? FALSE;

    $form['status_messages'] = [
      '#markdown' => '',
    ];

    if (!$messageSent) {
      $form['message'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Message', [], $tOpts),
        '#required' => TRUE,
      ];

      $sessionHash = Crypt::hashBase64($this->session->getId());
      $upload_location = 'private://grants_messages/' . $sessionHash;

      $maxFileSizeInBytes = (1024 * 1024) * 20;

      $form['messageAttachment'] = [
        '#type' => 'managed_file',
        '#title' => $this->t('Attachment', [], $tOpts),
        '#multiple' => FALSE,
        '#uri_scheme' => 'private',
        '#file_extensions' => 'doc,docx,gif,jpg,jpeg,pdf,png,ppt,pptx,rtf,txt,xls,xlsx,zip',
        '#upload_validators' => [
          'file_validate_extensions' => ['doc docx gif jpg jpeg pdf png ppt pptx rtf txt xls xlsx zip'],
          'file_validate_size' => [$maxFileSizeInBytes],
        ],
        '#description' => $this->t('Only one file.<br>Limit: 20 MB.<br>
Allowed file types: doc, docx, gif, jpg, jpeg, pdf, png, ppt, pptx,
rtf, txt, xls, xlsx, zip.', [], $tOpts),
        '#element_validate' => ['\Drupal\grants_handler\Form\MessageForm::validateUpload'],
        '#upload_location' => $upload_location,
        '#sanitize' => TRUE,
      ];
      $form['attachmentDescription'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Attachment description', [], $tOpts),
        '#required' => FALSE,
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Send', [], $tOpts),
        '#ajax' => [
          'callback' => '::ajaxSubmit',
          'wrapper' => 'grants-handler-message',
        ],
      ];

    }
    else {
      $form['new_message'] = [
        '#type' => 'submit',
        '#submit' => [
          [$this, 'newMessageHandler'],
        ],
        '#value' => $this->t('New message', [], $tOpts),
        '#ajax' => [
          'callback' => '::ajaxSubmit',
          'wrapper' => 'grants-handler-message',
        ],
      ];
    }

    $form_state->setStorage($storage);

    return $form;
  }

  /**
   * Ajax handler for new message.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   */
  public function newMessageHandler(array &$form, FormStateInterface $formState) {
    $formState->setRebuild();
    $storage = $formState->getStorage();
    $newStorage = [
      'webformSubmission' => $storage['webformSubmission'],
    ];

    $formState->setStorage($newStorage);
  }

  /**
   * Ajax submit callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   The Ajax response.
   *
   * @throws \Exception
   */
  public function ajaxSubmit(array &$form, FormStateInterface $formState): AjaxResponse {

    $storage = $formState->getStorage();
    $messageSent = $storage['message_sent'] ?? NULL;
    $ajaxResponse = new AjaxResponse();

    if ($messageSent) {

      // Minimal data required to display the message immediately.
      $messageBuild = [
        '#theme' => 'message_list_item',
        '#message' => [
          'body' => $messageSent['body'],
        ],
      ];

      // Check if attachment was uploaded with the message.
      $attachmentArray = $messageSent['attachments'] ?? [];
      $attachment = reset($attachmentArray);

      if ($attachment) {
        $messageBuild['#message']['attachments'] = [
          [
            'description' => $attachment->description,
            'fileName' => $attachment->fileName,
          ],
        ];
      }

      // Render the build array and add to the append command.
      $messageOutput = $this->renderer->render($messageBuild);
      $appendMessage = new AppendCommand('.webform-submission-messages__messages-list', $messageOutput);
      $ajaxResponse->addCommand($appendMessage);
    }

    // Handle possible errors during the AJAX request.
    $errorMessages = $this->messenger()
      ->messagesByType(MessengerInterface::TYPE_ERROR);
    $statusMessages = $this->messenger()
      ->messagesByType(MessengerInterface::TYPE_STATUS);

    $this->messenger()->deleteByType(MessengerInterface::TYPE_ERROR);
    $this->messenger()->deleteByType(MessengerInterface::TYPE_STATUS);
    $this->messenger()->deleteByType(MessengerInterface::TYPE_WARNING);

    $render = [
      '#theme' => 'status_messages',
      '#message_list' => [
        'status' => $statusMessages,
        'error' => $errorMessages,
      ],
      '#status_headings' => [
        'status' => $this->t('Status message'),
        'error' => $this->t('Error message'),
        'warning' => $this->t('Warning message'),
      ],
    ];

    $renderedHtml = $this->renderer->render($render);

    $form['status_messages'] = [
      '#markup' => $renderedHtml,
    ];

    $replaceCommand = new ReplaceCommand('[id^=grants-handler-message]', $form);
    $ajaxResponse->addCommand($replaceCommand);

    return $ajaxResponse;
  }

  /**
   * Validate & upload file attachment.
   *
   * This is done here because we want to show upload errors inline with the
   * form element. And only way to check upload is to actually do the upload,
   * ATV will error and we will respond accordingly.
   *
   * @param array $element
   *   Element tobe validated.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $form
   *   The form.
   */
  public static function validateUpload(
    array &$element,
    FormStateInterface $formState,
    array &$form
  ): void {

    $triggeringElement = $formState->getTriggeringElement();

    if (!str_contains($triggeringElement["#name"], 'messageAttachment_upload_button')) {
      return;
    }

    $storage = $formState->getStorage();
    $webformSubmission = $storage['webformSubmission'];
    $webformData = $webformSubmission->getData();
    $applicationNumber = $webformData['application_number'];

    /** @var \Drupal\helfi_atv\AtvService $atvService */
    $atvService = \Drupal::service('helfi_atv.atv_service');

    /** @var \Drupal\grants_handler\ApplicationGetterService $applicationGetterService */
    $applicationGetterService = \Drupal::service('grants_handler.application_getter_service');

    try {
      $applicationDocument = $applicationGetterService->getAtvDocument($applicationNumber);

      /** @var \Drupal\file\Entity\File $file */
      foreach ($element["#files"] as $file) {

        // Upload attachment to document.
        $attachmentResponse = $atvService->uploadAttachment(
          $applicationDocument->getId(),
          $file->getFilename(),
          $file
        );

        if ($attachmentResponse) {
          $storage['messageAttachment'] = [
            'file' => $file,
            'response' => $attachmentResponse,
          ];
        }
      }
    }
    catch (\Throwable $e) {
      // Set error to form.
      $formState->setError($element, 'File upload failed, error has been logged.');
      // Log error.
      \Drupal::logger('message_form')
        ->error('Message upload failed, error: @error',
          ['@error' => $e->getMessage()]
            );
    }

    $formState->setStorage($storage);
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild();
    $tOpts = ['context' => 'grants_handler'];

    $storage = $form_state->getStorage();
    if (!isset($storage['webformSubmission'])) {
      $this->messenger()
        ->addError($this->t('webformSubmission not found!', [], $tOpts));
      return;
    }

    /** @var \Drupal\webform\Entity\WebformSubmission $submission */
    $submission = $storage['webformSubmission'];
    $submissionData = $submission->getData();
    $nextMessageId = Uuid::uuid4()->toString();

    $attachment = $storage['messageAttachment'] ?? [];
    $data = [
      'body' => Xss::filter($form_state->getValue('message')),
      'messageId' => $nextMessageId,
    ];

    if (!empty($attachment)) {

      $response = $attachment['response'];
      $file = $attachment['file'];

      $integrationId = AttachmentHandlerHelper::getIntegrationIdFromFileHref($response['href']);
      $integrationId = AttachmentHandlerHelper::addEnvToIntegrationId($integrationId);

      $data['attachments'] = [
        (object) [
          'fileName' => $response['filename'],
          'description' => $form_state->getValue('attachmentDescription'),
          'integrationID' => $integrationId,
        ],
      ];

      // Remove file attachment directly after upload.
      $this->attachmentRemover->removeGrantAttachments(
        [$file->id()],
        [$file->id() => ['upload' => TRUE]],
        $submissionData['application_number'],
        getenv('DEBUG'),
        $submission->id()
      );
    }

    if ($this->messageService->sendMessage($data, $submission, $nextMessageId)) {
      $storage['message_sent'] = $data;
      $this->messenger()
        ->addStatus($this->t('Your message has been sent.', [], $tOpts));
    }
    else {
      $this->messenger()
        ->addStatus($this->t('Sending of your message failed.', [], $tOpts));
    }

    $form_state->setStorage($storage);
  }

}
