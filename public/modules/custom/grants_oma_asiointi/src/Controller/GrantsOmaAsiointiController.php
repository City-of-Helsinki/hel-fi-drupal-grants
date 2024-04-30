<?php

namespace Drupal\grants_oma_asiointi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_mandate\Controller\GrantsMandateController;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for Oma Asiointi routes.
 *
 * @phpstan-consistent-constructor
 */
class GrantsOmaAsiointiController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The request stack used to access request globals.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Access to profile data.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Logger access.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * The grants_handler.application_handler service.
   *
   * @var \Drupal\grants_handler\ApplicationHandler
   */
  protected ApplicationHandler $applicationHandler;

  /**
   * The helfi_atv.atv_service service.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $helfiAtvAtvService;

  /**
   * CompanyController constructor.
   */
  public function __construct(
    RequestStack $requestStack,
    AccountProxyInterface $current_user,
    LanguageManagerInterface $language_manager,
    GrantsProfileService $grantsProfileService,
    ApplicationHandler $grants_handler_application_handler,
    AtvService $helfi_atv_atv_service,
  ) {
    $this->requestStack = $requestStack;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
    $this->grantsProfileService = $grantsProfileService;
    $this->logger = $this->getLogger('grants_oma_asiointi');
    $this->applicationHandler = $grants_handler_application_handler;
    $this->helfiAtvAtvService = $helfi_atv_atv_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GrantsMandateController|static {
    return new static(
      $container->get('request_stack'),
      $container->get('current_user'),
      $container->get('language_manager'),
      $container->get('grants_profile.service'),
      $container->get('grants_handler.application_handler'),
      $container->get('helfi_atv.atv_service'),
    );
  }

  /**
   * Controller for setting time for closing a notification.
   */
  public function logCloseTime() {
    $dateTime = new \DateTime();
    $timeStamp = $dateTime->getTimestamp();
    $this->grantsProfileService->setNotificationShown($timeStamp);

    // Return a JSON response with the logged close time.
    return new JsonResponse(['closeTime' => $timeStamp]);
  }

  /**
   * Builds the response.
   *
   * @return array
   *   Render array
   */
  public function build(): array {

    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();

    if ($selectedCompany == NULL) {
      throw new AccessDeniedHttpException('User not authorised');
    }

    $grantsProfile = [];

    /** @var \Drupal\helfi_atv\AtvDocument | null $grantsProfileDocument */
    $grantsProfileDocument = $this->grantsProfileService->getGrantsProfile($selectedCompany);
    if ($grantsProfileDocument) {
      $grantsProfile = $grantsProfileDocument->getContent();
    }

    $showProfileNotice = FALSE;

    if (empty($grantsProfile["addresses"]) || empty($grantsProfile["bankAccounts"])) {
      $showProfileNotice = TRUE;
    }

    $updatedAt = $this->grantsProfileService->getUpdatedAt();
    $notificationShown = $this->grantsProfileService->getNotificationShown();

    $notificationShownTimestamp = ((int) $notificationShown) / 1000;
    $threeMonthsAgoTimestamp = strtotime('-3 months');

    $showNotification = FALSE;

    if (($notificationShownTimestamp < $threeMonthsAgoTimestamp) && ($updatedAt < $threeMonthsAgoTimestamp)) {
      $showNotification = TRUE;
    }

    $appEnv = ApplicationHandler::getAppEnv();

    try {
      // Get applications from ATV.
      $applications = ApplicationHandler::getCompanyApplications(
        $selectedCompany,
        $appEnv,
        FALSE,
        TRUE,
        'application_list_item'
      );
    }
    catch (\Throwable $e) {
      // If errors, just don't do anything.
      $applications = [];
    }
    $drafts = $applications['DRAFT'] ?? [];
    unset($applications['DRAFT']);
    // Parse messages.
    [$other, $unreadMsg] = $this->parseMessages($applications);

    return [
      '#theme' => 'grants_oma_asiointi_front',
      '#infoboxes' => [
        '#theme' => 'grants_oma_asiointi_infoboxes',
        '#profileNotice' => $showProfileNotice,
      ],
      '#drafts' => [
        '#theme' => 'application_list',
        '#type' => 'drafts',
        '#header' => $this->t('Applications in progress', [], ['context' => 'grants_oma_asiointi']),
        '#id' => 'oma-asiointi__drafts',
        '#items' => $drafts,
      ],
      '#others' => [
        '#theme' => 'application_list',
        '#type' => 'sent',
        '#header' => $this->t('Sent applications', [], ['context' => 'grants_oma_asiointi']),
        '#id' => 'oma-asiointi__sent',
        '#items' => $other,
      ],
      '#notification' => [
        '#theme' => 'grants_user_data_notification',
        '#showNotification' => $showNotification,
      ],
      '#unread' => $unreadMsg,
    ];
  }

  /**
   * Parse messages from applications.
   */
  protected function parseMessages(array $applications) {
    $other = [];
    $unreadMsg = [];

    foreach ($applications as $values) {
      $other = array_merge($other, $values);
      foreach ($values as $application) {
        $appMessages = ApplicationHandler::parseMessages($application['#submission']);
        foreach ($appMessages as $msg) {
          if ($msg["messageStatus"] == 'UNREAD' && $msg["sentBy"] == 'Avustusten kasittelyjarjestelma') {
            $unreadMsg[] = [
              '#theme' => 'message_notification_item',
              '#message' => $msg,
            ];
          }
        }
      }
    }
    return [$other, $unreadMsg];
  }

  /**
   * Get title for oma asiointi page.
   *
   * @return string
   *   Title.
   */
  public function title() :string {
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    return $selectedCompany['name'] ?? '';
  }

}
