<?php

namespace Drupal\grants_application\Plugin\rest\resource;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\grants_application\Atv\HelfiAtvService;
use Drupal\grants_application\Form\ApplicationNumberService;
use Drupal\grants_application\Form\FormSettingsService;
use Drupal\grants_application\Helper;
use Drupal\grants_application\User\UserInformationService;
use Drupal\rest\Attribute\RestResource;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\RouteCollection;

/**
 *
 */
#[RestResource(
  id: "application_rest_resource",
  label: new TranslatableMarkup("Application"),
  uri_paths: [
    "canonical" => "/application/{application_type_id}/{application_number}",
    "create" => "/application/{application_type_id}",
    "edit" => "/applicatoin/{application_type_id}",
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
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get("logger.channel.grants_application"),
      $container->get('Drupal\grants_application\Form\FormSettingsService'),
      $container->get('Drupal\grants_application\User\UserInformationService'),
      $container->get('Drupal\grants_application\Atv\HelfiAtvService'),
      $container->get('uuid'),
      $container->get('Drupal\grants_application\Form\ApplicationNumberService'),
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param int $application_type_id
   *   The application type id.
   * @param string|null $application_number
   *   The unique identifier for the application.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   The response containing the entity with its accessible fields.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get(int $application_type_id, ?string $application_number = NULL) {
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
      ...$settings->toArray(),
    ];

    // Allow previewing for anonymous user.
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

    // @todo Access check.
    $form_data = [];
    if ($application_number) {
      try {
        $document = $this->atvService->getDocument($application_number);
        $form_data = $document->getContent() ?? [];
      }
      catch (\Throwable $e) {
        // @todo helfi_atv -module throws multiple exceptions, handle them accordingly.
      }
    }

    $response['form_data'] = $form_data;

    return new JsonResponse($response);
  }

  /**
   * Post request.
   */
  public function post() {
    $request = \Drupal::request();
    $content = json_decode($request->getContent(), TRUE);
    $env = Helper::getAppEnv();

    [
      'application_type_id' => $application_type_id,
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
      // Return new JsonResponse([], 403);.
    }

    // @todo Validate form data against schema maybe.
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
    $langcode = $langcode;
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

    $document->setContent($form_data);

    try {
      $this->atvService->saveNewDocument($document);
    }
    catch (\Exception $e) {
      // Saving failed.
      return new JsonResponse([], 500);
    }

    return new JsonResponse($document->toArray(), 200);
  }

  /**
   * Responds to entity PATCH requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $original_entity
   *   The original entity object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function patch() {
    $request = \Drupal::request();
    $content = json_decode($request->getContent(), TRUE);
    // @todo Access checking.
    [
      'application_number' => $application_number,
      'form_data' => $form_data,
    ] = $content;

    if (!$application_number) {
      // Missing application number.
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
      $document->setContent($form_data);
      $this->atvService->updateExistingDocument($document);
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

}
