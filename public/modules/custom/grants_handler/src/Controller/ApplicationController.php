<?php

namespace Drupal\grants_handler\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Render\RendererInterface;
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
final class ApplicationController extends ControllerBase {

  const ISO8601 = "/^(?:[1-9]\d{3}-(?:(?:0[1-9]|1[0-2])-(?:0[1-9]|1\d|2[0-8])" .
  "|(?:0[13-9]|1[0-2])-(?:29|30)|(?:0[13578]|1[02])-31)" .
  "|(?:[1-9]\d(?:0[48]|[2468][048]|[13579][26])" .
  "|(?:[2468][048]|[13579][26])00)-02-29)(T(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d(?:\.\d{1,9})" .
  "?(?:Z|[+-][01]\d:[0-5]\d))?$/";

  use StringTranslationTrait;

  public function __construct(
    private readonly EntityRepositoryInterface $entityRepository,
    private readonly WebformRequestInterface $requestHandler,
    private readonly RendererInterface $renderer,
    private readonly RequestStack $request,
    private readonly GrantsProfileService $grantsProfileService,
    private readonly ApplicationDataService $applicationDataService,
    private readonly ApplicationStatusService $applicationStatusService,
    private readonly ApplicationInitService $applicationInitService,
    private readonly ApplicationAccessHandler $applicationAccessHandler,
    private readonly ApplicationGetterService $applicationGetterService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ApplicationController {
    return new self(
      $container->get('entity.repository'),
      $container->get('webform.request'),
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('grants_profile.service'),
      $container->get('grants_metadata.application_data_service'),
      $container->get('grants_handler.application_status_service'),
      $container->get('grants_handler.application_init_service'),
      $container->get('grants_handler.application_access_handler'),
      $container->get('grants_handler.application_getter_service')
    );
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
   * @throws \Drupal\grants_mandate\CompanySelectException
   * @throws \Drupal\grants_profile\GrantsProfileException
   */
  public function accessByApplicationNumber(AccountInterface $account, string $submission_id): AccessResultInterface {
    try {
      $webform_submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($submission_id);
    }
    catch (
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

        $page = $this->entityTypeManager()
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
