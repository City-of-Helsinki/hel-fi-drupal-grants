<?php

namespace Drupal\grants_handler\Controller;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_handler\FormLockService;
use Drupal\grants_handler\GrantsHandlerNavigationHelper;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Grants Handler routes.
 */
class WebformNavigationController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The request service.
   *
   * @var \Drupal\Core\Http\RequestStack
   */
  protected RequestStack $request;

  /**
   * Access to grants profile.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Application handler.
   *
   * @var \Drupal\grants_handler\ApplicationHandler
   */
  protected ApplicationHandler $applicationHandler;

  /**
   * Form lock service.
   *
   * @var \Drupal\grants_handler\FormLockService
   */
  protected FormLockService $formLockService;

  /**
   * Use helpoer.
   *
   * @var \Drupal\grants_handler\GrantsHandlerNavigationHelper
   */
  protected GrantsHandlerNavigationHelper $wfNaviHelper;

  /**
   * Access to ATV.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): WebformNavigationController {
    $instance = parent::create($container);
    $instance->currentUser = $container->get('current_user');

    $instance->request = $container->get('request_stack');
    $instance->grantsProfileService = $container->get('grants_profile.service');
    $instance->applicationHandler = $container->get('grants_handler.application_handler');
    $instance->formLockService = $container->get('grants_handler.form_lock_service');
    $instance->wfNaviHelper = $container->get('grants_handler.navigation_helper');
    $instance->atvService = $container->get('helfi_atv.atv_service');
    return $instance;
  }

  /**
   * Clear submission logs for given submission.
   *
   * @param string $submission_id
   *   Submission.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Drupal\Core\Access\AccessResultInterface
   *   Redirect to form.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function clearDraftData(string $submission_id): RedirectResponse|AccessResultInterface {
    $redirectUrl = Url::fromRoute('grants_oma_asiointi.front');
    $tOpts = ['context' => 'grants_handler'];

    $locked = $this->formLockService->isApplicationFormLocked($submission_id);
    if ($locked) {
      $this->messenger()
        ->addError($this->t('Deleting draft failed. This form is currently locked for another person.', [], $tOpts));
      $this->getLogger('grants_handler')
        ->error('Error: Tried to delete draft which is locked to another user.');
      return new RedirectResponse($redirectUrl->toString());
    }

    try {
      $submission = ApplicationHandler::submissionObjectFromApplicationNumber($submission_id);
    }
    catch (\Exception  $e) {
      $this->messenger()
        ->addError($this->t('Deleting draft failed. Error has been logged, please contact support.', [], $tOpts));
      $this->getLogger('grants_handler')
        ->error('Error: %error', ['%error' => $e->getMessage()]);
      return new RedirectResponse($redirectUrl->toString());
    }

    $submissionData = $submission->getData();

    if (empty($submissionData)) {
      $submission->delete();
    }
    elseif ($submissionData['status'] !== 'DRAFT') {
      $this->messenger()
        ->addError($this->t('Only DRAFT status submissions are deletable', [], $tOpts));
      // Throw new AccessException('Only DRAFT status submissions
      // are deletable');.
    }
    else {

      $this->wfNaviHelper->deleteSubmissionLogs($submission);

      try {
        $document = $this->applicationHandler->getAtvDocument($submission_id);

        if ($this->atvService->deleteDocument($document)) {
          $submission->delete();
          $this->messenger()->addStatus($this->t('Draft deleted.', [], $tOpts));
        }
      }
      catch (\Exception $e) {
        $this->messenger()
          ->addError($this->t('Deleting draft failed. Error has been logged, please contact support.', [], $tOpts));
        $this->getLogger('grants_handler')
          ->error('Error: %error', ['%error' => $e->getMessage()]);
      }
    }

    return new RedirectResponse($redirectUrl->toString());

  }

}
