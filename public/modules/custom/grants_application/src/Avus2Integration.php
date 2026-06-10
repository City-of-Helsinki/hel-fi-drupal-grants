<?php

declare(strict_types=1);

namespace Drupal\grants_application;

use Drupal\Component\Serialization\Json;
use Drupal\grants_metadata\AtvSchema;
use Drupal\helfi_atv\AtvDocument;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Sentry\Breadcrumb;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function Sentry\addBreadcrumb;

/**
 * Avus2 integration.
 */
class Avus2Integration {

  /**
   * The endpoint.
   */
  private string $endpoint;

  /**
   * The username.
   */
  private string $username;

  /**
   * The password.
   */
  private string $password;

  /**
   * The constructor.
   */
  public function __construct(
    #[Autowire(service: 'grants_metadata.atv_schema')]
    private readonly AtvSchema $atvSchema,
    private readonly ClientInterface $httpClient,
  ) {
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
   * @param bool $integrationError
   *   Try submitting the application again from scratch.
   *
   * @throws \Drupal\grants_application\Avus2Exception
   *   If the request fails.
   */
  public function sendToAvus2(AtvDocument $document, string $application_number, string $save_id, bool $integrationError): void {

    // The content contains also the data used by react form,
    // we don't want to send that.
    $content = $document->getContent();
    unset($content['form_data']);
    unset($content['compensation']['form_data']);
    $json = Json::encode($content);

    $headers = [];

    if ($integrationError) {
      // We set the data source for integration to be used in controlling
      // application testing in problematic cases.
      // FIXME: this is immediately overridden to USER.
      $headers['X-hki-UpdateSource'] = 'RESEND';
    }

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

    // Leave a trail for Sentry so a later exception has the context of the
    // Avus2 request that preceded it.
    addBreadcrumb(new Breadcrumb(
      Breadcrumb::LEVEL_INFO,
      Breadcrumb::TYPE_HTTP,
      'avus2_integration',
      'Sending application to Avus2',
      [
        'application_number' => $application_number,
        'save_id' => $save_id,
        'case_status' => $headers['X-Case-Status'],
        'integration_error' => $integrationError,
      ],
    ));

    try {
      $res = $this->httpClient->request('POST', $this->endpoint, [
        'auth' => [
          $this->username,
          $this->password,
          "Basic",
        ],
        'body' => $json,
        'headers' => $headers,
      ]);

      if ($res->getStatusCode() !== 200) {
        throw new Avus2Exception('Avus2 integration failed: ' . $res->getBody()->getContents());
      }
    }
    catch (GuzzleException $e) {
      throw new Avus2Exception($e->getMessage(), $e->getCode(), $e);
    }
  }

}
