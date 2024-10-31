<?php

namespace Drupal\grants_handler\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_handler\ApplicationAccessHandler;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationInitService;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\grants_mandate\CompanySelectException;
use Drupal\grants_metadata\ApplicationDataService;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformRequestInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Grants Handler routes.
 */
class ApplicationController extends ControllerBase {

  const ISO8601 = "/^(?:[1-9]\d{3}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1\d|2[0-8])" .
  "|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)" .
  "|(?:[1-9]\d(?:0[48]|[2468][048]|[13579][26])" .
  "|(?:[2468][048]|[13579][26])00)-02-29)(T(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d(?:\.\d{1,9})" .
  "?(?:Z|[+-][01]\d:[0-5]\d))?$/";

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected EntityRepositoryInterface $entityRepository;

  /**
   * The webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected WebformRequestInterface $requestHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $request;

  /**
   * Access to grants profile.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Application data service.
   *
   * @var \Drupal\grants_metadata\ApplicationDataService
   */
  protected ApplicationDataService $applicationDataService;

  /**
   * Application status service.
   *
   * @var \Drupal\grants_handler\ApplicationStatusService
   */
  protected ApplicationStatusService $applicationStatusService;

  /**
   * Application init service.
   *
   * @var \Drupal\grants_handler\ApplicationInitService
   */
  protected ApplicationInitService $applicationInitService;

  /**
   * Access handler for applications.
   *
   * @var \Drupal\grants_handler\ApplicationAccessHandler
   */
  protected ApplicationAccessHandler $applicationAccessHandler;

