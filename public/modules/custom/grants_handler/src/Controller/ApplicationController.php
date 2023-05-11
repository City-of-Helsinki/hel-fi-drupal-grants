<?php

namespace Drupal\grants_handler\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Http\RequestStack;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\grants_handler\ApplicationHandler;
use Drupal\grants_profile\Form\GrantsProfileFormRegisteredCommunity;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\grants_handler\Plugin\WebformElement\CompensationsComposite;
use Drupal\node\Entity\Node;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformRequestInterface;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Returns responses for Grants Handler routes.
 */
class ApplicationController extends ControllerBase {


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
    $instance->applicationHandler = $container->get('grants_handler.application_handler');
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
   */
  public function access(AccountInterface $account, string $webform, string $webform_submission): AccessResultInterface {
    $webformObject = Webform::load($webform);
    $webform_submissionObject = WebformSubmission::load($webform_submission);
    if ($webformObject == NULL || $webform_submissionObject == NULL) {
      return AccessResult::forbidden('No submission found');
    }

    $uri = $this->request->getCurrentRequest()->getUri();

    $operation = 'view';
    if (str_ends_with($uri, '/edit')) {
      $operation = 'edit';
    }

    // Parameters from the route and/or request as needed.
    return AccessResult::allowedIf($account->hasPermission('view own webform submission') && $this->singleSubmissionAccess($account, $operation, $webformObject, $webform_submissionObject));
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   */
  public function accessByApplicationNumber(AccountInterface $account, string $submission_id): AccessResultInterface {
    $webform_submission = ApplicationHandler::submissionObjectFromApplicationNumber($submission_id);

    if ($webform_submission == NULL) {
      return AccessResult::forbidden('No submission found');
    }

    $webform = $webform_submission->getWebform();

    if ($webform == NULL) {
      return AccessResult::forbidden('No webform found');
    }

    $uri = $this->request->getCurrentRequest()->getUri();

    $operation = 'view';
    if (str_ends_with($uri, '/edit')) {
      $operation = 'edit';
    }

    // Parameters from the route and/or request as needed.
    return AccessResult::allowedIf(
      $account->hasPermission('view own webform submission') &&
      $this->singleSubmissionAccess(
        $account,
        $operation,
        $webform,
        $webform_submission
      ));
  }

  /**
   * Placeholder for proper submission content based access checking.
   *
   * Gets webform & submission with data and determines access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   User account.
   * @param string $operation
   *   Operation we check access against.
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform object.
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Submission object.
   *
   * @return bool
   *   Access status
   */
  protected function singleSubmissionAccess(AccountInterface $account, string $operation, Webform $webform, WebformSubmission $webform_submission): bool {

    return TRUE;
  }

  /**
   * Print Drupal messages according to application status.
   *
   * @var string $status
   *  Status string from method.
   */
  public function showMessageForDataStatus(string $status) {
    $message = NULL;

    switch ($status) {
      case 'DATA_NOT_SAVED_AVUS2':
      case 'DATA_NOT_SAVED_ATV':
      case 'NO_SUBMISSION_DATA':
        $message = $this->t('Application saving process not done, data on this page is not yet updated.');
        break;

      case 'FILE_UPLOAD_PENDING':
        $message = $this->t('File uploads are pending. Data on this page is not fully updated.');
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
      $webform_submission = ApplicationHandler::submissionObjectFromApplicationNumber($submission_id);

      if ($webform_submission != NULL) {
        $webform = $webform_submission->getWebform();
        $submissionData = $webform_submission->getData();

        $saveIdValidates = $this->applicationHandler->validateDataIntegrity(
          $webform_submission,
          NULL,
          $submissionData['application_number'],
          $submissionData['metadata']['saveid'] ?? '');

        $this->showMessageForDataStatus($saveIdValidates);

        // Set webform submission template.
        $build = [
          '#theme' => 'webform_submission',
          '#view_mode' => $view_mode,
          '#webform_submission' => $webform_submission,
          // '#editSubmissionLink' =>
          // Link::fromTextAndUrl(t('Edit application'), $url),
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
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \Drupal\helfi_helsinki_profiili\ProfileDataException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function newApplication(string $webform_id): RedirectResponse {

    $webform = Webform::load($webform_id);

    if (!ApplicationHandler::isApplicationOpen($webform)) {
      // Add message if application is not open.
      $this->messenger()->addError('Application is not open', TRUE);

      // Get service page node.
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'service')
        ->condition('field_webform', $webform_id);
      $res = $query->execute();
      $node = Node::load(reset($res));

      // Redirect user to service page with message.
      return $this->redirect(
        'entity.node.canonical',
        [
          'node' => $node->id(),
        ]
      );
    }

    $newSubmission = $this->applicationHandler->initApplication($webform->id());

    return $this->redirect(
      'grants_handler.edit_application',
      [
        'webform' => $webform->id(),
        'webform_submission' => $newSubmission->id(),
      ]
    );
  }

  /**
   * Helper funtion to transform ATV data for print view.
   */
  private function transformField($field, &$pages, &$isSubventionType, &$subventionType, $langcode) {
    if (isset($field['ID'])) {
      $labelData = json_decode($field['meta'], TRUE);
      if (!$labelData) {
        return;
      }
      // Handle application type field.
      if ($field['ID'] === 'applicantType') {
        if ($field['value'] === 'registered_community') {
          $field['value'] = '' . $this->t('Registered community', [], ['langcode' => $langcode]);
        }
        // @todo other types when needed.
      }

      // Handle application type field.
      if ($field['ID'] === 'registrationDate') {
        $field['value'] = date_format(date_create($field['value']), 'd.m.Y');
      }

      // Handle application type field.
      if ($field['ID'] === 'issuer') {
        $issuerLanguageOptions = [
          'context' => 'Grant Issuers',
          'langcode' => $langcode,
        ];
        $issuerArray = [
          "1" => $this->t('State', [], $issuerLanguageOptions),
          "3" => $this->t('EU', [], $issuerLanguageOptions),
          "4" => $this->t('Other', [], $issuerLanguageOptions),
          "5" => $this->t('Foundation', [], $issuerLanguageOptions),
          "6" => $this->t("STEA", [], $issuerLanguageOptions),
        ];
        $field['value'] = $issuerArray[$field['value']];
      }
      if ($labelData['section']['id'] === 'application_number' || $labelData['section']['id'] === 'status') {
        unset($field);
        unset($labelData['section']);
        return;
      }
      if ($labelData['section']['id'] === 'lisatiedot_ja_liitteet_section') {
        if ($field['ID'] === 'integrationID' || $field['ID'] === 'isNewAttachment' || $field['ID'] === 'fileType') {
          unset($field);
          return;
        }
        if ($field['ID'] === 'isDeliveredLater' || $field['ID'] === 'isIncludedInOtherFile') {
          if ($field['value'] === 'false') {
            unset($field);
            return;
          }
          else {
            $field['value'] = Markup::create('<br>');
          }
        }
        if ($field['ID'] === 'fileName') {
          $field['value'] = Markup::create($field['value'] . '<br><br>');
        }

      }
      $i = 0;
      // Handle subvention type composite field.
      if ($field['ID'] === 'subventionType') {
        $typeNames = CompensationsComposite::getOptionsForTypes($langcode);
        $subventionType = $typeNames[$field['value']];
        $isSubventionType = TRUE;
        return;
      }
      elseif ($isSubventionType) {
        $labelData['element']['label'] = $subventionType;
        $isSubventionType = FALSE;
      }

      if ($field['ID'] == 'role') {
        $roles = GrantsProfileFormRegisteredCommunity::getOfficialRoles();
        $role = $roles[$field['value']];
        if ($role) {
          $field['value'] = $role;
        }
      }

      if (isset($field) && array_key_exists('value', $field) && $field['value'] === 'true') {
        $field['value'] = $this->t('Yes', [], [
          'context' => 'Grant Print View Boolean',
          'langcode' => $langcode,
        ]);
      }

      if (isset($field) && array_key_exists('value', $field) && $field['value'] === 'false') {
        $field['value'] = $this->t('No', [], [
          'context' => 'Grant Print View Boolean',
          'langcode' => $langcode,
        ]);
      }
      $newField = [
        'ID' => $field['ID'],
        'value' => $field['value'],
        'valueType' => $field['valueType'],
        'label' => $labelData['element']['label'],
      ];
      $pageNumber = $labelData['page']['number'];
      if (!isset($pages[$pageNumber])) {
        $pages[$pageNumber] = [
          'label' => $labelData['page']['label'],
          'id' => $labelData['page']['id'],
          'sections' => [],
        ];
      }
      $sectionId = $labelData['section']['id'];
      if (!isset($pages[$pageNumber]['sections'][$sectionId])) {
        $pages[$pageNumber]['sections'][$sectionId] = [
          'label' => $labelData['section']['label'],
          'id' => $labelData['section']['id'],
          'weight' => $labelData['section']['weight'],
          'fields' => [],
        ];
      }

      $pages[$pageNumber]['sections'][$sectionId]['fields'][] = $newField;
      return;
    }
    $isSubventionType = FALSE;
    $subventionType = '';

    if (is_array($field)) {
      foreach ($field as $subField) {
        $this->transformField($subField, $pages, $isSubventionType, $subventionType, $langcode);
      }
    }
  }

  /**
   * Print view for single application in ATV schema.
   *
   * @param string $submission_id
   *   Application number for submission.
   *
   * @return array
   *   Render array for the page.
   */
  public function printViewAtv(string $submission_id): array {
    $isSubventionType = FALSE;
    $subventionType = '';
    try {
      $atv_document = ApplicationHandler::atvDocumentFromApplicationNumber($submission_id);
    }
    catch (\Exception $e) {
      throw new NotFoundHttpException('Application ' . $submission_id . ' not found.');
    }
    $langcode = $atv_document->getMetadata()['language'];

    $newPages = [];
    // Iterate over regular fields.
    $compensation = $atv_document->jsonSerialize()['content']['compensation'];

    foreach ($compensation as $pageKey => $page) {
      if (!is_array($page)) {
        continue;
      }
      foreach ($page as $fieldKey => $field) {
        $this->transformField($field, $newPages, $isSubventionType, $subventionType, $langcode);
      }
    }
    $attachments = $atv_document->jsonSerialize()['content']['attachmentsInfo'];
    foreach ($attachments as $pageKey => $page) {
      if (!is_array($page)) {
        continue;
      }
      foreach ($page as $fieldKey => $field) {
        $this->transformField($field, $newPages, $isSubventionType, $subventionType, $langcode);
      }
    }
    // Set correct template.
    $build = [
      '#theme' => 'grants_handler_print_atv_document',
      '#atv_document' => $atv_document->jsonSerialize(),
      '#pages' => $newPages,
      '#document_langcode' => $atv_document->getMetadata()['language'],
      '#cache' => [
        'contexts' => [
          'url.path',
        ],
      ],
    ];

    return $build;
  }

  /**
   * Returns a page title.
   */
  public function getEditTitle($webform_submission): string {
    $webform = $webform_submission->getWebform();
    return $webform->label();
  }

  /**
   * Returns a page title.
   */
  public function getTitle($submission_id): string {
    $submissionObject = ApplicationHandler::submissionObjectFromApplicationNumber($submission_id);
    $webform = $submissionObject->getWebform();
    return $webform->label();
  }

}
