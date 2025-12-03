<?php

declare(strict_types=1);

namespace Drupal\grants_application;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\ApplicationNumberService;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\User\UserInformationService;

/**
 * Class for retrieving / saving application data.
 */
class ApplicationService {

  public function __construct(
    private readonly ApplicationNumberService $applicationNumberService,
    private readonly HelfiAtvService $atvService,
    private readonly FormSettingsService $formSettingsService,
    private readonly LanguageManagerInterface $languageManager,
    private readonly UserInformationService $userInformationService,
    private readonly UuidInterface $uuid,
  ) {
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
  public function createDraft(int $application_type_id, string|null $copy_from = NULL): array {
    $settings = $this->formSettingsService->getFormSettings($application_type_id);

    $form_data = [];
    if ($copy_from) {
      $copy_document = $this->atvService->getDocument($copy_from);
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
      $user_data['sub'],
      $selected_company['identifier'],
      FALSE,
      $selected_company,
      $this->userInformationService->getApplicantType(),
    );

    // Grants_events requires the events-array to exist.
    // And compensation must be json-object.
    $document->setContent([
      'form_data' => $form_data,
      'compensation' => [
        'applicantInfoArray' => [],
      ],
      'formUpdate' => FALSE,
      'statusUpdates' => [],
      'events' => [],
      'messages' => [],
    ]);

    $document = $this->atvService->saveNewDocument($document);
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

}
