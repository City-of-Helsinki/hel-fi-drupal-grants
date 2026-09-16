<?php

declare(strict_types=1);

namespace Drupal\grants_profile\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\Helpers;
use Drupal\grants_handler\MessageService;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Provides an example block.
 *
 * @phpstan-consistent-constructor
 */
#[Block(
  id: 'grants_oma_asiointi_block',
  admin_label: new TranslatableMarkup('Grants Oma Asiointi'),
)]
class OmaAsiointiBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected HelsinkiProfiiliUserData $helfiHelsinkiProfiiliUserdata,
    protected GrantsProfileService $grantsProfileService,
    protected AtvService $helfiAtvAtvService,
    protected AccountInterface $currentUser,
    #[Autowire(service: 'grants_handler.message_service')]
    protected MessageService $messageService,
    protected ApplicationGetterService $applicationGetterService,
    protected LanguageManagerInterface $languageManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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

    if ($selectedCompany['type'] == 'private_person') {
      $searchParams = [
        'service' => 'AvustushakemusIntegraatio',
        'user_id' => $userData->sub,
        'lookfor' => $lookForAppEnv . ',applicant_type:' . $selectedCompany['type'],
      ];
    }
    elseif ($selectedCompany['type'] == 'unregistered_community') {
      $searchParams = [
        'service' => 'AvustushakemusIntegraatio',
        'user_id' => $userData->sub,
        'lookfor' => $lookForAppEnv . ',applicant_type:' . $selectedCompany['type'] . ',applicant_id:' . $selectedCompany['identifier'],
      ];
    }
    else {
      $searchParams = [
        'service' => 'AvustushakemusIntegraatio',
        'business_id' => $selectedCompany['identifier'],
        'lookfor' => $lookForAppEnv,
      ];
    }

    try {
      $applicationDocuments = $this->helfiAtvAtvService->searchDocuments($searchParams);

      /** @var \Drupal\helfi_atv\AtvDocument $document */
      foreach ($applicationDocuments as $document) {
        if (array_key_exists(
          $document->getType(),
          Helpers::getApplicationTypes())
        ) {
          try {
            $atvContent = $document->getContent();
            $applicationNumber = $document->getMetadata()['applicationnumber'];

            if (!$applicationNumber) {
              continue;
            }

            // Get react or webform.
            $submission = NULL;
            $submission = $this->applicationGetterService->getReactFormApplicationSubmission($applicationNumber, $document);
            if (!$submission) {
              $submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($applicationNumber, $document);
            }

            $submissionData = $submission->getData();
            $submissionMessages = $this->messageService->parseMessages($atvContent, TRUE);
            $messages += $submissionMessages;

            if ($submissionData['form_timestamp']) {
              $ts = strtotime($submissionData['form_timestamp']);
              $submissions[$ts] = $submissionData;
            }
          }
          catch (\Throwable) {
            // Catching only ATV-exception here exits the loop, not good.
            continue;
          }
        }
      }
    }
    catch (\Throwable) {
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
      '#cache' => ['max-age' => $this->getCacheMaxAge()],
    ];

    return $build;
  }

  /**
   * Disable cache.
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

}
