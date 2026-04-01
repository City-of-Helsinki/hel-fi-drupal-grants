<?php
// phpcs:ignoreFile
namespace Drupal\grants_application\Plugin\rest\resource;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\content_lock\ContentLock\ContentLockInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Avus2DataParser;
use Drupal\grants_application\Avus2Integration;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_application\Mapper\JsonMapperService;
use Drupal\grants_application\JsonSchemaValidator;
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
    "canonical" => "/applications/{form_identifier}/application/{application_number}",
    "create" => "/applications/{form_identifier}/application/{application_number}",
    "edit" => "/applications/{form_identifier}/application/{application_number}",
  ]
)]
final class Application extends ResourceBase {
  use StringTranslationTrait;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    $serializer_formats,
    LoggerInterface $logger,
    private FormSettingsServiceInterface $formSettingsService,
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
    private JsonSchemaValidator $jsonSchemaValidator,
    private ContentLockInterface $contentLock,
    private AccountProxyInterface $accountProxy,
    private JsonMapperService $jsonMapperService,
    private Avus2DataParser $avus2DataParser,
  ) {
    // @todo Use autowiretrait.
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    // @todo Use autowiretrait.
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.channel.grants_application'),
      $container->get(FormSettingsServiceInterface::class),
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
      $container->get(JsonSchemaValidator::class),
      $container->get('content_lock'),
      $container->get('current_user'),
      $container->get(JsonMapperService::class),
      $container->get(Avus2DataParser::class),
    );
  }

  /**
   * Get an existing application.
   *
   * An application that has been saved as a draft or already sent.
   *
   * @param string $form_identifier
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
    string $form_identifier,
    ?string $application_number,
  ): JsonResponse {
    if (!$application_number) {
      return new JsonResponse([], 400);
    }
    // @todo Parse the last STATUS_UPDATE event here.
    // It can be used to determinate if this is editable.
    try {
      $settings = $this->formSettingsService->getFormSettingsByFormIdentifier($form_identifier);
    }
    catch (\Exception $e) {
      // Cannot find form.
      return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
    }

    if (!$settings->isApplicationOpen()) {
      return new JsonResponse(['error' => $this->t('The application is not currently open')], 400);
      // @todo Uncomment.
      // return new JsonResponse([], 403);
    }

    try {
      $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
      $selected_company = $this->userInformationService->getSelectedCompany();
      $user_information = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      // Unable to fetch user information.
      return new JsonResponse(['error' => $this->t('Unable to fetch your user information. Please try again in a moment')], 500);
    }

    try {
      // Make sure it exists in database.
      $submission = $this->getSubmissionEntity($user_information['sub'], $application_number, $grants_profile_data->getBusinessId());
    }
    catch (\Exception $e) {
      // Cannot get the submission.
      return new JsonResponse(['error' => $this->t('We cannot find the application you are trying to open. Please try creating another one')], 500);
    }

    try {
      $document = $this->atvService->getDocument($application_number);
      if ($sideDocumentId = $submission->get('side_document_id')->value) {
        $sideDocument = $this->atvService->getDocumentById($sideDocumentId);
      } else {
        // Side document bc.
        try {
          $document_content = $document->getContent();
          $form_data = isset($document_content['form_data']) ?
            $document_content['form_data'] : (isset($document_content['compensation']['form_data']) ?
            $document_content['compensation']['form_data'] : []);

          $sideDocument = $this->atvService->createSideDocument(
            'application_type',
            $settings->getApplicationName(),
            $user_information['sub'],
            $selected_company,
            $document->getId(),
            $selected_company['type']
          );

          $sideDocument->setContent($form_data);
          $sideDocument = $this->atvService->saveNewDocument($sideDocument);

          $submission->set('side_document_id', $sideDocument->getId());
          $submission->save();
        }
        catch (\Exception $e) {
          $this->logger->critical("Side document creation failed: $application_number");
          return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
        }

      }

      $content = $document->getContent();

      foreach (['events', 'messages', 'attachmentsInfo', 'statusUpdates'] as $item) {
        if (isset($content[$item])) {
          $form_data[$item] = $content[$item];
        }
      }
    }
    catch (\Throwable $e) {
      // @todo helfi_atv -module throws multiple exceptions, handle them accordingly.
      return new JsonResponse(['error' => $this->t('Unable to fetch your application. Please try again in a moment')], 500);
    }

    $changeTime = new DrupalDateTime($document->getUpdatedAt());

    $handlers = $this->avus2DataParser->getHandlers($document);
    $submitted = $this->avus2DataParser->getSubmitted($document);
    $attachment_data = $this->avus2DataParser->getSubmittedAttachments($document);
    $history = $this->avus2DataParser->getHistory($document);

    // @todo Only return required user data to frontend.
    $response = [
      'form_data' => $sideDocument->getContent(),
      'summary_data' => [
        'application_number' => $application_number,
        'application_submitted' => $submitted,
        'handlers' => $handlers,
        'status_updates' => $history,
        'attachments' => $attachment_data,
      ],
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
    string $form_identifier,
    ?string $application_number = NULL,
  ): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    [
      'form_data' => $form_data,
      'attachments' => $attachments,
    ] = $content;

    // phpcs:enable
    try {
      $settings = $this->formSettingsService->getFormSettingsByFormIdentifier($form_identifier);
    }
    catch (\Exception $e) {
      $this->logger->info("User failed to open application due to missing form settings, form identifier: $form_identifier");
      return new JsonResponse(['error' => $this->t('Something went wrong')], 404);
    }

    if (!$application_number) {
      $this->logger->critical('POST-request without application number, form identifier: ' . $form_identifier);
      return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
    }

    $errors = $this->validate($form_identifier, $form_data);
    if (is_array($errors)) {
      // Encode changes (empty)array into object in some cases
      $this->logger->alert("User encountered validation error on application $form_identifier: " . json_encode($errors));
    }

    try {
      $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
      $selected_company = $this->userInformationService->getSelectedCompany();
      $user_data = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      $this->logger->error("User failed to fetch the user information during POST-request: {$e->getMessage()}");
      return new JsonResponse(['error' => $this->t('Unable to fetch your user information. Please try again in a moment')], 500);
    }

    try {
      $submission = $this->getSubmissionEntity(
        $this->userInformationService->getUserData()['sub'],
        $application_number,
        $grants_profile_data->getBusinessId(),
      );
    }
    catch (\Exception $e) {
      $this->logger->error("During POST-request, failed to query submission
        entity from database, $application_number: {$e->getMessage()}");
      return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
    }

    try {
      $document = $this->atvService->getDocument($application_number);
      $sideDocument = $this->atvService->getDocumentById($submission->get('side_document_id')->value);
    }
    catch (\Throwable $e) {
      $this->logger->error("During POST-request, failed to fetch ATV-document, $application_number: {$e->getMessage()}");
      return new JsonResponse(['error' => $this->t('We cannot fetch the application. Please try again in a moment')], 500);
    }

    // Here we do the actual work.
    // - Handle bank account file upload / other bank account shenanigans.
    //   - The bank account file handling causes extra document load and save.
    // - Map the React-form data to Avus2-format.
    // - Update the ATV document one last time before sending to integration.
    //   - The atv document must be updated as 'SUBMITTED' at this point.
    // - Send to integration.
    //   - Set 'X-Case-Status'-headed to 'SUBMITTED'.
    //   - After this point we are no longer allowed to touch the status.
    // - Update the custom submission entity.
    try {
      $bankFile = $this->jsonMapperService->getSelectedBankFile($form_data);
    }
    catch (\Exception) {
      return new JsonResponse(
        ['error' => $this->t('Your user profile does not contain the given bank account number. Please update your user profile and try again')],
        500
      );
    }

    $uploadedBankFile = FALSE;

    if (!$this->jsonMapperService->documentBankFileIsSet($document)) {
      // Bank file has not yet been added to the ATV-document.
      try {
        $actualFile = $this->atvService->getAttachment($bankFile['href']);
      }
      catch (\Exception $e) {
        $this->logger->error("Unable to get bank file: {$e->getMessage()}");
        return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
      }

      if (!is_bool($actualFile)) {
        $this->atvService->addAttachment(
          $document->getId(),
          $bankFile['filename'],
          $actualFile,
        );
        $actualFile->delete();
        // We don't add the event here since we need to load it once more.
        $uploadedBankFile = TRUE;
      }

      // Reload the document.
      try {
        $document = $this->atvService->getDocument($application_number);
        $bankFileIsSet = $this->jsonMapperService->documentBankFileIsSet($document);
      }
      catch(\throwable) {
        // Just to be safe.
        return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
      }

      // Validate that upload was success.
      if (!$bankFileIsSet) {
        // This should never happen.
        $this->logger->error('User is unable to upload bank file to document or race condition.');
      }
    }

    try {
      $mappedData = $this->jsonMapperService->handleMapping(
        $settings->getFormId(),
        $submission->get('form_identifier')->value,
        $application_number,
        $form_data,
        $bankFile,
        (bool) $submission->get('draft')->value,
        $selected_company['type'],
      );
    }
    catch (\Exception $e) {
      $this->logger->critical("Failed mapping, application: $form_identifier");
      return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
    }



    // Save id has previously been saved to database to track
    // unsuccessful submissions due to integration failures.
    // @todo Use drupal uuid service maybe ?
    $save_id = Uuid::uuid4()->toString();

    // We add the events manually instead of requesting integration.
    // Requesting integration could possibly cause a race condition.
    if ($uploadedBankFile) {
      $bankAccountNumber = $this->jsonMapperService->getSelectedBankAccount($form_data);
      $event = $this->eventsService->getEventData(
        'HANDLER_ATT_OK',
        $application_number,
        "Attachment uploaded for the IBAN: $bankAccountNumber.",
        $save_id
      );
      $this->eventsService->addNewEventForApplication($document, $event);
    }

    if ($document->getContent()['events']) {
      $mappedData['events'] = $document->getContent()['events'];
    }

    $sideDocument->setContent($form_data);
    $sideDocument->setDeleteAfter((new \DateTimeImmutable('+6 years'))->format('Y-m-d'));
    $this->atvService->updateExistingDocument($sideDocument);

    // On first AVUS2-submit formUpdate = FALSE and afterward only TRUE.
    // check GrantsHandler::getFormUpdate for more detailed explanation.
    // In case we are resending a bad initial submission,
    // we must set the formUpdate = false.
    $integrationError = FALSE;
    foreach ($document->getContent()['events'] as $event) {
      if (isset($event['eventType']) && $event['eventType'] === 'INTEGRATION_ERROR_AVUS2' && $document->getStatus() === 'SUBMITTED') {
        $integrationError = TRUE;
        $mappedData['formUpdate'] = FALSE;
        break;
      }
    }
    $document->setContent($mappedData);
    // @codingStandardsIgnoreEnd

    // Set the submitted -status right before sending to Avus2.
    // The status is set as request header by integration-service.
    $document->setStatus('SUBMITTED');
    $document->setDeleteAfter((new \DateTimeImmutable('+6 years'))->format('Y-m-d'));
    $latestDocument = $this->atvService->updateExistingDocument($document);

    // Add the submit event to the events.
    $event = $this->eventsService->getEventData(
      'HANDLER_SEND_INTEGRATION',
      $application_number,
      'Send application to integration.',
      $save_id
    );
    $this->eventsService->addNewEventForApplication($latestDocument, $event);

    $success = FALSE;
    try {
      $success = $this->integration->sendToAvus2($latestDocument, $application_number, $save_id, $integrationError);
    }
    catch (\Exception $e) {
      $this->logger->error('Avus2 -POST-request failed: ' . $e->getMessage());
      return new JsonResponse(['error' => $this->t('An error occurred while sending the application. Please try again in a moment')], 500);
    }

    if (!$success) {
      $this->logger->error('Avus2 -POST-request returned non-200 response');
      return new JsonResponse(['error' => $this->t('An error occurred while sending the application. Please try again in a moment')], 500);
    }

    try {
      $submission->setChangedTime(time());
      $submission->set('draft', FALSE);
      $submission->save();
    }
    catch (\Exception $e) {
      // This should never happen.
      return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
    }

    // @todo Move ApplicationSubmitEvent and ApplicationSubmitType to
    // grants_application module when this module is enabled in
    // production.
    //
    // This event lets other parts of the system to react
    // to user submitting grants forms.
    $this->dispatcher->dispatch(new ApplicationSubmitEvent(ApplicationSubmitType::SUBMIT));

    if ($this->contentLock->isLockable($submission)) {
      $this->contentLock->release(
        $submission,
        '*',
        $this->accountProxy->id()
      );
    }

    return $this->getSuccessResponse($application_number);
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
    string $form_identifier,
    ?string $application_number = NULL,
  ): JsonResponse {
    $content = json_decode($request->getContent(), TRUE);
    [
      'form_data' => $form_data,
      'attachments' => $attachments,
    ] = $content;

    try {
      $settings = $this->formSettingsService->getFormSettingsByFormIdentifier($form_identifier);
    }
    catch (\Exception $e) {
      $this->logger->info("User failed to open application due to missing form settings: $form_identifier");
      return new JsonResponse(['error' => $this->t('Something went wrong')], 404);
    }

    if (!$application_number) {
      $this->logger->critical("PATCH-request without application number for $form_identifier");
      return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
    }


    $errors = $this->validate($form_identifier, $form_data);
    if (is_array($errors)) {
      // Encode changes (empty)array into object in some cases
      $this->logger->alert("User encountered validation error on application $form_identifier: " . json_encode($errors));
    }

    try {
      $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
      $selected_company = $this->userInformationService->getSelectedCompany();
      $user_data = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      $this->logger->error("User failed to fetch the user information during POST-request: {$e->getMessage()}");
      return new JsonResponse(['error' => $this->t('Unable to fetch your user information. Please try again in a moment')], 500);
    }

    try {
      $submission = $this->getSubmissionEntity(
        $this->userInformationService->getUserData()['sub'],
        $application_number,
        $grants_profile_data->getBusinessId(),
      );
    }
    catch (\Exception $e) {
      $this->logger->error("During PATCH-request, failed to query submission
        entity from database, $application_number: {$e->getMessage()}");
      return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
    }

    try {
      $document = $this->atvService->getDocument($application_number);
      $sideDocument = $this->atvService->getDocumentById($submission->getSideDocumentId());
    }
    catch (\Throwable $e) {
      $this->logger->error("During PATCH-request, failed to fetch ATV-document, $application_number: {$e->getMessage()}");
      return new JsonResponse(['error' => $this->t('Unable to fetch the application. Please try again in a moment')], 500);
    }

    try {
      $oldDocument = $document->toArray();
      $mappedData = $this->jsonMapperService->handleMappingForPatchRequest(
        $settings->getFormId(),
        $submission->get('form_identifier')->value,
        $application_number,
        $form_data,
        $selected_company['type'],
        $oldDocument
      );
    }
    catch (\Exception $e) {
      // Unable to combine datasources, bad atv-connection maybe?
      $this->logger->critical('Error during PATCH-request, unable to combine datasources: ' . $e->getMessage());
      return new JsonResponse(
        ['error' => $this->t('An error occurred while sending the application. Please try again later')],
        500,
      );
    }

    $document->setContent($mappedData);
    $document->setDeleteAfter((new \DateTimeImmutable('+6 years'))->format('Y-m-d'));

    $save_id = Uuid::uuid4()->toString();
    try {
      // We don't use the event api to apply this particular event.
      // instead we just put the event into the document.
      $event = $this->eventsService->getEventData(
        'HANDLER_SEND_INTEGRATION',
        $application_number,
        'send application to integration.',
        $save_id
      );
      $this->eventsService->addNewEventForApplication($document, $event);
    }
    catch (\Exception $e) {
      return new JsonResponse(['error' => $this->t('An error occurred while sending the application. Please try again in a moment')], 500);
    }

    try {
      $sideDocument->setContent($form_data);
      $sideDocument->setDeleteAfter((new \DateTimeImmutable('+6 years'))->format('Y-m-d'));
      $this->atvService->updateExistingDocument($sideDocument);
      $latestDocument = $this->atvService->updateExistingDocument($document);
    }
    catch (\Exception $e) {
      // Unable to find the document.
      return new JsonResponse(['error' => $this->t('An error occurred while sending the application. Please try again in a moment')], 500);
    }

    $success = FALSE;
    try {
      $success = $this->integration->sendToAvus2($latestDocument, $application_number, $save_id, FALSE);
    }
    catch (\Exception $e) {
      $this->logger->error('Avus2 -PATCH-request failed: ' . $e->getMessage());
      return new JsonResponse(['error' => $this->t('An error occurred while sending the application. Please try again in a moment')], 500);
    }

    if (!$success) {
      $this->logger->error('Avus2 -PATCH-request returned non-200 response');
      return new JsonResponse(['error' => $this->t('An error occurred while sending the application. Please try again in a moment')], 500);
    }

    $submission->setChangedTime(time());
    $submission->save();

    // @todo Move ApplicationSubmitEvent and ApplicationSubmitType to
    // grants_application module when this module is enabled in
    // production.
    //
    // This event lets other parts of the system to react
    // to user submitting grants forms.
    $this->dispatcher->dispatch(new ApplicationSubmitEvent(ApplicationSubmitType::SUBMIT));

    if ($this->contentLock->isLockable($submission)) {
      $this->contentLock->release(
        $submission,
        '*',
        $this->accountProxy->id()
      );
    }

    return $this->getSuccessResponse($application_number);
  }
  // phpcs:enabled

  private function getSuccessResponse(string $applicationNumber): JsonResponse {
    return new JsonResponse([
      'redirect_url' => Url::fromRoute(
        'helfi_grants.completion',
        ['application_number' => $applicationNumber],
        ['absolute' => TRUE],
      )->toString(),
    ], 200);
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
   * @param string $formIdentifier
   *   The form identifier.
   * @param array $formData
   *   The form data.
   *
   * @return bool|array
   *   Is valid or array of errors.
   */
  private function validate(string $formIdentifier, array $formData): bool|array {
    $settings = $this->formSettingsService->getFormSettingsByFormIdentifier($formIdentifier);
    $results = $this->jsonSchemaValidator->validate(json_decode(json_encode($formData)), json_decode(json_encode($settings->getSchema())));

    if (is_array($results)) {
      $errors = [];
      foreach ($results as $error) {
        $errors[] = $error['message'];
      }
      $results = $errors;
    }

    return $results;
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
