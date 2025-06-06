<?php

namespace Drupal\grants_application;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\grants_metadata\AtvSchema;
use Drupal\helfi_atv\AtvDocument;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Avus2 integration.
 */
final class Avus2Integration {

  /**
   * The endpoint.
   *
   * @var string
   */
  private string $endpoint;

  /**
   * The username.
   *
   * @var string
   */
  private string $username;

  /**
   * The password.
   *
   * @var string
   */
  private string $password;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private LoggerChannelInterface $logger;

  /**
   * The constructor.
   */
  public function __construct(
    private LoggerChannelFactoryInterface $loggerFactory,
    #[Autowire(service: 'grants_metadata.atv_schema')]
    private AtvSchema $atvSchema,
    private ClientInterface $httpClient,
  ) {
    $this->logger = $this->loggerFactory->get('avus2_integration');

    if ($schema = getenv('ATV_SCHEMA_PATH')) {
      $this->atvSchema->setSchema($schema);
    }

    $this->endpoint = getenv('AVUSTUS2_ENDPOINT') ?: '';
    $this->username = getenv('AVUSTUS2_USERNAME') ?: '';
    $this->password = getenv('AVUSTUS2_PASSWORD') ?: '';
  }

  /**
   * Send the form data to Avus2.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The document.
   * @param string $application_number
   *   The application number.
   * @param string $save_id
   *   The save id.
   *
   * @return bool
   *   Successful submission.
   */
  public function sendToAvus2(AtvDocument $document, string $application_number, string $save_id): bool {

    // The content contains also the data used by react form,
    // we don't want to send that.
    $content = $document->getContent();
    unset($content['form_data']);
    unset($content['compensation']['form_data']);
    $json = Json::encode($content);

    try {
      $headers = [];

      // Get status from updated document.
      $headers['X-Case-Status'] = $document->getStatus();

      // We set the data source for integration to be used in controlling
      // application testing in problematic cases.
      $headers['X-hki-UpdateSource'] = 'USER';

      // Current environment as a header to be added to meta -fields.
      $headers['X-hki-appEnv'] = Helper::getAppEnv();
      // Set application number to meta as well to enable better searches.
      $headers['X-hki-applicationNumber'] = $application_number;

      // Set new saveid to header.
      $headers['X-hki-saveId'] = $save_id;

      $res = $this->httpClient->request('POST', $this->endpoint, [
        'auth' => [
          $this->username,
          $this->password,
          "Basic",
        ],
        'body' => $json,
        'headers' => $headers,
      ]);

      return $res->getStatusCode() === 200;
    }
    catch (\Exception $e) {
      /*
      $this->messenger->addError(
      $this->t('Application saving failed, error has been logged.',
      [],
      ['context' => 'grants_handler']),
      );
       */
      $this->logger->error('Error saving application: %msg', ['%msg' => $e->getMessage()]);
      // \Sentry\captureException($e);
      return FALSE;
    }
  }

}
