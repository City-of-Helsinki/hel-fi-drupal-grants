<?php

namespace Drupal\grants_handler\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_handler\EventException;
use Drupal\grants_handler\EventsService;
use Drupal\grants_handler\MessageService;
use Drupal\helfi_atv\AtvService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Grants Handler routes.
 */
class MessageController extends ControllerBase {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The grants_handler.events_service service.
   *
   * @var \Drupal\grants_handler\EventsService
   */
  protected EventsService $eventsService;

  /**
   * The grants_handler.message_service service.
   *
   * @var \Drupal\grants_handler\MessageService
   */
  protected MessageService $messageService;

  /**
   * The request service.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  protected RequestStack $request;

  /**
   * Debug on?
   *
   * @var bool
   */
  protected bool $debug;

  /**
   * Atv access.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * The controller constructor.
   *
   * @param \Drupal\grants_handler\EventsService $grants_handler_events_service
   *   The grants_handler.events_service service.
   * @param \Drupal\grants_handler\MessageService $grants_handler_message_service
   *   The grants_handler.message_service service.
   * @param \Drupal\Core\Http\RequestStack $requestStack
   *   Request stuff.
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   Access to ATV backend.
   */
  public function __construct(
    EventsService $grants_handler_events_service,
    MessageService $grants_handler_message_service,
    RequestStack $requestStack,
    AtvService $atvService
  ) {
    $this->eventsService = $grants_handler_events_service;
    $this->messageService = $grants_handler_message_service;
    $this->request = $requestStack;
    $this->atvService = $atvService;

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
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('grants_handler.events_service'),
      $container->get('grants_handler.message_service'),
      $container->get('request_stack'),
      $container->get('helfi_atv.atv_service')
    );
  }

  /**
   * Builds the response.
   */
  public function markMessageRead(string $submission_id, string $message_id): AjaxResponse {
    $tOpts = ['context' => 'grants_handler'];

    $isError = FALSE;
    $submission = ApplicationHandler::submissionObjectFromApplicationNumber($submission_id, NULL, FALSE);
    $submissionData = $submission->getData();
    $thisEvent = array_filter($submissionData['events'], function ($event) use ($message_id) {
      if (isset($event['eventTarget']) && $event['eventTarget'] == $message_id && $event['eventType'] == EventsService::$eventTypes['MESSAGE_READ']) {
        return TRUE;
      }
      return FALSE;
    });

    if (empty($thisEvent)) {
      try {
        $this->eventsService->logEvent(
          $submission_id,
          EventsService::$eventTypes['MESSAGE_READ'],
          $this->t('Message marked as read', [], $tOpts),
          $message_id
        );
        $message = $this->t('Message marked as read', [], $tOpts);
        $this->atvService->clearCache($submission_id);
      }
      catch (EventException $ee) {
        $this->getLogger('message_controller')->error('Error: %error', [
          '%error' => $ee->getMessage(),
        ]);
        $isError = TRUE;
        $message = $this->t('Message marking as read failed.', [], $tOpts);
      }
    }
    else {
      $message = $this->t('Message already read.', [], $tOpts);
    }

    $ajaxResponse = new AjaxResponse();

    $dataSelector = str_replace(
      '@message_id',
      $message_id,
      '[data-message-id="@message_id"]'
    );

    if (!$isError) {
      // New message container.
      $replaceMessageContainerCommand = new ReplaceCommand(
        $dataSelector . ' .webform-submission-messages__new-message',
        NULL
      );
      // Mark as read button.
      $replaceButtonCommand = new ReplaceCommand($dataSelector . ' .use-ajax', NULL);

      $ajaxResponse->addCommand($replaceMessageContainerCommand);
      $ajaxResponse->addCommand($replaceButtonCommand);
    }

    $render = [
      '#theme' => 'status_messages',
      '#message_list' => [],
      '#status_headings' => [
        'status' => t('Status message'),
        'error' => t('Error message'),
        'warning' => t('Warning message'),
      ],
    ];

    $messageType = $isError ? 'error' : 'status';
    $render['#message_list'][$messageType][] = $message;

    $renderedHtml = \Drupal::service('renderer')->render($render);
    $prependCommand = new PrependCommand($dataSelector, $renderedHtml);

    $ajaxResponse->addCommand($prependCommand);

    return $ajaxResponse;

  }

}
