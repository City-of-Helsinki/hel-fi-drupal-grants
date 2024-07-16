<?php

namespace Drupal\grants_oma_asiointi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationHelpers;
use Drupal\grants_handler\Helpers;
use Drupal\grants_handler\MessageService;
use Drupal\grants_metadata\AtvSchema;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides an example block.
 *
 * @Block(
 *   id = "grants_oma_asiointi_block",
 *   admin_label = @Translation("Grants Oma Asiointi"),
 *   category = @Translation("Oma Asiointi")
 * )
 *
 * @phpstan-consistent-constructor
 */
class OmaAsiointiBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The helfi_helsinki_profiili.userdata service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata;

  /**
   * The grants_profile.service service.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * The helfi_atv.atv_service service.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $helfiAtvAtvService;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $request;

  /**
   * The current language service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The grants_handler.message_service service.
   *
   * @var \Drupal\grants_handler\MessageService
   */
  protected MessageService $messageService;

  /**
   * The application getter service.
   *
   * @var \Drupal\grants_handler\ApplicationGetterService
   */
  protected ApplicationGetterService $applicationGetterService;

  /**
   * Construct block object.
   *
   * @param array $configuration
   *   Block config.
   * @param string $plugin_id
   *   Plugin.
   * @param mixed $plugin_definition
   *   Plugin def.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData
   *   Helsinki profile user data.
   * @param \Drupal\grants_profile\GrantsProfileService $grants_profile_service
   *   The grants_profile.service service.
   * @param \Drupal\helfi_atv\AtvService $helfi_atv_atv_service
   *   The helfi_atv.atv_service service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request object.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user.
   * @param \Drupal\grants_handler\MessageService $messageService
   *   MEssage service.
   * @param \Drupal\grants_handler\ApplicationGetterService $applicationGetterService
   *   Application getters.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
    GrantsProfileService $grants_profile_service,
    AtvService $helfi_atv_atv_service,
    Request $request,
    LanguageManagerInterface $languageManager,
    AccountInterface $currentUser,
    MessageService $messageService,
    ApplicationGetterService $applicationGetterService
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->helfiHelsinkiProfiiliUserdata = $helsinkiProfiiliUserData;
    $this->grantsProfileService = $grants_profile_service;
    $this->helfiAtvAtvService = $helfi_atv_atv_service;
    $this->request = $request;
    $this->languageManager = $languageManager;
    $this->currentUser = $currentUser;
    $this->messageService = $messageService;
    $this->applicationGetterService = $applicationGetterService;
  }

  /**
   * Factory function.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container.
   * @param array $configuration
   *   Block config.
   * @param string $plugin_id
   *   Plugin.
   * @param mixed $plugin_definition
   *   Plugin def.
   *
   * @return static
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('helfi_helsinki_profiili.userdata'),
      $container->get('grants_profile.service'),
      $container->get('helfi_atv.atv_service'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('language_manager'),
      $container->get('current_user'),
      $container->get('grants_handler.message_service'),
      $container->get('grants_handler.application_getter_service')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function build() {

    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    $userData = $this->helfiHelsinkiProfiiliUserdata->getUserData();

    // If no company selected, no mandates no access.
    $roles = $this->currentUser->getRoles();
    if (
      in_array('helsinkiprofiili', $roles) &&
      $selectedCompany == NULL) {
      $build = [
        '#theme' => 'grants_oma_asiointi_block',
        '#hascompany' => FALSE,
      ];
      return $build;
    }

    $helsinkiProfileData = $this->helfiHelsinkiProfiiliUserdata->getUserProfileData();
    $appEnv = Helpers::getAppEnv();
    $lookForAppEnv = 'appenv:' . $appEnv;

    $messages = [];
    $submissions = [];

    try {

      if ($selectedCompany['type'] == 'private_person') {
        $searchParams = [
          'service' => 'AvustushakemusIntegraatio',
          'user_id' => $userData['sub'],
          'lookfor' => $lookForAppEnv . ',applicant_type:' . $selectedCompany['type'],
        ];
      }
      elseif ($selectedCompany['type'] == 'unregistered_community') {
        $searchParams = [
          'service' => 'AvustushakemusIntegraatio',
          'user_id' => $userData['sub'],
          'lookfor' => $lookForAppEnv . ',applicant_type:' . $selectedCompany['type'] .
          ',applicant_id:' . $selectedCompany['identifier'],
        ];
      }
      else {
        $searchParams = [
          'service' => 'AvustushakemusIntegraatio',
          'business_id' => $selectedCompany['identifier'],
          'lookfor' => $lookForAppEnv,
        ];
      }

      $applicationDocuments = $this->helfiAtvAtvService->searchDocuments($searchParams);

      /**
       * Create rows for table.
       *
       * @var \Drupal\helfi_atv\AtvDocument $document
       */
      foreach ($applicationDocuments as $document) {
        if (array_key_exists(
          $document->getType(),
          Helpers::getApplicationTypes())
        ) {

          try {

            $docArray = $document->toArray();
            $id = AtvSchema::extractDataForWebForm(
              $docArray['content'], ['applicationNumber']
            );

            if (!isset($id['applicationNumber']) || empty($id['applicationNumber'])) {
              continue;
            }

            $submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($document->getTransactionId(), $document);
            $submissionData = $submission->getData();
            $submissionMessages = $this->messageService->parseMessages($submissionData, TRUE);
            $messages += $submissionMessages;

            if ($submissionData['form_timestamp']) {
              $ts = strtotime($submissionData['form_timestamp']);
              $submissions[$ts] = $submissionData;
            }
          }
          catch (AtvDocumentNotFoundException $e) {
          }
        }
      }

    }
    catch (\Throwable $e) {
    }

    $receivedMsgs = [];

    // Show only messages that are received from kasittelyjarjestelma.
    foreach ($messages as $message) {
      if ($message['sentBy'] === 'Avustusten kasittelyjarjestelma') {
        array_push($receivedMsgs, $message);
      }
    }

    $lang = $this->languageManager->getCurrentLanguage();
    krsort($submissions);
    krsort($messages);
    $link = Link::createFromRoute(
      $this->t('Go to My Services', [], ['context' => 'grants_oma_asiointi']), 'grants_oma_asiointi.front'
    );
    $allMessagesLink = Link::createFromRoute(
      $this->t('See all messages', [], ['context' => 'grants_oma_asiointi']), 'grants_oma_asiointi.front'
    );
    $build = [
      '#theme' => 'grants_oma_asiointi_block',
      '#allMessages' => $receivedMsgs,
      '#messages' => array_slice($receivedMsgs, 0, 2),
      '#allSubmissions' => $submissions,
      '#submissions' => array_slice($submissions, 0, 2),
      '#userProfileData' => $helsinkiProfileData['myProfile'],
      '#applicationTypes' => Helpers::getApplicationTypes(),
      '#lang' => $lang->getId(),
      '#link' => $link,
      '#allMessagesLink' => $allMessagesLink,
    ];

    return $build;
  }

  /**
   * Disable cache.
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
