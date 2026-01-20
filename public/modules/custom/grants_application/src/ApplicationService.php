<?php

declare(strict_types=1);

namespace Drupal\grants_application;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\ApplicationNumberService;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\User\UserInformationService;
use Drupal\helfi_atv\AtvDocument;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Class for retrieving / saving application data.
 */
class ApplicationService {

  public function __construct(
    private readonly ApplicationNumberService $applicationNumberService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FormSettingsService $formSettingsService,
    private readonly HelfiAtvService $atvService,
    private readonly LanguageManagerInterface $languageManager,
    #[Autowire(service: 'logger.channel.grants_application')]
    private readonly LoggerInterface $logger,
    private readonly UserInformationService $userInformationService,
    private readonly UuidInterface $uuid,
  ) {
  }

  /**
   * Create the draft application document.
   *
   * @param int $application_type_id
   *   The application type id.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   The atv document.
   */
  public function createDraft(int $application_type_id): AtvDocument {
    try {
      $settings = $this->formSettingsService->getFormSettings($application_type_id);
    }
    catch (\Exception $e) {
      throw $e;
    }

    try {
      $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
      $selected_company = $this->userInformationService->getSelectedCompany();
      $user_data = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      throw $e;
    }

    // @todo Application number generation must match the existing shenanigans,
    // or we must start from application number 1000 or something.
    $application_uuid = $this->uuid->generate();
    $env = Helper::getAppEnv();

    $application_number = $this->applicationNumberService
      ->createNewApplicationNumber($env, $application_type_id);

    $langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    $application_title = $settings->toArray()['settings']['title'];
    $application_type = $settings->toArray()['settings']['application_type'];

    $document = $this->atvService->createAtvDocument(
      $application_uuid,
      $application_number,
      $application_type,
      $application_title,
      $langcode,
      $user_data['sub'],
      $selected_company['identifier'],
      FALSE,
      $selected_company,
      $this->userInformationService->getApplicantType(),
    );

    $document->setContent([
      'compensation' => [
        'applicantInfoArray' => [],
      ],
      'formUpdate' => FALSE,
      'statusUpdates' => [],
      'events' => [],
      'messages' => [],
    ]);

    try {
      $document = $this->atvService->saveNewDocument($document);
    }
    catch (\Exception $e) {
      throw $e;
    }

    $sideDocument = $this->atvService->createSideDocument(
      $application_type,
      $application_title,
      $user_data['sub'],
      $selected_company,
      $document->getId(),
      $document->getId(),
    );

    try {
      $sideDocument = $this->atvService->saveNewDocument($sideDocument);
    }
    catch (\Exception $e) {
      $this->atvService->deleteDocument($document);
      throw $e;
    }

    $now = time();
    $entity = ApplicationSubmission::create([
      'document_id' => $document->getId(),
      'business_id' => $grants_profile_data->getBusinessId(),
      'sub' => $user_data['sub'],
      'langcode' => $langcode,
      'draft' => TRUE,
      'application_type_id' => $application_type_id,
      'application_number' => $application_number,
      'created' => $now,
      'changed' => $now,
      'side_document_id' => $sideDocument->getId(),
    ]);
    $entity->save();

    return $document;
  }

