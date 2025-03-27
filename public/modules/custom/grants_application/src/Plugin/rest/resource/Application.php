<?php

namespace Drupal\grants_application\Plugin\rest\resource;

use Drupal\Component\Utility\Xss;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Entity\ApplicationSubmission;
use Drupal\grants_application\Form\ApplicationNumberService;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\Helper;
use Drupal\grants_application\User\UserInformationService;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * The application rest resources.
 */
#[RestResource(
  id: "application_rest_resource",
  label: new TranslatableMarkup("Application"),
  uri_paths: [
    "canonical" => "/application/{application_type_id}/{application_number}",
    "create" => "/application/{application_type_id}",
    "edit" => "/application/{application_type_id}",
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
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * If no application number, it's either preview or new submission.
   * If nothing in database, it's new submission.
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
    ?string $application_number = NULL,
  ): JsonResponse {
    // @todo Sanitize & validate & authorize properly.
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

    $response = [
      'form_data' => [],
      'grants_profile' => [],
      'user_data' => [],
      'token' => $this->csrfTokenGenerator->get('rest'),
      ...$settings->toArray(),
    ];

    // Allow previewing for anonymous user.
    // @phpstan-ignore-next-line
    if (\Drupal::currentUser()->isAnonymous()) {
      return new JsonResponse($response);
    }

    try {
      $grants_profile_data = $this->userInformationService->getGrantsProfileContent();
      $user_information = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      // Unable to fetch user information.
      return new JsonResponse([], 500);
    }

    $response['grants_profile'] = $grants_profile_data;
    $response['user_data'] = $user_information;

    // New form with only user data.
    if (!$application_number) {
      return new JsonResponse($response);
    }

    try {
      $submission = $this->getSubmissionEntity($user_information['sub'], $application_number);
    }
    catch (\Exception $e) {
      // Error.
      // @todo 403 ord 500 etc.
      return new JsonResponse([], 500);
    }

    $response['grants_profile'] = $grants_profile_data;
    $response['user_data'] = $user_information;

    if (!$submission) {
      $response['form_data'] = [];
      return new JsonResponse($response);
    }

    $form_data = [];

    try {
      $document = $this->atvService->getDocument($application_number);
      $form_data = $document->getContent();
    }
    catch (\Throwable $e) {
      // @todo helfi_atv -module throws multiple exceptions, handle them accordingly.
    }

    $response['form_data'] = $form_data;

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
    Request $request,
  ): JsonResponse {
    // @todo Sanitize & validate & authorize properly.
    $content = json_decode($request->getContent(), TRUE);
    $env = Helper::getAppEnv();

    [
      'application_number' => $application_number,
      'langcode' => $langcode,
      'form_data' => $form_data,
    ] = $content;

    try {
      $settings = $this->formSettingsService->getFormSettings($application_type_id);
    }
    catch (\Exception $e) {
      // Cannot find form.
      return new JsonResponse([], 500);
    }

    if (!$settings->isApplicationOpen()) {
      // @todo Uncomment.
      // Return new JsonResponse([], 403);.
    }

    $application_uuid = $this->uuid->generate();

    // @todo Application number generation must match the existing shenanigans.
    $application_number = $this->applicationNumberService
      ->createNewApplicationNumber($env, $application_type_id);

    try {
      $selected_company = $this->userInformationService->getSelectedCompany();
      // Helsinkiprofiiliuserdata getuserdata.
      $user_data = $this->userInformationService->getUserData();
    }
    catch (\Exception $e) {
      return new JsonResponse([], 500);
    }

    $application_name = $settings->toArray()['settings']['title'];
    $application_title = $settings->toArray()['settings']['title'];
    $application_type = $settings->toArray()['settings']['application_type'];
    $langcode = $this->languageManager->getCurrentLanguage()->getId();
    $sub = $user_data['sub'];

    $document = $this->atvService->createAtvDocument(
      $application_uuid,
      $application_number,
      $application_name,
      $application_type,
      $application_title,
      $langcode,
      $sub,
      $selected_company['identifier'],
      FALSE,
      $selected_company,
      $this->userInformationService->getApplicantType(),
    );

    // @todo Better sanitation.
    $document->setContent(json_decode(Xss::filter(json_encode($form_data ?? [])), TRUE));

    try {
      $this->atvService->saveNewDocument($document);
      $now = time();
      ApplicationSubmission::create([
        // 'uuid' => $this->uuid->generate(),
        'sub' => $sub,
        'langcode' => $langcode,
        'draft' => TRUE,
        'application_type_id' => $application_type_id,
        'application_number' => $application_number,
        'created' => $now,
        'changed' => $now,
      ])
        ->save();
    }
    catch (\Exception | GuzzleException $e) {
      // Saving failed.
      return new JsonResponse([], 500);
    }

    return new JsonResponse($document->toArray(), 200);
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
    ] = $content;

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
    catch(\Exception $e) {
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
      $document->setContent(json_decode(Xss::filter(json_encode($form_data)), TRUE));
      $this->atvService->updateExistingDocument($document);

      $submission->setChangedTime(time());
      $submission->save();
    }
    catch (\Exception $e) {
      // Unable to find the document.
      return new JsonResponse([], 500);
    }

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
   * @return ApplicationSubmission|bool
   *   The application submission entity.
   */
  private function getSubmissionEntity(string $sub, string $application_number): ApplicationSubmission {
    $ids = \Drupal::entityQuery('application_submission')
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
