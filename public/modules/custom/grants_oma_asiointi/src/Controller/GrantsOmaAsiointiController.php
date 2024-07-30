<?php

namespace Drupal\grants_oma_asiointi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\Helpers;
use Drupal\grants_handler\MessageService;
use Drupal\grants_mandate\Controller\GrantsMandateController;
use Drupal\grants_profile\GrantsProfileException;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvService;
use GuzzleHttp\Exception\GuzzleException;
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
   * Logger access.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * CompanyController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   The grants profile service.
   * @param \Drupal\helfi_atv\AtvService $helfiAtvAtvService
   *   The ATV service.
   * @param \Drupal\grants_handler\MessageService $messageService
   *   The message service.
   * @param \Drupal\grants_handler\ApplicationGetterService $applicationGetterService
   *   The application getter service.
   */
  public function __construct(
    protected RequestStack $requestStack,
    protected GrantsProfileService $grantsProfileService,
    protected AtvService $helfiAtvAtvService,
    protected MessageService $messageService,
    protected ApplicationGetterService $applicationGetterService,
  ) {
    $this->logger = $this->getLogger('grants_oma_asiointi');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GrantsMandateController|static {
    return new static(
      $container->get('request_stack'),
      $container->get('grants_profile.service'),
      $container->get('helfi_atv.atv_service'),
      $container->get('grants_handler.message_service'),
      $container->get('grants_handler.application_getter_service')
    );
  }

  /**
   * Controller for setting time for closing a notification.
   */
  public function logCloseTime(): JsonResponse {
    $dateTime = new \DateTime();
    $timeStamp = $dateTime->getTimestamp();
    try {
      $this->grantsProfileService->setNotificationShown($timeStamp);
    }
    catch (GrantsProfileException | GuzzleException $e) {
      $this->logger->error('Failed to set notification close time: @error', ['@error' => $e->getMessage()]);
      return new JsonResponse(['error' => 'Failed to set notification close time'], 500);
    }

    // Return a JSON response with the logged close time.
    return new JsonResponse(['closeTime' => $timeStamp]);
  }

  /**
   * Builds the response.
   *
   * @return array
   *   Render array
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
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

    $appEnv = Helpers::getAppEnv();

    try {
      // Get applications from ATV.
      $applications = $this->applicationGetterService->getCompanyApplications(
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
   *
   * @param array $applications
   *   Applications.
   *
   * @return array
   *   Parsed messages.
   */
  protected function parseMessages(array $applications): array {
    $other = [];
    $unreadMsg = [];

    foreach ($applications as $values) {
      $other = array_merge($other, $values);
      foreach ($values as $application) {
        $appMessages = $this->messageService->parseMessages($application['#submission']);
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
