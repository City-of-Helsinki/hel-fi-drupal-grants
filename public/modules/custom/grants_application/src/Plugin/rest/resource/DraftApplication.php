<?php
// phpcs:ignoreFile
namespace Drupal\grants_application\Plugin\rest\resource;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\content_lock\ContentLock\ContentLock;
use Drupal\content_lock\ContentLock\ContentLockInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\grants_application\ApplicationService;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\ApplicationNumberService;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_application\Form\FormValidator;
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
    "canonical" => "/applications/{form_identifier}/{application_number}",
    "create" => "/applications/{form_identifier}/{application_number}/{copy_from?}",
    "edit" => "/applications/{form_identifier}/{application_number}",
  ]
)]
final class DraftApplication extends ResourceBase {

  use StringTranslationTrait;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    private FormSettingsServiceInterface $formSettingsService,
    private UserInformationService $userInformationService,
    private HelfiAtvService $atvService,
    private UuidInterface $uuid,
    private ApplicationNumberService $applicationNumberService,
    private LanguageManagerInterface $languageManager,
    private EntityTypeManagerInterface $entityTypeManager,
    private CsrfTokenGenerator $csrfTokenGenerator,
    private EventDispatcherInterface $dispatcher,
    private FormValidator $formValidator,
    private ContentLockInterface $contentLock,
    private AccountProxyInterface $accountProxy,
    private ApplicationService $applicationService,
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
      $container->get(ApplicationNumberService::class),
      $container->get(LanguageManagerInterface::class),
      $container->get('entity_type.manager'),
      $container->get(CsrfTokenGenerator::class),
      $container->get(EventDispatcherInterface::class),
      $container->get(FormValidator::class),
      $container->get('content_lock'),
      $container->get('current_user'),
      $container->get(ApplicationService::class),
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
   * @param string $form_identifier
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
    string $form_identifier,
    string $application_number,
  ): RedirectResponse|JsonResponse {
    if (!$application_number) {
      return new JsonResponse([], 400);
    }

    try {
      $settings = $this->formSettingsService->getFormSettingsByFormIdentifier($form_identifier);
    }
    catch (\Exception $e) {
      // Cannot find form by application type id.
      return new JsonResponse([], 404);
    }

    if (!$settings->isApplicationOpen()) {
      return new JsonResponse(['error' => $this->t('The application is not currently open.')], 403);
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
      $submission = $this->applicationService->getSubmissionEntity($user_information['sub'], $application_number, $grants_profile_data->getBusinessId());
    }
    catch (\Exception $e) {
      // Cannot get the submission.
      return new JsonResponse([], 500);
    }

    try {
      $document = $this->atvService->getDocument($application_number);
      $document_content = $document->getContent();
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

    if (!$document_content['form_data'] && !$document_content['compensation']) {
      $response['form_data'] = [];
    }
    else {
      $response['form_data'] = $document_content['form_data'] ?? $document_content['compensation']['form_data'];
    }
    // @todo Only return required user data to frontend
    $response['grants_profile'] = $grants_profile_data->toArray();
    $response['user_data'] = $user_information;
    $response['status'] = $document->getStatus();
    $response['token'] = $this->csrfTokenGenerator->get('rest');
    $response['last_changed'] = $submission->get('changed')->value;
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
   * @param string $form_identifier
   *   The application type id.
   * @param string|null $copy_from
   *   The application number to copy from.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function post(string $form_identifier, string|null $copy_from = NULL): JsonResponse {
    try {
      $settings = $this->formSettingsService->getFormSettingsByFormIdentifier($form_identifier);
    }
    catch (\Exception $e) {
      // Cannot find form by application type id.
      return new JsonResponse([], 404);
    }
    $application_type_id = $settings->getFormId();

    $form_data = [];

    // If copying, new application is created and old data is added to it.
    if ($copy_from) {
      try {
        $copy_document = $this->atvService->getDocument($copy_from);
        $copy_content = $copy_document->getContent();
        $form_data = $copy_content['form_data'] ?? $copy_content['compensation']['form_data'];
      }
      catch (\Throwable $e) {
        // Unable to fetch the document to copy from.
        return new JsonResponse([], 500);
      }
    }

    try {
      $submission = $this->applicationService->createDraft(
        $application_type_id,
        $form_identifier,
        $settings,
        $form_data,
      );
    }
    catch (\Exception $e) {
      // Unable to create.
      return new JsonResponse([], 500);
    }

    $application_number = $submission->get('application_number')->value;
    $document_id = $submission->get('document_id')->value;
    if ($copy_from) {
      $result['redirect_url'] = Url::fromRoute(
        'helfi_grants.forms_app',
        ['id' => $application_type_id, 'application_number' => $submission->get('application_number')->value],
        ['absolute' => TRUE],
      )->toString();
    } else {
      $result = [
        'application_number' => $application_number,
        'document_id' => $document_id,
      ];
      $this->contentLock->locking($submission, '*', $this->accountProxy->id());
    }

    return new JsonResponse($result, 200);
  }

  /**
   * Responds to entity PATCH requests.
   *
   * Update existing draft submission.
   *
   * @param string $form_identifier
   *   The form identifier.
   * @param string $application_number
   *   The application number.
   * @param Request $request
   *   The request object.
   *
   * @return JsonResponse
   *   The HTTP response object.
   */
  public function patch(
    string $form_identifier,
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
      $this->formSettingsService->getFormSettingsByFormIdentifier($form_identifier);
    }
    catch (\Exception $e) {
      // Cannot find form settings.
      return new JsonResponse(['error' => $this->t('Something went wrong')], 404);
    }

    if (!$application_number) {
      // Missing application number.
      return new JsonResponse(['error' => $this->t('Something went wrong')], 500);
    }

    try {
      $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
    }
    catch (\Exception $e) {
      return new JsonResponse([], 500);
    }

    try {
      $submission = $this->applicationService->getSubmissionEntity(
        $this->userInformationService->getUserData()['sub'],
        $application_number,
        $grants_profile_data->getBusinessId(),
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
      return new JsonResponse(['error' => $this->t('Unable to fetch your application. Please try again in a moment')], 500);
    }

    if (!$document) {
      // Unable to find the document.
      return new JsonResponse(['error' => $this->t('We cannot find the application you are trying to open. Please try creating a new application')], 500);
    }

    $content = $document->getContent();
    $content['form_data'] = $form_data;
    $document->setContent($content);

    try {
      $this->cleanUpAttachments($document, $attachments);
    }
    catch (\Exception $e) {
      // @todo log error
    }

    try {
      // @todo Always get the events and messages from atv submission before overwriting.
      // ^This is not a problem here since we should not have any events at this point.
      // @todo Save the react form data in separate atv doc.
      $this->atvService->updateExistingDocument($document);

      $submission->setChangedTime(time());
      $submission->save();
    }
    catch (\Exception $e) {
      return new JsonResponse([['error' => $this->t('Unable to save the draft. Please try again in a moment')]], 500);
    }

    $this->showSavedMessage($application_number);

    // @todo Move ApplicationSubmitEvent and ApplicationSubmitType to
    // grants_application module when this module is enabled in
    // production.
    //
    // This event lets other parts of the system to react
    // to user submitting grants forms.
    $this->dispatcher->dispatch(new ApplicationSubmitEvent(ApplicationSubmitType::SUBMIT_DRAFT));

    if ($this->contentLock->isLockable($submission)) {
      $this->contentLock->release($submission, $application_number, $this->accountProxy->id());
    }

    return new JsonResponse($document->toArray(), 200);
  }

  /**
   * Shows a status message to the user when saving.
   *
   * @param string $application_number
   *   The application number.
   */
  private function showSavedMessage(string $application_number): void {
    $this->messenger()
      ->addStatus(
        $this->t(
          'Grant application (<span id="saved-application-number">@number</span>) saved as DRAFT',
          [
            '@number' => $application_number,
          ]
        )
      );
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

}
