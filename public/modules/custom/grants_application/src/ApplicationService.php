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
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_application\User\UserInformationService;

/**
 * Class for retrieving / saving application data.
 */
class ApplicationService {

  public function __construct(
    private readonly ApplicationNumberService $applicationNumberService,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FormSettingsServiceInterface $formSettingsService,
    private readonly HelfiAtvService $atvService,
    private readonly LanguageManagerInterface $languageManager,
    private readonly UserInformationService $userInformationService,
    private readonly UuidInterface $uuid,
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
   * @return array
   *   The created draft application data.
   */
  public function createDraft(string $form_identifier, string|null $original_application_number = NULL): array {
    try {
      $entity = $this->getSubmissionEntity(
        $original_application_number,
      );
    }
    catch (\Exception $e) {
      throw $e;
    }

    $settings = $this->formSettingsService->getFormSettingsByFormIdentifier($form_identifier);

    if (!$settings->isCopyable()) {
      throw new \Exception('Copying applications is disabled for this application type.');
    }

    $application_type_id = $settings->getFormId();
    $form_data = [];
    if ($original_application_number) {
      $copy_document = $this->atvService->getDocument($original_application_number);
      $copy_content = $copy_document->getContent();
      $form_data = $copy_content['compensation']['form_data'] ?? [];
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
    $application_name = $settings->toArray()['settings']['title'];
    $application_title = $settings->toArray()['settings']['title'];
    $application_type = $settings->toArray()['settings']['application_type'];

    // @todo Save the react form data in separate atv doc.
    $document = $this->atvService->createAtvDocument(
      $application_uuid,
      $application_number,
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
      'form_data' => $this->removeAttachmentsFromCopiedDocument($form_data),
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
    $now = time();
    ApplicationSubmission::create([
      'document_id' => $document->getId(),
      'business_id' => $businessId,
      'sub' => $user_data->sub,
      'langcode' => $langcode,
      'draft' => TRUE,
      'application_type_id' => $application_type_id,
      'form_identifier' => $entity->get('form_identified')->value,
      'application_number' => $application_number,
      'created' => $now,
      'changed' => $now,
    ])
      ->save();

    $result = [
      'application_number' => $application_number,
      'document_id' => $document->getId(),
    ];

    if ($original_application_number) {
      $result['redirect_url'] = Url::fromRoute(
        'helfi_grants.forms_app',
        ['form_identifier' => $form_identifier, 'application_number' => $application_number],
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
   * @param string $application_number
   *   The application number.
   *
   * @return \Drupal\grants_application\Entity\ApplicationSubmission
   *   The application submission entity.
   */
  public function getSubmissionEntity(string $application_number): ApplicationSubmission {
    if ($this->userInformationService->getApplicantType() === 'private_person') {
      $ids = $this->entityTypeManager
        ->getStorage('application_submission')
        ->getQuery()
        ->accessCheck(TRUE)
        ->condition('sub', $this->userInformationService->getUserData()->sub)
        ->condition('application_number', $application_number)
        ->condition('business_id', '')
        ->execute();

      if ($ids) {
        return ApplicationSubmission::load(reset($ids));
      }
      throw new \Exception('Application not found');
    }
    else {
      $business_id = $this->userInformationService->getApplicantType() === 'registered_community' ?
        $this->userInformationService->getGrantsProfileContent()->getBusinessId() :
        $this->userInformationService->getGrantsProfileContent()->getBusinessId() ?? '';

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

}
