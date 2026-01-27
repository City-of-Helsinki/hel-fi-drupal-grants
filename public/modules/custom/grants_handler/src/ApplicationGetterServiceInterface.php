<?php

declare(strict_types=1);

namespace Drupal\grants_handler;

use Drupal\helfi_atv\AtvDocument;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Provides methods for fetching and building application-related data.
 */
interface ApplicationGetterServiceInterface {

  /**
   * Sets the grants profile service.
   *
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Grants profile service.
   */
  public function setGrantsProfileService(GrantsProfileService $grantsProfileService): void;

  /**
   * Loads the ATV document for an application.
   *
   * @param string $transactionId
   *   Id of the transaction.
   * @param bool $refetch
   *   Force ATV document fetch.
   *
   * @return \Drupal\helfi_atv\AtvDocument|null
   *   The fetched document or NULL on failure.
   */
  public function getAtvDocument(string $transactionId, bool $refetch = FALSE): ?AtvDocument;

  /**
   * Gets company applications, optionally sorted/grouped.
   *
   * @param array $selectedCompany
   *   Company data.
   * @param string $appEnv
   *   Environment.
   * @param bool $sortByFinished
   *   When true, results will be sorted by finished status.
   * @param bool $sortByStatus
   *   Sort by application status.
   * @param string $themeHook
   *   Theme hook to render content. When set, populates #submission with
   *   webform submission data.
   *
   * @return array
   *   Submissions in an array.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function getCompanyApplications(
    array $selectedCompany,
    string $appEnv,
    bool $sortByFinished = FALSE,
    bool $sortByStatus = FALSE,
    string $themeHook = '',
  ): array;

  /**
   * Gets a submission object from local DB and fills form data from ATV.
   *
   * If local submission is not found, creates a new one and sets data.
   *
   * @param string $applicationNumber
   *   Ie GRANTS-DEV-00000098.
   * @param \Drupal\helfi_atv\AtvDocument|null $document
   *   Document to extract values from.
   * @param bool $refetch
   *   Force refetch from ATV.
   * @param bool $skipAccessCheck
   *   Should the access checks be skipped (e.g. Admin UI).
   *
   * @return \Drupal\webform\Entity\WebformSubmission|null
   *   Webform submission.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\grants_mandate\CompanySelectException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   */
  public function submissionObjectFromApplicationNumber(
    string $applicationNumber,
    ?AtvDocument $document = NULL,
    bool $refetch = FALSE,
    bool $skipAccessCheck = FALSE,
  ): ?WebformSubmission;

  /**
   * Gets a data definition instance for an application type.
   *
   * @param string $type
   *   Type of the application.
   *
   * @return mixed
   *   Data definition instance (as created by the application's definition
   *   class).
   */
  public function getDataDefinition(string $type): mixed;

  /**
   * Extracts webform from application number via ATV form_uuid when available.
   *
   * @param string $applicationNumber
   *   Application number.
   *
   * @return \Drupal\webform\Entity\Webform
   *   Webform object.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   */
  public function getWebformFromApplicationNumber(string $applicationNumber): Webform;

}
