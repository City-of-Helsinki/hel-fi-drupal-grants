<?php

declare(strict_types=1);

namespace Drupal\grants_application;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\ApplicationNumberService;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_application\User\UserInformationService;

/**
 * Class for retrieving / saving application data.
 */
class ApplicationService {

  /**
   * Fields that contain a compensation issuer.
   */
  private const ISSUER_FIELDS = [
    'other_compensation_issuer',
    'other_applied_compensation_issuer',
  ];

  /**
   * Legacy issuer labels (fi, en, sv) mapped to the current enum values.
   */
  private const LEGACY_ISSUER_VALUES = [
    'Valtio' => '1',
    'State' => '1',
    'Stat' => '1',
    'EU' => '3',
    'Muu' => '4',
    'Other' => '4',
    'Övrig' => '4',
    'Säätiö' => '5',
    'Foundation' => '5',
    'Fundament' => '5',
  ];

  public function __construct(
    private readonly ApplicationNumberService $applicationNumberService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FormSettingsServiceInterface $formSettingsService,
    private readonly HelfiAtvService $atvService,
    private readonly LanguageManagerInterface $languageManager,
    private readonly UserInformationService $userInformationService,
    private readonly UuidInterface $uuid,
    private readonly AccountProxyInterface $accountProxy,
  ) {
  }

  /**
   * Creates a new draft application.
   *
   * @param string $form_identifier
   *   The application type ID.
   * @param string|null $original_application_number
   *   The application number to copy from.
   *
   * @return array<mixed>
   *   The created draft application data.
   */
  public function createCopy(string $form_identifier, string|null $original_application_number = NULL): array {
    try {
      $entity = $this->getSubmissionEntity(
        $original_application_number,
      );
    }
    catch (\Exception $e) {
      throw $e;
    }

    $settings = $this->formSettingsService->getFormSettingsByFormIdentifier($form_identifier);

    if (!$settings->canBeCopied()) {
      throw new \Exception('Copying applications is disabled or the application is not open for this application type.');
    }

    $sideDocumentId = $entity->getSideDocumentId();
    try {
      $documentToCopy = $this->atvService->getDocumentById($sideDocumentId);
    }
    catch (\Exception $e) {
      throw new \Exception('Unable to fetch the original document.');
    }

    $copiedDocumentContent = $documentToCopy->getContent();

    $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
    $selected_company = $this->userInformationService->getSelectedCompany();
    $user_data = $this->userInformationService->getUserData();

    $application_uuid = $this->uuid->generate();
    $env = Helper::getAppEnv();

    $application_type_id = $settings->getFormId();
    $new_application_number = $this->applicationNumberService
      ->createNewApplicationNumber($env, $application_type_id);

    $langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();

    $application_name = $settings->toArray()['settings']['title'];
    $application_title = $settings->toArray()['settings']['title'];
    $application_type = $settings->toArray()['settings']['application_type'];

    // The actual ATV-document copy.
    $document = $this->atvService->createAtvDocument(
      $application_uuid,
      $new_application_number,
      $application_name,
      $application_type,
      $application_title,
      $langcode,
      $user_data->sub,
      $selected_company['identifier'],
      FALSE,
      $selected_company,
      $this->userInformationService->getApplicantType(),
    );

    // Grants_events requires the events-array to exist.
    // And compensation must be json-object.
    $document->setContent([
      'compensation' => [
        'applicantInfoArray' => [],
      ],
      'formUpdate' => FALSE,
      'statusUpdates' => [],
      'events' => [],
      'messages' => [],
    ]);

    if ($this->userInformationService->getApplicantType() === 'private_person') {
      $businessId = '';
    }
    else {
      $businessId =
        $this->userInformationService->getApplicantType() == 'registered_community' ?
          $grants_profile_data->getBusinessId() :
          $grants_profile_data->getBusinessId() ?? '';
    }

    $document = $this->atvService->saveNewDocument($document);
    // Side document copy containing the react-data.
    $sideDocument = $this->atvService->createSideDocument(
      $application_type,
      $application_title,
      $user_data->sub,
      $selected_company,
      $document->getId(),
    );
    $sideDocument->setContent($this->convertLegacyIssuerValues(
      $this->removeAttachmentsFromCopiedDocument($copiedDocumentContent),
    ));
    $sideDocument = $this->atvService->saveNewDocument($sideDocument);

    $now = time();
    ApplicationSubmission::create([
      'document_id' => $document->getId(),
      'business_id' => $businessId,
      'sub' => $user_data->sub,
      'langcode' => $langcode,
      'draft' => TRUE,
      'application_type_id' => $application_type_id,
      'form_identifier' => $entity->get('form_identifier')->value,
      'application_number' => $new_application_number,
      'side_document_id' => $sideDocument->getId(),
      'created' => $now,
      'changed' => $now,
    ])
      ->save();

    $result = [
      'application_number' => $new_application_number,
      'document_id' => $document->getId(),
    ];

    if ($original_application_number) {
      $result['redirect_url'] = Url::fromRoute(
        'helfi_grants.forms_app',
        ['form_identifier' => $form_identifier, 'application_number' => $new_application_number],
        ['absolute' => TRUE],
      )->toString();
    }

    return $result;
  }

  /**
   * Remove attachments from a copied document.
   *
   * @param array $form_data
   *   The form data array.
   *
   * @return array
   *   The form data array without attachments.
   */
  private function removeAttachmentsFromCopiedDocument(array $form_data): array {
    if (isset($form_data['attachments_step']['attachments_section'])) {
      unset($form_data['attachments_step']['attachments_section']);
    }
    return $form_data;
  }

  /**
   * Convert legacy compensation issuer values to the current enum values.
   *
   * Applications saved before the issuer enums were changed to Avus2 codes
   * contain translated labels, which the current schemas don't allow.
   *
   * @param array<string, mixed> $form_data
   *   The form data array.
   *
   * @return array<string, mixed>
   *   The form data array with the issuer values converted.
   */
  private function convertLegacyIssuerValues(array $form_data): array {
    array_walk_recursive($form_data, function (&$value, $key) {
      if (
        in_array($key, self::ISSUER_FIELDS, TRUE) &&
        is_string($value) &&
        isset(self::LEGACY_ISSUER_VALUES[$value])
      ) {
        $value = self::LEGACY_ISSUER_VALUES[$value];
      }
    });
    return $form_data;
  }

  /**
   * Get the application submission.
   *
   * @param string $application_number
   *   The application number.
   *
   * @return \Drupal\grants_application\Entity\ApplicationSubmission
   *   The application submission entity.
   */
  public function getSubmissionEntity(string $application_number): ApplicationSubmission {
    $submission = NULL;

    $query = $this->entityTypeManager
      ->getStorage('application_submission')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('application_number', $application_number);

    if ($this->userInformationService->getApplicantType() === 'private_person') {
      $query->condition('sub', $this->userInformationService->getUserData()->sub);
    }
    else {
      // Throws exception if fails.
      $grantsProfile = $this->userInformationService->getGrantsProfileContent();

      $business_id = $grantsProfile->getBusinessId() ?? '';
      $query->condition('business_id', $business_id);
    }

    $ids = $query->execute();
    if (!$ids) {
      throw new \Exception('Application not found');
    }

    $submission = ApplicationSubmission::load(reset($ids));
    if (
      $submission &&
      $submission->access('edit', $this->accountProxy->getAccount(), TRUE)->isForbidden()
    ) {
      throw new \Exception('Application not found');
    }

    return $submission;
  }

}
