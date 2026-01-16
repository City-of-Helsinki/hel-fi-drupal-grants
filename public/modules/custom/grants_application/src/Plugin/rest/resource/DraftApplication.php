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
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\Form\FormValidator;
use Drupal\grants_application\Helper;
use Drupal\grants_application\User\UserInformationService;
use Drupal\grants_handler\ApplicationSubmitType;
use Drupal\grants_handler\Event\ApplicationSubmitEvent;
use Drupal\helfi_atv\AtvDocument;
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
  label: new TranslatableMarkup("Draft application resource"),
  uri_paths: [
    "canonical" => "/applications/{application_type_id}/{application_number}",
    "create" => "/applications/{application_type_id}/{application_number}/{copy_from?}",
    "edit" => "/applications/{application_type_id}/{application_number}",
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
    private FormSettingsService $formSettingsService,
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
      $container->get(FormSettingsService::class),
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
      $container->get(ApplicationService::class)
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
   * This is called as long as the application is a draft.
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
      // return new JsonResponse(['error' => $this->t('The application is not currently open.')], 403);
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
      $entity = $this->getSubmissionEntity($user_information['sub'], $application_number, $grants_profile_data->getBusinessId());
    }
    catch (\Exception $e) {
      // Cannot get the submission.
      return new JsonResponse([], 500);
    }

    try {
      $document = $this->atvService->getDocument($application_number);
      $sideDocument = $this->atvService->getDocumentById($entity->getSideDocumentId());
    }
    catch (\Throwable $e) {
      // @todo helfi_atv -module throws multiple exceptions, handle them accordingly.
      return new JsonResponse([], 500);
    }

    $response = [];

    // @todo Backward compatibility?
    $response['form_data'] = $sideDocument->getContent()['form_data'];

    // @todo Only return required user data to frontend
    $response['grants_profile'] = $grants_profile_data->toArray();
    $response['user_data'] = $user_information;
    $response['status'] = $document->getStatus();
    $response['token'] = $this->csrfTokenGenerator->get('rest');
    $response['last_changed'] = $entity->get('changed')->value;
    $response = array_merge($response, $settings->toArray());

    return new JsonResponse($response);
  }

  /**
   * Create the initial document.
   *
   * This is only called when a react-form is opened for the first time.
   * After that the patch-function takes care of submitting the form as draft.
   * Avus2-submit is handled in Application-resource.
   *
   * @param int $application_type_id
   *   The application type id.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The response.
   */
  public function post(int $application_type_id): JsonResponse {
    try {
      /** @var AtvDocument $atvDocument */
      $atvDocument = $this->applicationService->createDraft($application_type_id);
    }
    catch (\Exception $e) {
      return new JsonResponse([], 500);
    }

    // @todo Check lock logic.
    // $this->contentLock->locking($submission, '*', $this->accountProxy->id());
    return new JsonResponse([
      'application_number' => $atvDocument->getMetadata()['applicationnumber'],
      'document_id' => $atvDocument->getId(),
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
    $content = json_decode($request->getContent(), TRUE);
    [
      'attachments' => $attachments,
      'form_data' => $form_data,
    ] = $content;

    try {
      $this->formSettingsService->getFormSettings($application_type_id);
    }
    catch (\Exception $e) {
      // Cannot find form by application type id.
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
      $entity = $this->getSubmissionEntity(
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
      $sideDocument = $this->atvService->getDocumentById($entity->getSideDocumentId());
    }
    catch (\Throwable $e) {
      // Error while fetching the document.
      return new JsonResponse(['error' => $this->t('Unable to fetch your application. Please try again in a moment')], 500);
    }

    // @todo Backward compatibility?
    // On testing environment, old applications won't have the side document.
    if (!$sideDocument) {
      // Unable to find the document.
      return new JsonResponse(['error' => $this->t('We cannot find the application you are trying to open. Please try creating a new application')], 500);
    }
    $sideDocument->setContent(['form_data' => $form_data]);

    try {
      // The attachments are always set to the original document
      // This function actually calls the remove file -endpoint.
      $this->cleanUpAttachments($document, $attachments);
    }
    catch (\Exception $e) {
      // Failing to remove file should not matter, most likely not found.
    }

    try {
      $this->atvService->updateExistingDocument($sideDocument);
    }
    catch (\Exception $e) {
      return new JsonResponse([['error' => $this->t('Unable to save the draft. Please try again in a moment')]], 500);
    }
    $entity->setChangedTime(time());
    $entity->save();

    $this->showSavedMessage($application_number);

    // @todo Move ApplicationSubmitEvent and ApplicationSubmitType to
    // grants_application module when this module is enabled in
    // production.
    //
    // This event lets other parts of the system to react
    // to user submitting grants forms.
    $this->dispatcher->dispatch(new ApplicationSubmitEvent(ApplicationSubmitType::SUBMIT_DRAFT));

    if ($this->contentLock->isLockable($entity)) {
      $this->contentLock->release($entity, $application_number, $this->accountProxy->id());
    }

    return new JsonResponse($sideDocument->toArray(), 200);
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
   * @param AtvDocument $document
   *   The ATV document.
   * @param array $attachments
   *   The attachments.
   */
  private function cleanUpAttachments(AtvDocument $document, array $attachments = []): void {
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

    // Check for business id as well.
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
