<?php

namespace Drupal\grants_handler;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_metadata\AtvSchema;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\webform\Entity\WebformSubmission;
use GuzzleHttp\Client;

/**
 * Handle message uploading and other things related.
 */
class MessageService {

  use StringTranslationTrait;
  use DebuggableTrait;

  /**
   * The helfi_helsinki_profiili.userdata service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory|\Drupal\Core\Logger\LoggerChannelInterface|\Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannelFactory|LoggerChannelInterface|LoggerChannel $logger;

  /**
   * Log events via integration.
   *
   * @var \Drupal\grants_handler\EventsService
   */
  protected EventsService $eventsService;

  /**
   * API endopoint.
   *
   * @var string
   */
  protected string $endpoint;

  /**
   * Api username.
   *
   * @var string
   */
  protected string $username;

  /**
   * Api password.
   *
   * @var string
   */
  protected string $password;


  /**
   * Atv access.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Application status service.
   *
   * @var \Drupal\grants_handler\ApplicationStatusService
   */
  protected ApplicationStatusService $applicationStatusService;

  /**
   * Constructs a MessageService object.
   *
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helfi_helsinki_profiili_userdata
   *   The helfi_helsinki_profiili.userdata service.
   * @param \GuzzleHttp\Client $http_client
   *   Client to post data.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   Log things.
   * @param \Drupal\grants_handler\EventsService $eventsService
   *   Log events to atv document.
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   Access to ATV.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user.
   * @param \Drupal\grants_handler\ApplicationStatusService $applicationStatusService
   *   Application status service.
   */
  public function __construct(
    HelsinkiProfiiliUserData $helfi_helsinki_profiili_userdata,
    Client $http_client,
    LoggerChannelFactoryInterface $loggerFactory,
    EventsService $eventsService,
    AtvService $atvService,
    AccountProxyInterface $currentUser,
    ApplicationStatusService $applicationStatusService,
  ) {
    $this->helfiHelsinkiProfiiliUserdata = $helfi_helsinki_profiili_userdata;
    $this->httpClient = $http_client;
    $this->logger = $loggerFactory->get('grants_handler_message_service');
    $this->eventsService = $eventsService;
    $this->atvService = $atvService;
    $this->currentUser = $currentUser;
    $this->applicationStatusService = $applicationStatusService;

    $this->endpoint = getenv('AVUSTUS2_MESSAGE_ENDPOINT');
    $this->username = getenv('AVUSTUS2_USERNAME');
    $this->password = getenv('AVUSTUS2_PASSWORD');

    $this->setDebug(NULL);
  }

