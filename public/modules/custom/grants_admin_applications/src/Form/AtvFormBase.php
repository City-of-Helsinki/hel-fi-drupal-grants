<?php

namespace Drupal\grants_admin_applications\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\helfi_atv\AtvDocument;
use Ramsey\Uuid\Uuid;

/**
 * Base form class with some ATV methods.
 */
abstract class AtvFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Gets the logger channel.
   *
   * @return \Psr\Log\LoggerInterface
   *   The logger for the given channel.
   */
  public static function getLoggerChannel() {
    $loggerFactory = \Drupal::service('logger.factory');
    return $loggerFactory->get('grants_admin_applications');
  }

  /**
   * Update resent application save id to database.
   *
   * @param mixed $applicationNumber
   *   The application number.
   * @param mixed $saveId
   *   The new save id.
   */
  public static function updateSaveIdRecord(string $applicationNumber, string $saveId) {

    $database = \Drupal::service('database');
    $webform_submission = ApplicationHandler::submissionObjectFromApplicationNumber(
      $applicationNumber,
      NULL,
      FALSE,
      TRUE,
    );

    $fields = [
      'webform_id' => ($webform_submission) ? $webform_submission->getWebform()
        ->id() : '',
      'sid' => ($webform_submission) ? $webform_submission->id() : 0,
      'handler_id' => ApplicationHandler::HANDLER_ID,
      'application_number' => $applicationNumber,
      'saveid' => $saveId,
      'uid' => \Drupal::currentUser()->id(),
      'user_uuid' => '',
      'timestamp' => (string) \Drupal::time()->getRequestTime(),
    ];

    $query = $database->insert(ApplicationHandler::TABLE, $fields);
    $query->fields($fields)->execute();

  }

  /**
   * Attempts to resend ATV document through integrations.
   *
   * @param Drupal\helfi_atv\AtvDocument $atvDoc
   *   The document to be resent.
   * @param string $applicationId
   *   Application id.
   */
  public static function sendApplicationToIntegrations(AtvDocument $atvDoc, string $applicationId) {
    $httpClient = \Drupal::service('http_client');
    $messenger = \Drupal::service('messenger');
    $logger = self::getLoggerChannel();

    $headers = [];
    $saveId = Uuid::uuid4()->toString();
    // Current environment as a header to be added to meta -fields.
    $headers['X-hki-appEnv'] = ApplicationHandler::getAppEnv();
    $headers['X-hki-applicationNumber'] = $applicationId;

    $content = $atvDoc->getContent();
    $myJSON = Json::encode($content);

    // Usually we set drafts to submitted state before sending to integrations,
    // should we do the same here?
    $endpoint = getenv('AVUSTUS2_ENDPOINT');
    $username = getenv('AVUSTUS2_USERNAME');
    $password = getenv('AVUSTUS2_PASSWORD');

    try {

      $headers['X-hki-saveId'] = $saveId;
      self::updateSaveIdRecord($applicationId, $saveId);

      $res = $httpClient->post($endpoint, [
        'auth' => [
          $username,
          $password,
          "Basic",
        ],
        'body' => $myJSON,
        'headers' => $headers,
      ]);

      $status = $res->getStatusCode();

      $messenger->addStatus('Integration status code: ' . $status);

      $body = $res->getBody()->getContents();
      $messenger->addStatus('Integration response: ' . $body);
      $messenger->addStatus('Updated saveId to: ' . $saveId);

      $logger->info(
        'Application resend - Integration status: @status - Response: @response',
        [
          '@status' => $status,
          '@response' => $body,
        ]
      );
    }
    catch (\Exception $e) {
      $logger->error('Application resending failed: @error', ['@error' => $e->getMessage()]);
      $messenger->addError(t('Application resending failed: @error', ['@error' => $e->getMessage()]));
    }
  }

}
