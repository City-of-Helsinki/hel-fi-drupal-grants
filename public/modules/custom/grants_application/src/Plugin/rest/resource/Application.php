<?php

namespace Drupal\grants_application\Plugin\rest\resource;

use Drupal\Component\Utility\Xss;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Avus2Mapper;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\ApplicationNumberService;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\User\UserInformationService;
use Drupal\grants_handler\ApplicationSubmitType;
use Drupal\grants_handler\Event\ApplicationSubmitEvent;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
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
    "create" => "/applications/{application_type_id}/send/{application_number}",
    "edit" => "/applications/{application_type_id}/edit/{application_number}",
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
   * @param \Drupal\grants_application\Form\ApplicationNumberService $applicationNumberService
   *   The application number service.
   * @param \Drupal\Core\Access\CsrfTokenGenerator $csrfTokenGenerator
   *   The csrf token generator.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\grants_application\Avus2Mapper $avus2Mapper
   *   The Avus2-mapper.
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
    private CsrfTokenGenerator $csrfTokenGenerator,
    private LanguageManagerInterface $languageManager,
    private EntityTypeManagerInterface $entityTypeManager,
    private Avus2Mapper $avus2Mapper,
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
      $container->get(CsrfTokenGenerator::class),
      $container->get(LanguageManagerInterface::class),
      $container->get('entity_type.manager'),
      $container->get(Avus2Mapper::class),
      $container->get(EventDispatcherInterface::class),
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

    // @todo only return required user data to frontend.
    $response = [
      'form_data' => $form_data,
      'grants_profile' => $grants_profile_data->toArray(),
      'user_data' => $user_information,
      'token' => $this->csrfTokenGenerator->get('rest'),
      ...$settings->toArray(),
    ];

    return new JsonResponse($response);
  }

  /**
   * Post request.
   *
   * Create a new submission.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The json response.
   */
  public function post(
    int $application_type_id,
    string $application_number,
    Request $request,
  ): JsonResponse {
    // @todo Move ApplicationSubmitEvent and ApplicationSubmitType to
    // grants_application module when this module is enabled in
    // production.
    //
    // This event lets other parts of the system to react
    // to user submitting grants forms.
    $this->dispatcher->dispatch(new ApplicationSubmitEvent(ApplicationSubmitType::SUBMIT));

    // @todo Send to avus2.
    return new JsonResponse([], 200);
  }

  /**
   * Responds to entity PATCH requests.
   *
   * Update existing submission.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function patch(Request $request): JsonResponse {
    // @todo Sanitize & validate & authorize properly.
    $content = json_decode($request->getContent(), TRUE);
    [
      'application_number' => $application_number,
      'form_data' => $form_data,
      'draft' => $draft,
    ] = $content;

    $draft = $draft ?? FALSE;

    if (!$application_number) {
      // Missing application number.
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

    try {
      // @todo Better sanitation.
      $sanitized_data = json_decode(Xss::filter(json_encode($form_data ?? [])));
      $document_data = ['form_data' => $sanitized_data];

      // $atv_mapped_data = $this->atvMapper->mapData($sanitized_data);
      $document->setContent($document_data);
      // @todo Always get the events and messages from atv submission before overwriting.
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