  /**
   * Getter service for applications.
   *
   * @var \Drupal\grants_handler\ApplicationGetterService
   */
  protected ApplicationGetterService $applicationGetterService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ApplicationController {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');

    $instance->entityRepository = $container->get('entity.repository');
    $instance->requestHandler = $container->get('webform.request');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->renderer = $container->get('renderer');
    $instance->request = $container->get('request_stack');
    $instance->grantsProfileService = $container->get('grants_profile.service');
    $instance->applicationDataService = $container->get('grants_metadata.application_data_service');
    $instance->applicationStatusService = $container->get('grants_handler.application_status_service');
    $instance->applicationInitService = $container->get('grants_handler.application_init_service');
    $instance->applicationAccessHandler = $container->get('grants_handler.application_access_handler');
    $instance->applicationGetterService = $container->get('grants_handler.application_getter_service');

    return $instance;
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param string $webform
   *   Web form id.
   * @param string $webform_submission
   *   Submission id.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function access(AccountInterface $account, string $webform, string $webform_submission): AccessResultInterface {
    $webformObject = Webform::load($webform);
    $webform_submissionObject = WebformSubmission::load($webform_submission);
    if ($webformObject == NULL || $webform_submissionObject == NULL) {
      return AccessResult::forbidden('No submission found');
    }

    // Parameters from the route and/or request as needed.
    return AccessResult::allowedIf(
      $account->hasPermission('view own webform submission') &&
      $this->applicationAccessHandler->singleSubmissionAccess(
        $webform_submissionObject
      ));
  }

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   * @param string $submission_id
   *   Application number from Avus2 / ATV.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function accessByApplicationNumber(AccountInterface $account, string $submission_id): AccessResultInterface {
    try {
      $webform_submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($submission_id);
    }
    catch (\Exception |
    EntityStorageException |
    CompanySelectException $e) {
      return AccessResult::forbidden('Submission gettting failed');
    }

    if ($webform_submission == NULL) {
      return AccessResult::forbidden('No submission found');
    }

    $webform = $webform_submission->getWebform();

    if ($webform == NULL) {
      return AccessResult::forbidden('No webform found');
    }

    // Parameters from the route and/or request as needed.
    return AccessResult::allowedIf(
      $account->hasPermission('view own webform submission') &&
      $this->applicationAccessHandler->singleSubmissionAccess(
        $webform_submission
      ));
  }

  /**
   * Print Drupal messages according to application status.
   *
   * @param string $status
   *   Status string from method.
   */
  public function showMessageForDataStatus(string $status): void {
    $message = NULL;
    $tOpts = ['context' => 'grants_handler'];

    switch ($status) {
      case 'DATA_NOT_SAVED_AVUS2':
      case 'DATA_NOT_SAVED_ATV':
      case 'NO_SUBMISSION_DATA':
        $message = $this->t('Application saving process not done, data on this page is not yet updated.', [], $tOpts);
        break;

      case 'FILE_UPLOAD_PENDING':
        $message = $this->t('File uploads are pending. Data on this page is not fully updated.', [], $tOpts);
        break;

      case 'OK':
      default:

        break;
    }
    if ($message != NULL) {
      $this->messenger()->addWarning($message);
    }
  }

  /**
   * View single application.
   *
   * @param string $submission_id
   *   Application number for submission.
   * @param string $view_mode
   *   View mode.
   * @param string $langcode
   *   Language.
   *
   * @return array
   *   Build for the page.
   */
  public function view(string $submission_id, string $view_mode = 'full', string $langcode = 'fi'): array {
    $view_mode = 'default';

    try {
      $webform_submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($submission_id);

      if ($webform_submission != NULL) {
        $webform = $webform_submission->getWebform();
        $submissionData = $webform_submission->getData();

        $saveIdValidates = $this->applicationDataService->validateDataIntegrity(
          $submissionData,
          $submissionData['application_number'],
          $submissionData['metadata']['saveid'] ?? '');

        $this->showMessageForDataStatus($saveIdValidates);

        // Set webform submission template.
        $build = [
          '#theme' => 'webform_submission',
          '#view_mode' => $view_mode,
          '#webform_submission' => $webform_submission,
        ];

        // Navigation.
        $build['navigation'] = [
          '#type' => 'webform_submission_navigation',
          '#webform_submission' => $webform_submission,
        ];

        // Information.
        $build['information'] = [
          '#theme' => 'webform_submission_information',
          '#webform_submission' => $webform_submission,
          '#source_entity' => $webform_submission,
        ];

        $page = $this->entityTypeManager
          ->getViewBuilder($webform_submission->getEntityTypeId())
          ->view($webform_submission, $view_mode);

        // Submission.
        $build['submission'] = $page;

        // Library.
        $build['#attached']['library'][] = 'webform/webform.admin';

        // Add entities cacheable dependency.
        $this->renderer->addCacheableDependency($build, $this->currentUser);
        $this->renderer->addCacheableDependency($build, $webform);
        $this->renderer->addCacheableDependency($build, $webform_submission);
        return $build;
      }
      else {
        throw new NotFoundHttpException('Application ' . $submission_id . ' not found.');
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException | AtvDocumentNotFoundException | GuzzleException $e) {
      throw new NotFoundHttpException($e->getMessage());
    }
    catch (\Exception $e) {
      throw new NotFoundHttpException($e->getMessage());
    }
    return [];
  }

  /**
   * Create new application and redirect to edit page.
   *
   * @param string $webform_id
   *   Webform to be added.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect to edit form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function newApplication(string $webform_id): RedirectResponse {
    $webform = Webform::load($webform_id);

    if (!$this->applicationStatusService->isApplicationOpen($webform)) {
      // Add message if application is not open.
      $tOpts = ['context' => 'grants_handler'];
      $this->messenger()
        ->addError($this->t('This application is not open', [], $tOpts), TRUE);
      $node_storage = $this->entityTypeManager()->getStorage('node');
      // @codingStandardsIgnoreStart
      // Get service page node.
      $query = $node_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'service')
        ->condition('field_webform', $webform_id);
      // @codingStandardsIgnoreEnd

      $res = $query->execute();
      if (empty($res)) {
        // If we end up here, the real issue is with content input.
        $this->messenger()
          ->addError($this->t('Service page not found!', [], $tOpts), TRUE);
        return $this->redirect('<front>');
      }

      $node = $node_storage->load(reset($res));

      // Redirect user to service page with message.
      return $this->redirect(
        'entity.node.canonical',
        [
          'node' => $node->id(),
        ]
      );
    }

    // Check applicant type before initializing a new draft.
    $currentRole = $this->grantsProfileService->getSelectedRoleData();
    $thirdPartySettings = $webform->getThirdPartySettings('grants_metadata');
    $acceptableApplicantTypes = array_values($thirdPartySettings['applicantTypes']);

    if (!in_array($currentRole['type'], $acceptableApplicantTypes)) {
      return $this->redirect('<front>');
    }

    try {
      $newSubmission = $this->applicationInitService->initApplication($webform->id());
    }
    catch (\Exception $e) {
      $newSubmission = NULL;
      $this->getLogger('ApplicatoinController')->error('Error: %error', [
        '%error' => $e->getMessage(),
      ]);
    }

    return $this->redirect(
      'grants_handler.edit_application',
      [
        'webform' => $webform->id(),
        'webform_submission' => $newSubmission?->id(),
      ]
    );
  }

  /**
   * Returns a page title.
   *
   * This works better than getTitle, since we know the webform object and can
   * get the title from it.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Submission object.
   *
   * @return string
   *   Webform title.
   */
  public function getEditTitle(WebformSubmission $webform_submission): string {
    $webform = $webform_submission->getWebform();
    return $webform->label();
  }

  /**
   * Returns a page title.
   *
   * @param string $submission_id
   *   Application number of the submission. NOT THE OBJECT ID!
   *
   * @return string
   *   Webform title
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\grants_mandate\CompanySelectException
   */
  public function getTitle(string $submission_id): string {
    $submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($submission_id);
    $webform = $submission->getWebform();

    return $webform->label();
  }

}
