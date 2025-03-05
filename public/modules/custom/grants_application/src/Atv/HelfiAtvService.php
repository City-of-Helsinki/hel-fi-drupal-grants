<?php

declare(strict_types=1);

namespace Drupal\grants_application\Atv;

use Drupal\grants_handler\Helpers;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A service to perform form specific operations.
 */
class HelfiAtvService {

  public function __construct(
    #[Autowire(service: 'helfi_atv.atv_service')]
    private readonly AtvService $atvService,
  ) {
  }

  /**
   * Get document from ATV by application number.
   *
   * @param string $application_number
   *   The application number.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   The atv document.
   *
   * @throws \Throwable
   */
  public function getDocument($application_number): AtvDocument {
    $sParams = [
      'transaction_id' => $application_number,
      'lookfor' => 'appenv:' . self::getAppEnv(),
    ];

    try {
      $results = $this->atvService->searchDocuments($sParams, TRUE);
    }
    catch (\Throwable $e) {
      /*
      $this->logger->error(
      'Failed to get document from ATV. Error: @error',
      ['@error' => $e->getMessage()]
      );
       */
      throw $e;
    }

    return reset($results);
  }

  /**
   * Save the document to ATV for the first time.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The atv document.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function saveNewDocument(AtvDocument $document): void {
    $this->atvService->postDocument($document);
  }

  /**
   * Update existing document in ATV.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   The atv document.
   */
  public function updateExistingDocument(AtvDocument $document): void {
    $this->atvService->patchDocument($document->getId(), $document->toArray());
  }

  /**
   * Get the environment.
   *
   * @return string
   *   The environment.
   */
  public static function getAppEnv(): string {
    return match (getenv('APP_ENV')) {
      'development' => 'DEV',
      'testing' => 'TEST',
      'staging' => 'STAGE',
      'production' => 'PROD',
      default => getenv('APP_ENV'),
    };
  }

  /**
   * Get ATV-document.
   *
   * @param string $application_uuid
   *   The uuid.
   * @param string $application_number
   *   The application number.
   * @param string $application_name
   *   The application name.
   * @param string $application_type
   *   The application type.
   * @param string $application_title
   *   The application title.
   * @param string $langcode
   *   The langcode.
   * @param string $sub
   *   The user sub.
   * @param string $company_identifier
   *   The company identifier.
   * @param bool $copy
   *   Is this copied.
   * @param array $selected_company
   *   The company data.
   * @param string|null $applicant_type
   *   The applicant type.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   A proper ATV-document
   */
  public static function createAtvDocument(
    string $application_uuid,
    string $application_number,
    string $application_name,
    string $application_type,
    string $application_title,
    string $langcode,
    string $sub,
    string $company_identifier,
    bool $copy,
    array $selected_company,
    ?string $applicant_type = NULL,
  ): AtvDocument {

    $atvDocument = AtvDocument::create([]);
    $atvDocument->setTransactionId($application_number);

    // @todo Load this from module settings.
    $atvDocument->setStatus('DRAFT');

    $atvDocument->setType($application_type);

    // @todo Check what this is.
    $atvDocument->setService(getenv('ATV_SERVICE'));

    $atvDocument->setUserId($sub);

    // @todo Check what this is x2.
    $atvDocument->setTosFunctionId(getenv('ATV_TOS_FUNCTION_ID'));
    $atvDocument->setTosRecordId(getenv('ATV_TOS_RECORD_ID'));

    if ($applicant_type == 'registered_community') {
      $atvDocument->setBusinessId($company_identifier);
    }

    $atvDocument->setDraft(TRUE);
    $atvDocument->setDeletable(FALSE);

    // @todo Translate the title somehow.
    $humanReadableTypes = [
      'en' => $application_title . '_EN',
      'fi' => $application_title . '_FI',
      'sv' => $application_title . '_SV',
    ];
    $atvDocument->setHumanReadableType($humanReadableTypes);

    $atvDocument->setMetadata([
      'appenv' => Helpers::getAppEnv(),
      'saveid' => $copy ? 'copiedSave' : 'initialSave',
      'applicationnumber' => $application_number,
      'language' => $langcode,
      'applicant_type' => $selected_company['type'],
      'applicant_id' => $selected_company['identifier'],
      'form_uuid' => $application_uuid,
    ]);

    return $atvDocument;
  }

}
