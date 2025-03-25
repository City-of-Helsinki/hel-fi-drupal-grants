<?php

namespace Drupal\grants_handler\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\Renderer;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_attachments\DebuggableTrait;
use Drupal\grants_events\EventsService;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\EventException;
use Drupal\grants_handler\MessageService;
use Drupal\helfi_atv\AtvService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Grants Handler routes.
 *
 * @phpstan-consistent-constructor
 */
class MessageController extends ControllerBase {

  use MessengerTrait;
  use StringTranslationTrait;
  use DebuggableTrait;

  /**
   * The controller constructor.
   *
   * @param \Drupal\grants_events\EventsService $eventsService
   *   Use submission events productively.
   * @param \Drupal\grants_handler\MessageService $messageService
   *   Use messages productively.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Drupal requests.
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   Access to ATV backend.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   Renderer.
   * @param \Drupal\grants_handler\ApplicationGetterService $applicationGetterService
   *   Access to ATV backend.
   */
  public function __construct(
    protected EventsService $eventsService,
    protected MessageService $messageService,
    protected RequestStack $request,
    protected AtvService $atvService,
    protected Renderer $renderer,
    protected ApplicationGetterService $applicationGetterService,
  ) {
    $this->setDebug(NULL);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): MessageController|static {
    return new static(
      $container->get('grants_events.events_service'),
      $container->get('grants_handler.message_service'),
      $container->get('request_stack'),
      $container->get('helfi_atv.atv_service'),
      $container->get('renderer'),
      $container->get('grants_handler.application_getter_service')
    );
  }

  /**
   * Mark message as read.
   *
   * @param string $submission_id
   *   The submission id.
   * @param string $message_id
   *   The message id.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|bool
   *   The response.
   *
   * @throws \Exception
   */
  public function markMessageRead(string $submission_id, string $message_id): AjaxResponse|bool {
    $tOpts = ['context' => 'grants_handler'];

    $isError = FALSE;
    try {
      $submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($submission_id, NULL, FALSE);
    }
    catch (\Exception | GuzzleException $e) {
      $submission = NULL;
      $this->getLogger('message_controller')->error('Error: %error', [
        '%error' => $e->getMessage(),
      ]);
    }

    if (!$submission) {
      return FALSE;
    }

    $submissionData = $submission->getData();
    $thisEvent = array_filter($submissionData['events'], function ($event) use ($message_id) {
      if (
        isset($event['eventTarget']) &&
        $event['eventTarget'] == $message_id &&
        $event['eventType'] == $this->eventsService->getEventTypes()['MESSAGE_READ']
      ) {
        return TRUE;
      }
      return FALSE;
    });

    if (empty($thisEvent)) {
      try {
        $this->eventsService->logEvent(
          $submission_id,
          $this->eventsService->getEventTypes()['MESSAGE_READ'],
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
        'status' => $this->t('Status message'),
        'error' => $this->t('Error message'),
        'warning' => $this->t('Warning message'),
      ],
    ];

    $messageType = $isError ? 'error' : 'status';
    $render['#message_list'][$messageType][] = $message;

    $renderedHtml = $this->renderer->render($render);
    $prependCommand = new PrependCommand($dataSelector, $renderedHtml);

    $ajaxResponse->addCommand($prependCommand);

    return $ajaxResponse;
  }

}
