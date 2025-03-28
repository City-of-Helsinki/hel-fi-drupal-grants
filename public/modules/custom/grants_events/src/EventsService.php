<?php

namespace Drupal\grants_events;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\grants_attachments\DebuggableTrait;
use Drupal\grants_metadata\AtvSchema;
use Drupal\helfi_atv\AtvDocument;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;

/**
 * Send event updates to documents via integration.
 */
class EventsService {

  use DebuggableTrait;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|\Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelInterface|LoggerChannelFactoryInterface $logger;

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
   * Event types that are supported.
   *
   * @var array|string[]
   */
  public array $eventTypes = [
    'AVUSTUS2_MSG_OK' => 'AVUSTUS2_MSG_OK',
    'AVUSTUS2_ATT_OK' => 'AVUSTUS2_ATT_OK',
    'STATUS_UPDATE' => 'STATUS_UPDATE',
    'MESSAGE_AVUS2' => 'MESSAGE_AVUS2',
    'MESSAGE_APP' => 'MESSAGE_APP',
    'MESSAGE_READ' => 'MESSAGE_READ',
    'MESSAGE_RESEND' => 'MESSAGE_RESEND',
    'HANDLER_ATT_OK' => 'HANDLER_ATT_OK',
    'HANDLER_SEND_INTEGRATION' => 'HANDLER_SEND_INTEGRATION',
    'HANDLER_ATT_DELETE' => 'HANDLER_ATT_DELETE',
    'HANDLER_RESEND_APP' => 'HANDLER_RESEND_APP',
    'HANDLER_APP_COPIED' => 'HANDLER_APP_COPIED',
    'INTEGRATION_INFO_ATT_OK' => 'INTEGRATION_INFO_ATT_OK',
    'INTEGRATION_INFO_APP_OK' => 'INTEGRATION_INFO_APP_OK',
    'EVENT_INFO' => 'EVENT_INFO',
    'HANDLER_ATT_DELETED' => 'HANDLER_ATT_DELETED',
    'INTEGRATION_ERROR_AVUS2' => 'INTEGRATION_ERROR_AVUS2',
    'INTEGRATION_ERROR_ATV_ATT' => 'INTEGRATION_ERROR_ATV_ATT',
  ];

  /**
   * Constructs a MessageService object.
   *
   * @param \GuzzleHttp\Client $http_client
   *   Client to post data.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Log things.
   */
  public function __construct(
    Client $http_client,
    LoggerChannelFactoryInterface $loggerFactory,
  ) {
    $this->httpClient = $http_client;
    $this->logger = $loggerFactory->get('grants_handler_events_service');

    $this->endpoint = getenv('AVUSTUS2_EVENT_ENDPOINT');
    $this->username = getenv('AVUSTUS2_USERNAME');
    $this->password = getenv('AVUSTUS2_PASSWORD');

    $this->setDebug(NULL);

  }

  /**
   * Get event types.
   *
   * @return array|string[]
   *   Event types.
   */
  public function getEventTypes(): array {
    return $this->eventTypes;
  }

  /**
   * Log event to document via event integration.
   *
   * @param string $applicationNumber
   *   Application to be logged.
   * @param string $eventType
   *   Type of event, must be configured in this class as active one.
   * @param string $eventDescription
   *   Free message to be added.
   * @param string $eventTarget
   *   Target ID for event.
   * @param array $eventData
   *   If we have already built-up event data, use this.
   *
   * @return array|null
   *   EventID if success, otherways NULL
   *
   * @throws \Drupal\grants_events\EventException
   */
  public function logEvent(
    string $applicationNumber,
    string $eventType,
    string $eventDescription,
    string $eventTarget,
    array $eventData = [],
  ): ?array {

    if (empty($eventData)) {
      $eventData = $this->getEventData($eventType, $applicationNumber, $eventDescription, $eventTarget);
    }

    $eventDataJson = Json::encode($eventData);

    if (TRUE === $this->debug) {
      $this->logger->debug(
        'Event ID: %eventId, JSON:  %json',
        [
          '%eventId' => $eventData['eventID'],
          '%json' => $eventDataJson,
        ]);
    }

    try {

      $res = $this->httpClient->post($this->endpoint, [
        'auth' => [$this->username, $this->password, "Basic"],
        'body' => $eventDataJson,
      ]);

      if ($res->getStatusCode() == 200) {
        $this->logger->info('Event logged: %eventId, message sent.', ['%eventId' => $eventData['eventID']]);
        return $eventData;
      }

    }
    catch (\Exception $e) {
      throw new EventException($e->getMessage());
    }
    catch (GuzzleException $e) {
      throw new EventException($e->getMessage());
    }

    return NULL;
  }

  /**
   * Filter events by given key.
   *
   * @param array $events
   *   Events to be filtered.
   * @param string $typeKey
   *   Event type wanted.
   *
   * @return array
   *   Filtered events.
   */
  public function filterEvents(array $events, string $typeKey): array {
    $messageEvents = array_filter($events, function ($event) use ($typeKey) {
      if ($event['eventType'] == $this->eventTypes[$typeKey]) {
        return TRUE;
      }
      return FALSE;
    });

    return [
      'events' => $messageEvents,
      'event_targets' => array_column($messageEvents, 'eventTarget'),
      'event_ids' => array_column($messageEvents, 'eventID'),
    ];
  }

  /**
   * Build event object/array from given data.
   *
   * @param string $eventType
   *   Type of event, must be in self::$eventTypes.
   * @param string $applicationNumber
   *   Application number for event.
   * @param string $eventDescription
   *   Event description.
   * @param string $eventTarget
   *   Event target.
   *
   * @return array
   *   Event data in array.
   *
   * @throws \Drupal\grants_events\EventException
   */
  public function getEventData(string $eventType, string $applicationNumber, string $eventDescription, string $eventTarget): array {
    $eventData = [];

    if (!in_array($eventType, $this->eventTypes)) {
      throw new EventException('Not valid event type: ' . $eventType);
    }
    else {
      $eventData['eventType'] = $eventType;
    }

    $eventData['eventID'] = Uuid::uuid4()->toString();
    $eventData['caseId'] = $applicationNumber;
    $eventData['eventDescription'] = AtvSchema::sanitizeInput($eventDescription);
    $eventData['eventTarget'] = $eventTarget;

    if (!isset($eventData['eventSource'])) {
      $eventData['eventSource'] = getenv('EVENTS_SOURCE');
    }

    $dt = new \DateTime();
    $dt->setTimezone(new \DateTimeZone('Europe/Helsinki'));

    $eventData['timeCreated'] = $eventData['timeUpdated'] = $dt->format('Y-m-d\TH:i:s');
    return $eventData;
  }

  /**
   * Add new event to application document.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   Document to be updated.
   * @param array $eventData
   *   Event data to be added.
   */
  public function addNewEventForApplication(AtvDocument &$document, array $eventData): void {
    $documentContent = $document->getContent();
    $documentEvents = $documentContent['events'] ?? [];
    $documentEvents[] = $eventData;
    $documentContent['events'] = $documentEvents;

    $document->setContent($documentContent);

  }

}
