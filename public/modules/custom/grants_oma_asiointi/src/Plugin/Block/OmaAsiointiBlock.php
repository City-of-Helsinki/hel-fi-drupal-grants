<?php

namespace Drupal\grants_oma_asiointi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\grants_handler\ApplicationGetterService;
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
   * Construct block object.
   *
   * @param array $configuration
   *   Block config.
   * @param string $plugin_id
   *   Plugin.
   * @param mixed $plugin_definition
   *   Plugin def.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata
   *   The helfi_helsinki_profiili service.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   The grants profile service.
   * @param \Drupal\helfi_atv\AtvService $helfiAtvAtvService
   *   The ATV service.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   Current request object.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   Current user.
   * @param \Drupal\grants_handler\MessageService $messageService
   *   Message service.
   * @param \Drupal\grants_handler\ApplicationGetterService $applicationGetterService
   *   Application getters.
   * @param \Drupal\Core\Language\LanguageManagerInterface $languageManager
   *   Language manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata,
    protected GrantsProfileService $grantsProfileService,
    protected AtvService $helfiAtvAtvService,
    protected Request $request,
    protected AccountInterface $currentUser,
    protected MessageService $messageService,
    protected ApplicationGetterService $applicationGetterService,
    protected LanguageManagerInterface $languageManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

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
    $plugin_definition,
  ): static {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('helfi_helsinki_profiili.userdata'),
      $container->get('grants_profile.service'),
      $container->get('helfi_atv.atv_service'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('current_user'),
      $container->get('grants_handler.message_service'),
      $container->get('grants_handler.application_getter_service'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function build(): array {

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