  /**
   * Send message to backend.
   *
   * @param array $unSanitizedMessageData
   *   Message data to be sanitized & used.
   * @param \Drupal\webform\Entity\WebformSubmission $submission
   *   Submission entity.
   * @param string $nextMessageId
   *   Next message id for logging.
   *
   * @return bool
   *   Return message status.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function sendMessage(array $unSanitizedMessageData, WebformSubmission $submission, string $nextMessageId): bool {
    $tOpts = ['context' => 'grants_handler'];

    $submissionData = $submission->getData();
    $userData = $this->helfiHelsinkiProfiiliUserdata->getUserData();

    // Make sure data from user is sanitized.
    $messageData = AtvSchema::sanitizeInput($unSanitizedMessageData);

    if (isset($submissionData["application_number"]) && !empty($submissionData["application_number"])) {
      $messageData['caseId'] = $submissionData["application_number"];

      if ($userData === NULL) {
        $currentUser = $this->currentUser;
        $messageData['sentBy'] = $currentUser->getDisplayName();
      }
      else {
        $messageData['sentBy'] = $userData['name'];
      }

      $dt = new \DateTime();
      $dt->setTimezone(new \DateTimeZone('Europe/Helsinki'));
      $messageData['sendDateTime'] = $dt->format('Y-m-d\TH:i:s');

      $messageDataJson = Json::encode($messageData);

      $res = $this->httpClient->post($this->endpoint, [
        'auth' => [$this->username, $this->password, "Basic"],
        'body' => $messageDataJson,
      ]);

      if ($this->debug === TRUE) {
        $this->logger->debug('MSG id: %msgId, JSON: %json', [
          '%msgId' => $nextMessageId,
          '%json' => $messageDataJson,
        ]);
      }

      if ($res->getStatusCode() == 200) {
        try {
          $this->atvService->clearCache($messageData['caseId']);
          $event = $this->eventsService->logEvent(
            $submissionData["application_number"],
            'MESSAGE_APP',
            $this->t('New message for @applicationNumber.',
              ['@applicationNumber' => $submissionData["application_number"]],
              $tOpts
            ),
            $nextMessageId
          );

          $this->logger->info(
            'MSG id: %nextId, message sent. Event logged: %eventId',
            [
              '%nextId' => $nextMessageId,
              '%eventId' => $event['eventID'],
            ]);

        }
        catch (EventException $e) {
          // Log event error.
          $this->logger->error('%error', ['%error' => $e->getMessage()]);
        }

        return TRUE;
      }

    }
    return FALSE;
  }

  /**
   * Check if given submission is allowed to be messaged.
   *
   * @param \Drupal\webform\Entity\WebformSubmission|null $submission
   *   Submission in question.
   * @param string|null $status
   *   If no object is available, do text comparison.
   *
   * @return bool
   *   Is submission editable?
   */
  public function isSubmissionMessageable(?WebformSubmission $submission, ?string $status): bool {

    if (NULL === $submission) {
      $submissionStatus = $status;
    }
    else {
      $data = $submission->getData();
      $submissionStatus = $data['status'];
    }

    $applicationStatuses = $this->applicationStatusService->getApplicationStatuses();

    if (in_array($submissionStatus, [
      $applicationStatuses['SUBMITTED'],
      $applicationStatuses['RECEIVED'],
      $applicationStatuses['PREPARING'],
      $applicationStatuses['PENDING'],
      $applicationStatuses['PROCESSING'],
    ])) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Parse messages from data.
   *
   * @param array $data
   *   Data to parse.
   * @param bool $onlyUnread
   *   Only unread messages.
   * @param bool $showHiddenMessages
   *   Show hidden messages.
   *
   * @return array
   *   Parsed messages.
   */
  public function parseMessages(array $data, bool $onlyUnread = FALSE, bool $showHiddenMessages = FALSE): array {
    if (!isset($data['events'])) {
      return [];
    }

    $messageEvents = $this->filterMessageEvents($data['events']);
    $resentMessages = $this->filterResentMessages($data['events'], $showHiddenMessages);
    $avus2ReceivedIds = $this->filterAvus2ReceivedMessages($data['events']);
    $eventIds = array_column($messageEvents, 'eventTarget');

    $messages = [];
    $unread = [];

    foreach ($data['messages'] as $message) {
      $ts = strtotime($message["sendDateTime"]);

      if (in_array($message['messageId'], $resentMessages)) {
        continue;
      }

      $this->setResentAndAvus2ReceivedFlags($message, $resentMessages, $avus2ReceivedIds, $showHiddenMessages);

      $msgUnread = $this->setMessageStatusAndCheckIfUnread($message, $eventIds);

      if ($onlyUnread === TRUE && $msgUnread === TRUE) {
        $unread[$ts] = $message;
      }
      $messages[$ts] = $message;
    }
    return $onlyUnread === TRUE ? $unread : $messages;
  }

  /**
   * Filter message events.
   *
   * @param array $events
   *   Events to filter.
   *
   * @return array
   *   Filtered events.
   */
  private function filterMessageEvents(array $events): array {
    return array_filter($events, function ($event) {
      return $event['eventType'] == $this->eventsService->getEventTypes()['MESSAGE_READ'];
    });
  }

  /**
   * Filter resent messages.
   *
   * @param array $events
   *   Events to filter.
   * @param bool $showHiddenMessages
   *   Show hidden messages.
   *
   * @return array
   *   Filtered events.
   */
  private function filterResentMessages(array $events, bool $showHiddenMessages): array {
    $resentMessages = array_filter($events, function ($event) {
      return $event['eventType'] == $this->eventsService->getEventTypes()['MESSAGE_RESEND'];
    });

    return $showHiddenMessages ? $resentMessages : array_unique(array_map(function ($message) {
      return $message['eventTarget'];
    }, $resentMessages));
  }

  /**
   * Filter avus2 received messages.
   *
   * @param array $events
   *   Events to filter.
   *
   * @return array
   *   Filtered events.
   */
  private function filterAvus2ReceivedMessages(array $events): array {
    $avus2ReceivedMessages = array_filter($events, function ($event) {
      return $event['eventType'] == $this->eventsService->getEventTypes()['AVUSTUS2_MSG_OK'];
    });

    return array_unique(array_column($avus2ReceivedMessages, 'eventTarget'));
  }

  /**
   * Set resent and avus2 received flags.
   *
   * @param array $message
   *   Message to set flags.
   * @param array $resentMessages
   *   Resent messages.
   * @param array $avus2ReceivedIds
   *   Avus2 received messages.
   * @param bool $showHiddenMessages
   *   Show hidden messages.
   */
  private function setResentAndAvus2ReceivedFlags(
    array &$message,
    array $resentMessages,
    array $avus2ReceivedIds,
    bool $showHiddenMessages,
  ): void {
    if (in_array($message['messageId'], $resentMessages) && $showHiddenMessages) {
      $message['resent'] = TRUE;
    }

    if (in_array($message['messageId'], $avus2ReceivedIds)) {
      $message['avus2received'] = TRUE;
    }
  }

  /**
   * Set message status for unread messages.
   *
   * @param array $message
   *   Message to set status.
   * @param array $eventIds
   *   Event ids.
   *
   * @return bool
   *   Is message unread.
   */
  private function setMessageStatusAndCheckIfUnread(array &$message, array $eventIds): bool {
    if (in_array($message['messageId'], $eventIds)) {
      $message['messageStatus'] = 'READ';
      return FALSE;
    }
    else {
      $message['messageStatus'] = 'UNREAD';
      return TRUE;
    }
  }

}
