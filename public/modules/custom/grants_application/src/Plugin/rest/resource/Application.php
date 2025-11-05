<?php
// phpcs:ignoreFile
namespace Drupal\grants_application\Plugin\rest\resource;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Avus2Integration;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\Helper;
use Drupal\grants_application\Mapper\JsonMapper;
use Drupal\grants_application\User\UserInformationService;
use Drupal\grants_attachments\AttachmentHandler;
use Drupal\grants_events\EventsService;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\grants_handler\ApplicationSubmitType;
use Drupal\grants_handler\Event\ApplicationSubmitEvent;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Handle the ready applications.
 */
#[RestResource(
  id: "application_rest_resource",
  label: new TranslatableMarkup("Application"),
  uri_paths: [
    "canonical" => "/applications/{application_type_id}/application/{application_number}",
    "create" => "/applications/{application_type_id}/application/{application_number}",
    "edit" => "/applications/{application_type_id}/application/{application_number}",
  ]
)]
final class Application extends ResourceBase {

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
   *   The uuid service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfTokenGenerator
   *   The csrf token generator.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\grants_application\Avus2Integration $integration
   *   The integration.
   * @param \Drupal\grants_events\EventsService $eventsService
   *   The event service.
   * @param \Drupal\grants_attachments\AttachmentHandler $attachmentHandler
   *   The attachment handler.
   * @param \Drupal\grants_handler\ApplicationStatusService $applicationStatusService
   *   The application status service.
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
    private CsrfTokenGenerator $csrfTokenGenerator,
    private LanguageManagerInterface $languageManager,
    private EntityTypeManagerInterface $entityTypeManager,
    private EventDispatcherInterface $dispatcher,
    private Avus2Integration $integration,
    private EventsService $eventsService,
    private AttachmentHandler $attachmentHandler,
    private ApplicationStatusService $applicationStatusService,
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
      $container->get(CsrfTokenGenerator::class),
      $container->get(LanguageManagerInterface::class),
      $container->get('entity_type.manager'),
      $container->get(EventDispatcherInterface::class),
      $container->get(Avus2Integration::class),
      $container->get('grants_events.events_service'),
      $container->get('grants_attachments.attachment_handler'),
      $container->get('grants_handler.application_status_service'),
    );
  }

  /**
   * Get an existing application.
   *
   * An application that has been saved as a draft or already sent.
   *
   * @param int $application_type_id
   *   The application type id.
   * @param string|null $application_number
   *   The unique identifier for the application.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get(
    int $application_type_id,
    ?string $application_number,
  ): JsonResponse {
    // @todo Sanitize & validate & authorize properly.
    if (!$application_number) {
      return new JsonResponse([], 400);
    }

    // @todo Parse the last STATUS_UPDATE event here.
    // It can be used to determinate if this is editable.
    try {
      $settings = $this->formSettingsService->getFormSettings($application_type_id);
    }
    catch (\Exception $e) {
      // Cannot find form.
      return new JsonResponse([], 500);
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
      $this->getSubmissionEntity($user_information['sub'], $application_number, $grants_profile_data->getBusinessId());
    }
    catch (\Exception $e) {
      // Cannot get the submission.
      return new JsonResponse(['Unable to fetch submission'], 500);
    }

    try {
      $document = $this->atvService->getDocument($application_number);
      $form_data = $document->getContent();
    }
    catch (\Throwable $e) {
      // @todo helfi_atv -module throws multiple exceptions, handle them accordingly.
      return new JsonResponse([], 500);
    }

    $changeTime = new DrupalDateTime($document->getUpdatedAt());

    // @todo only return required user data to frontend.
    $response = [
      'form_data' => $form_data,
      'grants_profile' => $grants_profile_data->toArray(),
      'last_changed' => $changeTime->getTimestamp(),
      'status' => $document->getStatus(),
      'token' => $this->csrfTokenGenerator->get('rest'),
      'user_data' => $user_information,
      ...$settings->toArray(),
    ];

    return new JsonResponse($response);
  }

  /**
   * Post request.
   *
   * Send application to Avus2
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function post(
    Request $request,
    int $application_type_id,
    ?string $application_number = NULL,
  ): JsonResponse {
    // @todo Sanitize & validate & authorize properly.
    /// phpcs:disable
    // NOSONAR
    $content = json_decode($request->getContent(), TRUE);
    // NOSONAR
    [
      'form_data' => $form_data,
      'attachments' => $attachments,
    ] = $content;

    // phpcs:enable
    try {
      $settings = $this->formSettingsService->getFormSettings($application_type_id);
    }
    catch (\Exception $e) {
      // Cannot find form by application type id.
      return new JsonResponse([], 404);
    }

    if (!$application_number) {
      return new JsonResponse(['missing application number'], 500);
    }

    try {
      $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
      $selected_company = $this->userInformationService->getSelectedCompany();
      $user_data = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      return new JsonResponse([], 500);
    }

    try {
      $submission = $this->getSubmissionEntity(
        $this->userInformationService->getUserData()['sub'],
        $application_number,
        $grants_profile_data->getBusinessId(),
      );
    }
    catch (\Exception $e) {
      // Cannot find correct draft submission.
      return new JsonResponse([], 500);
    }

    try {
      $document = $this->atvService->getDocument($application_number);
    }
    catch (\Throwable $e) {
      // Cannot fetch the corresponding ATV document.
      return new JsonResponse([], 500);
    }

    // Here we do the actual work.
    // Handle bank account file upload / other bank account shenanigans.
    // The bank account file handling causes extra document load and save.
    // No need to do anything with the document before this has been done.
    // Map the React-form data to Avus2-format.
    // Update the ATV document one last time before sending to integration.
    // Send to integration.
    // Update the custom submission entity.
    // Start: Check if the bank file is already added to the ATV document.
    $selected_bank_account_number = $form_data["applicant_info"]["bank_account"]["bank_account"];
    $bank_file = FALSE;
    // @todo Add file type check as well (filetype = 45 etc).
    foreach ($grants_profile_data->getBankAccounts() as $bank_account) {
      $bank_file = array_find($document->getAttachments(), fn(array $attachment) => $bank_account['confirmationFile'] === $attachment['filename']);
    }

    // If not, we must take if from the profile document
    // and upload to application form document.
    $bank_accounts = $grants_profile_data->getBankAccounts();
    $profile_files = $this->userInformationService->getGrantsProfileAttachments();

    try {
      $bank_confirmation_file_array = Helper::findMatchingBankConfirmationFile(
        $selected_bank_account_number,
        $bank_accounts,
        $profile_files,
      );
    }
    catch (\Exception $e) {
      // The user has removed bank account from profile.
      return new JsonResponse(
        ['Your user profile does not contain the given bank account number. Update your user profile and try again.'],
        500
      );
    }

    $actual_file = NULL;
    if (!$bank_file) {
      try {
        /** @var \Drupal\file\FileInterface $actual_file */
        $actual_file = $this->atvService->getAttachment($bank_confirmation_file_array['href']);
      }
      catch (\Exception $e) {
        // File does not exist in atv? Should not be possible.
      }
      if ($actual_file) {
        $this->atvService->addAttachment(
          $document->getId(),
          $bank_confirmation_file_array['filename'],
          $actual_file,
        );
        $actual_file->delete();
        // @todo Add ATT_HANDLER_OK event here probably.
      }

      // After uploading the bank file, reload the document to verify that it exists.
      $document = $this->atvService->getDocument($application_number);
      foreach ($grants_profile_data->getBankAccounts() as $bank_account) {
        $bank_file = array_find(
          $document->getAttachments(),
          fn(array $attachment) => $bank_account['confirmationFile'] === $attachment['filename']);
      }

      // This should not be possible.
      if (!$bank_file) {
        $this->logger->error('User is unable to upload bank file to document or race condition.');
        // We just uploaded it but
      }
    }

    // After bank file has been handled, load the ATV document.
    // Continue with the Avus2-mapping.
    $document = $this->atvService->getDocument($application_number);

    // @todo Better sanitation.
    // $document_data = ['form_data' => $form_data];

    $applicantTypeId = $this->userInformationService->getApplicantTypeId();

    $mappingFileName = "ID$application_type_id.json";
    $mapping = json_decode(file_get_contents(__DIR__ . '/../../../Mapper/Mappings/'.$mappingFileName), TRUE);
    $mapper = new JsonMapper($mapping);
    try {
      $dataSources = $mapper->getCombinedDataSources(
        $form_data,
        $user_data,
        $selected_company,
        $this->userInformationService->getUserProfileData(),
        $this->userInformationService->getGrantsProfileContent(),
        $settings,
        $application_number,
        $applicantTypeId,
      );
    }
    catch (\Exception $e) {
      // Unable to combine datasources, bad atv-connection maybe?
      $this->logger->error('Error while sending the application for the first time: ' . $e->getMessage());
      return new JsonResponse(
        ['error' => $this->stringTranslation->translate('An error occurred while sending the application. Please try again later.')],
        500,
      );
    }

    $document_data = $mapper->map($dataSources);

    // Handle all files.
    $bankFile = $mapper->mapBankFile($selected_bank_account_number, $bank_file);
    $fileData = $mapper->mapFiles($dataSources);

    $fileData['attachmentsInfo']['attachmentsArray'][] = $bankFile;

    $document_data = array_merge($document_data, $fileData);

    // Keep the react-form data.
    $document_data['form_data'] = $form_data;
    $document_data['formUpdate'] = !$submission->get('draft')->value;

    if (!isset($document_data['statusUpdates'])) {
      $document_data['statusUpdates'] = [];
    }

    if (!isset($document_data['events'])) {
      $document_data['events'] = [];
    }

    if (!isset($document_data['messages'])) {
      $document_data['messages'] = [];
    }

    // Save id has previously been saved to database to track
    // unsuccessful submissions due to integration failures.
    // @todo Use drupal uuid service maybe ?
    $save_id = Uuid::uuid4()->toString();

    // We don't use the event api to apply this particular event.
    // instead we just put the event into the document.
    $event = $this->eventsService->getEventData(
      'HANDLER_SEND_INTEGRATION',
      $application_number,
      'Send application to integration.',
      $save_id
    );
    $this->eventsService->addNewEventForApplication($document, $event);

    if ($document->getContent()['events']) {
      $document_data['events'] = $document->getContent()['events'];
    }

    // @todo Add SUBMITTED status here probably.
    //$document->setStatus('SUBMITTED');

    // @codingStandardsIgnoreStart
    // Update the atv document before sending to integration.
    // Lets try a way to hold on to the document data.
    // @todo Sanitize the input.
    // NOSONAR
    $document_data['compensation']['form_data'] = $form_data;
    // NOSONAR
    $document->setContent($document_data);
    // @codingStandardsIgnoreEnd

    // @todo Save the form_data in separate atv doc.
    // Also, on first save we also need to save to the actual ATV document.
    $this->atvService->updateExistingDocument($document);

    // @todo Make sure the formUpdate is set properly.
    // Initial import from ATV MUST have formUpdate FALSE, and
    // any subsequent update must have it as TRUE. The application status
    // handling makes this possibly very complicated, hence separate method
    // figuring it out.
    // This comment^ is from GrantsHandler::getFormUpdate.
    $success = FALSE;
    try {
      $success = $this->integration->sendToAvus2($document, $application_number, $save_id);
    }
    catch (\Exception $e) {
      // Log the exception,
      // return success = false to react.
      // @todo Log the failure to send to integration and return.
    }

    if (!$success) {
      // Avus2 returned non-200 code.
      // Log and return.
    }

    try {
      $submission->setChangedTime(time());
      $submission->set('draft', FALSE);
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
    $this->dispatcher->dispatch(new ApplicationSubmitEvent(ApplicationSubmitType::SUBMIT));

    return new JsonResponse([
      'redirect_url' => Url::fromRoute(
        'grants_handler.completion',
        ['submission_id' => $application_number],
        ['absolute' => TRUE],
      )->toString(),
    ], 200);
  }

  /**
   * Responds to entity PATCH requests.
   *
   * Update existing submission. A few things differ from post-request.
   * - The status (hidden under compensation) must not change anymore.
   * - User cannot delete files but can add more files.
   * - The items added by integration/avus2 must exist(events, messages etc.).
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function patch(
    Request $request,
    int $application_type_id,
    ?string $application_number = NULL,
  ): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    [
      'form_data' => $form_data,
      'attachments' => $attachments,
    ] = $content;

    try {
      $settings = $this->formSettingsService->getFormSettings($application_type_id);
    }
    catch (\Exception $e) {
      // Cannot find form by application type id.
      return new JsonResponse([], 404);
    }

    if (!$application_number) {
      return new JsonResponse(['missing application number'], 500);
    }

    try {
      $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
      $selected_company = $this->userInformationService->getSelectedCompany();
      $user_data = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      return new JsonResponse([], 500);
    }

    try {
      $submission = $this->getSubmissionEntity(
        $this->userInformationService->getUserData()['sub'],
        $application_number,
        $grants_profile_data->getBusinessId(),
      );
    }
    catch (\Exception $e) {
      // Cannot find correct draft submission.
      return new JsonResponse([], 500);
    }

    try {
      $document = $this->atvService->getDocument($application_number);
    }
    catch (\Throwable $e) {
      // Cannot fetch the corresponding ATV document.
      return new JsonResponse([], 500);
    }

    $mappingFileName = "ID$application_type_id.json";
    $mapping = json_decode(file_get_contents(__DIR__ . '/../../../Mapper/Mappings/'.$mappingFileName), TRUE);
    $mapper = new JsonMapper($mapping);

    try {
      $dataSources = $mapper->getCombinedDataSources(
        $form_data,
        $user_data,
        $selected_company,
        $this->userInformationService->getUserProfileData(),
        $this->userInformationService->getGrantsProfileContent(),
        $settings,
        $application_number,
        $this->userInformationService->getApplicantTypeId(),
      );
    }
    catch (\Exception $e) {
      // Unable to combine datasources, bad atv-connection maybe?
      $this->logger->error('Error while sending the application for the first time: ' . $e->getMessage());
      return new JsonResponse(
        ['error' => $this->stringTranslation->translate('An error occurred while sending the application. Please try again later.')],
        500,
      );
    }

    $oldDocument = $document->toArray();

    // Hold on to old values.
    $status = $mapper->getStatusValue($oldDocument);
    // $attachments = $oldDocument['attachmentsInfo'];
    $events = $oldDocument['events'];
    $messages = $oldDocument['messages'];
    $statusUpdates = $oldDocument['statusUpdates'];

    // Map the data again.
    $document_data = $mapper->map($dataSources);
    $patchedFiles = $mapper->patchMappedFiles(
      $oldDocument['attachmentsInfo']['attachmentsArray'],
      $mapper->mapFiles($dataSources)
    );

    $document_data['attachmentsInfo']['attachmentsArray'] = $patchedFiles;
    $document_data['events'] = $events;
    $document_data['messages'] = $messages;
    $document_data['statusUpdates'] = $statusUpdates;
    $document_data['formUpdate'] = TRUE;

    // Status is changed by someone else, we are not allowed to overwrite.
    $document_data['compensation']['applicationInfoArray'] = $mapper->getStatusValue($oldDocument);

    // @todo Add event HANDLER_SEND_INTEGRATION.
    try {
      // @todo Better sanitation.
      // @todo Save the form_data in separate atv doc.
      $document_data = ['form_data' => $form_data ?? []];

      $document->setContent($document_data);

      $this->atvService->updateExistingDocument($document);

      $submission->setChangedTime(time());
      $submission->save();
    }
    catch (\Exception $e) {
      // Unable to find the document.
      return new JsonResponse([], 500);
    }

    // @todo Move ApplicationSubmitEvent and ApplicationSubmitType to
    // grants_application module when this module is enabled in
    // production.
    //
    // This event lets other parts of the system to react
    // to user submitting grants forms.
    $this->dispatcher->dispatch(new ApplicationSubmitEvent(ApplicationSubmitType::SUBMIT));

    return new JsonResponse($document->toArray(), 200);
  }
  // phpcs:enabled

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
