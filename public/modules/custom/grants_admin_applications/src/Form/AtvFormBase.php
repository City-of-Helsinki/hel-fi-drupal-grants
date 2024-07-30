<?php

namespace Drupal\grants_admin_applications\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationHelpers;
use Drupal\grants_handler\EventsService;
use Drupal\grants_handler\Helpers;
use Drupal\grants_handler\MessageService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use GuzzleHttp\Client;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base form class with some ATV methods.
 */
abstract class AtvFormBase extends FormBase {

  const LOGGER_CHANNEL = 'grants_admin_applications';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The application getter service.
   *
   * @var \Drupal\grants_handler\ApplicationGetterService
   */
  protected ApplicationGetterService $applicationGetterService;

  /**
   * The http client.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $httpClient;

  /**
   * The events service.
   *
   * @var \Drupal\grants_handler\EventsService
   */
  protected EventsService $eventsService;

  /**
   * Access to ATV.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Immutable Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Message service.
   *
   * @var \Drupal\grants_handler\MessageService
   */
  protected MessageService $messageService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected TimeInterface $time;

  /**
   * Constructs a new AtvFormBase.
   */
  public function __construct(
    Connection $database,
    ApplicationGetterService $applicationGetterService,
    Client $httpClient,
    EventsService $eventsService,
    AtvService $atvService,
    ConfigFactory $config,
    MessageService $messageService,
    AccountProxyInterface $current_user,
    TimeInterface $time,
  ) {
    $this->database = $database;
    $this->applicationGetterService = $applicationGetterService;
    $this->httpClient = $httpClient;
    $this->eventsService = $eventsService;
    $this->atvService = $atvService;
    $this->config = $config->get('grants_metadata.settings');
    $this->messageService = $messageService;
    $this->currentUser = $current_user;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    // Late static binding.
    $class = static::class;
    return new $class(
      $container->get('database'),
      $container->get('grants_handler.application_getter_service'),
      $container->get('http_client'),
      $container->get('grants_handler.events_service'),
      $container->get('helfi_atv.atv_service'),
      $container->get('config.factory'),
      $container->get('grants_handler.message_service'),
      $container->get('current_user'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Update resent application save id to database.
   *
   * @param string $applicationNumber
   *   The application number.
   * @param string $saveId
   *   The new save id.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Drupal\grants_mandate\CompanySelectException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Exception
   */
  public function updateSaveIdRecord(string $applicationNumber, string $saveId): void {
    $webform_submission = $this->applicationGetterService->submissionObjectFromApplicationNumber(
      $applicationNumber,
      NULL,
      FALSE,
      TRUE,
    );

    $fields = [
      'webform_id' => ($webform_submission) ? $webform_submission->getWebform()
        ->id() : '',
      'sid' => ($webform_submission) ? $webform_submission->id() : 0,
      'handler_id' => ApplicationHelpers::HANDLER_ID,
      'application_number' => $applicationNumber,
      'saveid' => $saveId,
      'uid' => $this->currentUser->id(),
      'user_uuid' => '',
      'timestamp' => (string) $this->time->getRequestTime(),
    ];

    $query = $this->database->insert(ApplicationHelpers::TABLE, $fields);
    $query->fields($fields)->execute();
  }

  /**
   * Attempts to resend ATV document through integrations.
   *
   * @param \Drupal\helfi_atv\AtvDocument $atvDoc
   *   The document to be resent.
   * @param string $applicationId
   *   Application id.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function sendApplicationToIntegrations(AtvDocument $atvDoc, string $applicationId): void {
    $headers = [];
    $saveId = Uuid::uuid4()->toString();
    // Current environment as a header to be added to meta -fields.
    $headers['X-hki-appEnv'] = Helpers::getAppEnv();
    $headers['X-hki-applicationNumber'] = $applicationId;

    $content = $atvDoc->getContent();
    $status = $atvDoc->getStatus();
    $content['formUpdate'] = TRUE;

    // First imports cannot be with TRUE values, so set it as false for
    // SUBMITTED & DRAFT. @see ApplicationHandler::getFormUpdate comments.
    if (in_array($status, ['SUBMITTED', 'DRAFT'])) {
      $content['formUpdate'] = FALSE;
    }

    $myJSON = Json::encode($content);

    // Usually we set drafts to submitted state before sending to integrations,
    // should we do the same here?
    $endpoint = getenv('AVUSTUS2_ENDPOINT');
    $username = getenv('AVUSTUS2_USERNAME');
    $password = getenv('AVUSTUS2_PASSWORD');

    try {
      $headers['X-hki-saveId'] = $saveId;
      $this->updateSaveIdRecord($applicationId, $saveId);

      $res = $this->httpClient->post($endpoint, [
        'auth' => [
          $username,
          $password,
          "Basic",
        ],
        'body' => $myJSON,
        'headers' => $headers,
      ]);

      $status = $res->getStatusCode();

      $this->messenger()->addStatus('Integration status code: ' . $status);

      $body = $res->getBody()->getContents();
      $this->messenger()->addStatus('Integration response: ' . $body);
      $this->messenger()->addStatus('Updated saveId to: ' . $saveId);

      $this->eventsService->logEvent(
        $applicationId,
        'HANDLER_RESEND_APP',
        $this->t('Application resent from Drupal Admin UI', [], ['context' => 'grants_handler']),
        $applicationId
      );

      $this->logger(self::LOGGER_CHANNEL)->info(
        'Application resend - Integration status: @status - Response: @response',
        [
          '@status' => $status,
          '@response' => $body,
        ]
      );
    }
    catch (\Exception $e) {
      $this->logger(self::LOGGER_CHANNEL)
        ->error('Application resending failed: @error', ['@error' => $e->getMessage()]);
      $this->messenger()
        ->addError($this->t('Application resending failed: @error', ['@error' => $e->getMessage()]));
    }
  }

}
