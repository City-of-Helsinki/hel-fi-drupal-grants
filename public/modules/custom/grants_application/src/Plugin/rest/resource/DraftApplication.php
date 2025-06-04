<?php

namespace Drupal\grants_application\Plugin\rest\resource;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Avus2Mapper;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\ApplicationNumberService;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\Helper;
use Drupal\grants_application\User\UserInformationService;
use Drupal\grants_handler\ApplicationSubmitType;
use Drupal\grants_handler\Event\ApplicationSubmitEvent;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use GuzzleHttp\Exception\GuzzleException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Handle the draft applications.
 */
#[RestResource(
  id: "draft_application_rest_resource",
  label: new TranslatableMarkup("Application"),
  uri_paths: [
    "canonical" => "/applications/{application_type_id}/{application_number}",
    "create" => "/applications/{application_type_id}/{application_number}",
    "edit" => "/applications/{application_type_id}/{application_number}",
  ]
)]
final class DraftApplication extends ResourceBase {

  /**
   * Constructs a Drupal\rest\Plugin\rest\resource\EntityResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\grants_application\Form\FormSettingsService $formSettingsService
   *   The form settings service.
   * @param \Drupal\grants_application\User\UserInformationService $userInformationService
   *   The user information service.
   * @param \Drupal\grants_application\Atv\HelfiAtvService $atvService
   *   The helfi atv service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   The uuid interface.
   * @param \Drupal\grants_application\Form\ApplicationNumberService $applicationNumberService
   *   The application number service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager interface.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\grants_application\Avus2Mapper $avus2Mapper
   *   The Avus2-mapper.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfTokenGenerator
   *   The token generator.
   * @param \Psr\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    $serializer_formats,
    LoggerInterface $logger,
    private FormSettingsService $formSettingsService,
    private UserInformationService $userInformationService,
    private HelfiAtvService $atvService,
    private UuidInterface $uuid,
    private ApplicationNumberService $applicationNumberService,
    private LanguageManagerInterface $languageManager,
    private EntityTypeManagerInterface $entityTypeManager,
    private Avus2Mapper $avus2Mapper,
    private CsrfTokenGenerator $csrfTokenGenerator,
    private EventDispatcherInterface $dispatcher,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.channel.grants_application'),
      $container->get(FormSettingsService::class),
      $container->get(UserInformationService::class),
      $container->get(HelfiAtvService::class),
      $container->get(UuidInterface::class),
      $container->get(ApplicationNumberService::class),
      $container->get(LanguageManagerInterface::class),
      $container->get('entity_type.manager'),
      $container->get(Avus2Mapper::class),
      $container->get(CsrfTokenGenerator::class),
      $container->get(EventDispatcherInterface::class),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function routes(): RouteCollection {
    $collection = parent::routes();
    foreach ($collection->all() as $route) {
      $route->addDefaults(['application_number' => NULL]);
    }
    return $collection;
  }

  /**
   * Return initial form data.
   *
   * The atv-document and application number is created in controller.
   *
   * @param int $application_type_id
   *   The application type id.
   * @param string $application_number
   *   The application number.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get(
    int $application_type_id,
    string $application_number,
  ): RedirectResponse|JsonResponse {
    // @todo Sanitize & validate & authorize properly.
    if (!$application_number) {
      return new JsonResponse([], 400);
    }

    try {
      $settings = $this->formSettingsService->getFormSettings($application_type_id);
    }
    catch (\Exception $e) {
      // Cannot find form by application type id.
      return new JsonResponse([], 404);
    }

    if (!$settings->isApplicationOpen()) {
      // @todo Uncomment.
      // return new JsonResponse([], 403);
    }

    try {
      $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
      $user_information = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      // Unable to fetch user information.
      return new JsonResponse([], 500);
    }

    try {
      // Make sure it exists in database.
      $this->getSubmissionEntity($user_information['sub'], $application_number);
    }
    catch (\Exception $e) {
      // Cannot get the submission.
      return new JsonResponse([], 500);
    }

    try {
      $document = $this->atvService->getDocument($application_number);
      $form_data = $document->getContent();
    }
    catch (\Throwable $e) {
      // @todo helfi_atv -module throws multiple exceptions, handle them accordingly.
      return new JsonResponse([], 500);
    }

    // @todo On actual save, we are putting form_data inside
    // "compensation" to prevent it from getting overwritten by the integration.
    // This should be done in more clean way. Maybe separate ATV-doc for react
    // form or something else.
    $response = [];
    $response['form_data'] = $form_data['form_data'] ?? $form_data['compensation']['form_data'];

    // @todo Only return required user data to frontend
    $response['grants_profile'] = $grants_profile_data->toArray();
    $response['user_data'] = $user_information;
    $response['status'] = $document->getStatus();
    $response['token'] = $this->csrfTokenGenerator->get('rest');
    $response = array_merge($response, $settings->toArray());

    return new JsonResponse($response);
  }

  /**
   * Create the initial document.
   *
   * This is only called when a react-form is opened for the first time.
   * After that the patch-function takes care of submitting the form as draft.
   * Submitting is handled in Application-resource.
   *
   * @param int $application_type_id
   *   The application type id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function post(int $application_type_id): JsonResponse {
    try {
      $settings = $this->formSettingsService->getFormSettings($application_type_id);
    }
    catch (\Exception $e) {
      // Cannot find form by application type id.
      return new JsonResponse([], 404);
    }

    try {
      $selected_company = $this->userInformationService->getSelectedCompany();
      $user_data = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      return new JsonResponse([], 500);
    }

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

    $document->setContent([]);

    try {
      $document = $this->atvService->saveNewDocument($document);
      $now = time();
      ApplicationSubmission::create([
        'document_id' => $document->getId(),
        'sub' => $user_data['sub'],
        'langcode' => $langcode,
        'draft' => TRUE,
        'application_type_id' => $application_type_id,
        'application_number' => $application_number,
        'created' => $now,
        'changed' => $now,
      ])
        ->save();

      // @todo We must add bank confirmation file to the document at some point.
      // Either here or when we are doing the actual submission.
    }
    catch (\Exception | GuzzleException $e) {
      // Saving failed.
      return new JsonResponse([], 500);
    }

    return new JsonResponse([
      'application_number' => $application_number,
      'document_id' => $document->getId(),
    ], 200);
  }

  /**
   * Responds to entity PATCH requests.
   *
   * Update existing draft submission.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function patch(
    int $application_type_id,
    string $application_number,
    Request $request,
  ): JsonResponse {
    // @todo Sanitize & validate & authorize properly.
    $content = json_decode($request->getContent(), TRUE);
    [
      'attachments' => $attachments,
      'form_data' => $form_data,
    ] = $content;

    try {
      $settings = $this->formSettingsService->getFormSettings($application_type_id);
    }
    catch (\Exception $e) {
      // Cannot find form by application type id.
      return new JsonResponse([], 404);
    }

    if (!$application_number) {
      // Missing application number.
      return new JsonResponse([], 500);
    }

    try {
      $selected_company = $this->userInformationService->getSelectedCompany();
      $user_data = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      return new JsonResponse([], 500);
    }

    try {
      $submission = $this->getSubmissionEntity(
        $this->userInformationService->getUserData()['sub'],
        $application_number
      );
    }
    catch (\Exception $e) {
      // Something wrong.
      return new JsonResponse([], 500);
    }

    // @todo Check that the application is actually a draft.
    // Actually, the document state should not be > "received"
    // since an application which is taken into processing
    // should not change.
    try {
      $document = $this->atvService->getDocument($application_number);
    }
    catch (\Throwable $e) {
      // Error while fetching the document.
      return new JsonResponse([], 500);
    }

    if (!$document) {
      // Unable to find the document.
      return new JsonResponse([], 500);
    }

    // @todo Better sanitation.
    $document_data = ['form_data' => $form_data];

    $document_data['compensation'] = $this->avus2Mapper->mapApplicationData(
      $form_data,
      $user_data,
      $selected_company,
      $this->userInformationService->getUserProfileData(),
      $this->userInformationService->getGrantsProfileContent(),
      $settings,
      $application_number,
    );

    $document_data['attachmentsInfo'] = $this->avus2Mapper
      ->getAttachmentAndGeneralInfo($attachments, $form_data);

    // @todo clean this up a bit, unnecessarily duplicated variables.
    // Make sure the events and messages are not overridden.
    $content = $document->getContent();
    $content['compensation'] = $document_data['compensation'];
    $content['form_data'] = $form_data;
    // Temporary solution since integration removes the root form_data^.
    $content['compensation']['form_data'] = $form_data;
    $content['attachmentsInfo'] = $document_data['attachmentsInfo'];

    $document->setContent($content);

    try {
      $this->cleanUpAttachments($document, $attachments);
    }
    catch (\Exception $e) {
      // @todo log error
    }

    try {
      // @todo Always get the events and messages from atv submission before overwriting.
      $this->atvService->updateExistingDocument($document);

      $submission->setChangedTime(time());
      $submission->save();
    }
    catch (\Exception $e) {
      return new JsonResponse([], 500);
    }

    // @todo Move ApplicationSubmitEvent and ApplicationSubmitType to
    // grants_application module when this module is enabled in
    // production.
    //
    // This event lets other parts of the system to react
    // to user submitting grants forms.
    $this->dispatcher->dispatch(new ApplicationSubmitEvent(ApplicationSubmitType::SUBMIT_DRAFT));

    return new JsonResponse($document->toArray(), 200);
  }

  /**
   * Remove unused attachments from ATV document.
   *
   * @param object $document
   *   The ATV document.
   * @param array $attachments
   *   The attachments.
   */
  private function cleanUpAttachments($document, $attachments = []): void {
    $attachment_ids = array_column($attachments, 'fileId');

    foreach ($document->getAttachments() as $attachment) {
      if (!in_array($attachment['id'], $attachment_ids)) {
        $this->atvService->removeAttachment($document->getId(), $attachment['id']);
      }
    }
  }

  /**
   * Get the application submission.
   *
   * @param string $sub
   *   User uuid.
   * @param string $application_number
   *   The application number.
   *
   * @return \Drupal\grants_application\Entity\ApplicationSubmission
   *   The application submission entity.
   */
  private function getSubmissionEntity(string $sub, string $application_number): ApplicationSubmission {
    $ids = $this->entityTypeManager
      ->getStorage('application_submission')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('sub', $sub)
      ->condition('application_number', $application_number)
      ->execute();

    if (!$ids) {
      throw new \Exception('Application not found');
    }

    return ApplicationSubmission::load(reset($ids));
  }

}