  /**
   * Creates a new draft application.
   *
   * @param int $application_type_id
   *   The application type ID.
   * @param string|null $copy_from
   *   The application number to copy from.
   *
   * @return array
   *   The created draft application data.
   */
  public function createCopy(int $application_type_id, string|null $copy_from = NULL): array {
    try {
      $entity = $this->getSubmissionEntity(
        $this->userInformationService->getUserData()['sub'],
        $copy_from,
        $this->userInformationService->getGrantsProfileContent()->getBusinessId(),
      );
    }
    catch (\Exception $e) {
      $this->logger->error('Unable to fetch application to copy: @message', [
        '@message' => $e->getMessage(),
        'application_number' => $copy_from,
        'user_id' => $this->userInformationService->getUserData()['sub'],
      ]);

      throw $e;
    }

    $settings = $this->formSettingsService->getFormSettings($application_type_id);

    if (!$settings->isCopyable()) {
      throw new \Exception('Copying applications is disabled for this application type.');
    }

    $form_data = [];
    if ($copy_from) {
      $copy_document = $this->atvService->getDocumentById($entity->getSideDocumentId());
      $form_data = $copy_document->getContent()['form_data'] ?? [];
    }

    $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
    $selected_company = $this->userInformationService->getSelectedCompany();
    $user_data = $this->userInformationService->getUserData();

    $application_uuid = $this->uuid->generate();
    $env = Helper::getAppEnv();

    // @todo Application number generation must match the existing shenanigans,
    // or we must start from application number 1000 or something.
    $application_number = $this->applicationNumberService
      ->createNewApplicationNumber($env, $application_type_id);

    $langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();
    $application_title = $settings->toArray()['settings']['title'];
    $application_type = $settings->toArray()['settings']['application_type'];

    $document = $this->atvService->createAtvDocument(
      $application_uuid,
      $application_number,
      $application_type,
      $application_title,
      $langcode,
      $user_data['sub'],
      $selected_company['identifier'],
      TRUE,
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
    $document = $this->atvService->saveNewDocument($document);

    // Create a side document which only contains the react-form data.
    // The original document is set as parent.
    $sideDocument = $this->atvService->createSideDocument(
      $application_type,
      $application_title,
      $user_data['sub'],
      $selected_company,
      $document->getId(),
      $application_type,
    );

    $sideDocument->setContent([
      'messages' => [],
      'form_data' => $this->removeAttachmentsFromCopiedDocument($form_data),
    ]);
    $sideDocument->setTransactionId($document->getId());
    $sideDocument = $this->atvService->saveNewDocument($sideDocument);

    $now = time();
    ApplicationSubmission::create([
      'document_id' => $document->getId(),
      'business_id' => $grants_profile_data->getBusinessId(),
      'sub' => $user_data['sub'],
      'langcode' => $langcode,
      'draft' => TRUE,
      'application_type_id' => $application_type_id,
      'application_number' => $application_number,
      'created' => $now,
      'changed' => $now,
      'side_document_id' => $sideDocument->getId(),
    ])
      ->save();

    $result = [
      'application_number' => $application_number,
      'document_id' => $document->getId(),
    ];

    if ($copy_from) {
      $result['redirect_url'] = Url::fromRoute(
        'helfi_grants.forms_app',
        ['id' => $application_type_id, 'application_number' => $application_number],
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
    $removeKeys = function (array $data) use (&$removeKeys) {
      foreach ($data as $key => $value) {
        if ($key === 'file') {
          unset($data[$key]);
        }
        elseif (is_array($value)) {
          $data[$key] = $removeKeys($value);
        }
      }
      return $data;
    };

    return $removeKeys($form_data);
  }

  /**
   * Get the application submission.
   *
   * @param string $sub
   *   User uuid.
   * @param string $application_number
   *   The application number.
   * @param string $business_id
   *   The business id.
   *
   * @return \Drupal\grants_application\Entity\ApplicationSubmission
   *   The application submission entity.
   */
  private function getSubmissionEntity(string $sub, string $application_number, string $business_id): ApplicationSubmission {
    // @todo Duplicated, put this in better place.
    $ids = $this->entityTypeManager
      ->getStorage('application_submission')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('sub', $sub)
      ->condition('application_number', $application_number)
      ->execute();

    if ($ids) {
      return ApplicationSubmission::load(reset($ids));
    }

    $ids = $this->entityTypeManager
      ->getStorage('application_submission')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('business_id', $business_id)
      ->condition('application_number', $application_number)
      ->execute();

    if ($ids) {
      return ApplicationSubmission::load(reset($ids));
    }

    throw new \Exception('Application not found');
  }

}
