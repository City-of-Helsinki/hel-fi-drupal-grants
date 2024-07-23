<?php

namespace Drupal\grants_handler\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\grants_handler\ApplicationAccessHandler;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationHelpers;
use Drupal\grants_handler\ApplicationInitService;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\grants_handler\Plugin\WebformElement\CompensationsComposite;
use Drupal\grants_mandate\CompanySelectException;
use Drupal\grants_metadata\ApplicationDataService;
use Drupal\grants_metadata\InputmaskHandler;
use Drupal\grants_profile\Form\GrantsProfileFormRegisteredCommunity;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function accessByApplicationNumber(AccountInterface $account, string $submission_id): AccessResultInterface {
    try {
      $webform_submission = $this->applicationGetterService->submissionObjectFromApplicationNumber($submission_id);
    }
    catch (InvalidPluginDefinitionException |
    PluginNotFoundException |
    EntityStorageException |
    TempStoreException |
    CompanySelectException |
    AtvDocumentNotFoundException |
    AtvFailedToConnectException |
    TokenExpiredException |
    GuzzleException $e) {
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function newApplication(string $webform_id): RedirectResponse {

    $webform = Webform::load($webform_id);

    if (!$this->applicationStatusService->isApplicationOpen($webform)) {
      // Add message if application is not open.
      $tOpts = ['context' => 'grants_handler'];
      $this->messenger()->addError($this->t('This application is not open', [], $tOpts), TRUE);
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
        $this->messenger()->addError($this->t('Service page not found!', [], $tOpts), TRUE);
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
   * Helper funtion to transform ATV data for print view.
   */
  private function transformField($field, &$pages, &$isSubventionType, &$subventionType, $langcode) {
    if (isset($field['ID'])) {
      $labelData = json_decode($field['meta'], TRUE);
      if (!$labelData || $labelData['element']['hidden']) {
        return;
      }
      // Handle application type field.
      if ($field['ID'] === 'applicantType' && $field['value'] === 'registered_community') {
        $field['value'] = '' . $this->t('Registered community', [], ['langcode' => $langcode]);
        // Add other types here when needed.
      }
      // Handle dates.
      if (preg_match(self::ISO8601, $field['value'])) {
        $field['value'] = date_format(date_create($field['value']), 'd.m.Y');
      }

      // Handle input masks.
      if (isset($labelData['element']['input_mask'])) {
        $field['value'] = InputmaskHandler::convertPossibleInputmaskValue($field['value'], $labelData);
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
          'context' => 'grants_handler',
          'langcode' => $langcode,
        ]);
      }

      if (isset($field) && array_key_exists('value', $field) && $field['value'] === 'false') {
        $field['value'] = $this->t('No', [], [
          'context' => 'grants_handler',
          'langcode' => $langcode,
        ]);
      }

      if ($field['value'] === '') {
        $field['value'] = '-';
      }

      $newField = [
        'ID' => $field['ID'],
        'value' => $labelData['element']['valueTranslation'] ?? $field['value'],
        'valueType' => $field['valueType'],
        'label' => $labelData['element']['label'],
        'weight' => $labelData['element']['weight'],
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
      /** @var \Drupal\helfi_atv\AtvDocument $atv_document */
      $atv_document = ApplicationHelpers::atvDocumentFromApplicationNumber($submission_id);
    }
    catch (\Exception $e) {
      throw new NotFoundHttpException('Application ' . $submission_id . ' not found.');
    }
    $langcode = $atv_document->getMetadata()['language'];

    $newPages = [];
    // Iterate over regular fields.
    $compensation = $atv_document->jsonSerialize()['content']['compensation'];

    foreach ($compensation as $page) {
      if (!is_array($page)) {
        continue;
      }
      foreach ($page as $field) {
        $this->transformField($field, $newPages, $isSubventionType, $subventionType, $langcode);
      }
    }
    $attachments = $atv_document->jsonSerialize()['content']['attachmentsInfo'];
    foreach ($attachments as $page) {
      if (!is_array($page)) {
        continue;
      }
      foreach ($page as $field) {
        $this->transformField($field, $newPages, $isSubventionType, $subventionType, $langcode);
      }
    }

    // Sort the fields based on weight.
    foreach ($newPages as $pageKey => $page) {
      foreach ($page['sections'] as $sectionKey => $section) {
        usort($newPages[$pageKey]['sections'][$sectionKey]['fields'], function ($fieldA, $fieldB) {
          return $fieldA['weight'] - $fieldB['weight'];
        });
      }
    }

    if (isset($compensation['additionalInformation'])) {
      $tOpts = [
        'context' => 'grants_handler',
        'langcode' => $langcode,
      ];
      $field = [
        'ID' => 'additionalInformationField',
        'value' => $compensation['additionalInformation'],
        'valueType' => 'string',
        'label' => $this->t('Additional Information', [], $tOpts),
        'weight' => 1,
      ];
      $sections = [];
      $sections['section'] = [
        'label' => $this->t('Additional information concerning the application', [], $tOpts),
        'id' => 'additionalInformationPageSection',
        'weight' => 1,
        'fields' => [$field],
      ];
      $newPages['additionalInformation'] = [
        'label' => $this->t('Additional Information', [], $tOpts),
        'id' => 'additionalInformationPage',
        'sections' => $sections,
      ];
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
    $webform = ApplicationHelpers::getWebformFromApplicationNumber($submission_id);
    return $webform->label();
  }

}
