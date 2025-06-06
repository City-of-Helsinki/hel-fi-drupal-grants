<?php

namespace Drupal\grants_handler\Plugin\WebformHandler;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\DrupalKernel;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\Core\TypedData\Exception\ReadOnlyException;
use Drupal\Core\Url;
use Drupal\grants_attachments\AttachmentHandler;
use Drupal\grants_attachments\AttachmentRemover;
use Drupal\grants_handler\ApplicationException;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\ApplicationHelpers;
use Drupal\grants_handler\ApplicationInitService;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\grants_handler\ApplicationSubmitType;
use Drupal\grants_handler\ApplicationUploaderService;
use Drupal\grants_handler\ApplicationValidator;
use Drupal\grants_handler\Event\ApplicationSubmitEvent;
use Drupal\grants_handler\FormLockService;
use Drupal\grants_handler\GrantsErrorStorage;
use Drupal\grants_handler\GrantsException;
use Drupal\grants_handler\GrantsHandlerNavigationHelper;
use Drupal\grants_handler\Helpers;
use Drupal\grants_handler\WebformSubmissionNotesHelper;
use Drupal\grants_mandate\CompanySelectException;
use Drupal\grants_metadata\ApplicationDataService;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Main handler for Grants forms.
 *
 * @WebformHandler(
 *   id = "grants_handler",
 *   label = @Translation("Grants Handler"),
 *   category = @Translation("helfi"),
 *   description = @Translation("Grants webform handler"),
 *   cardinality =
 *   \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_IGNORED,
 *   submission =
 *   \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class GrantsHandler extends WebformHandlerBase {

  /**
   * Form data saved because the data in saved submission is not preserved.
   *
   * @var array
   *   Holds submitted data for processing in confirmForm.
   *
   * When we want to delete all submitted data before saving
   * submission to database. This way we can still use webform functionality
   * while not saving any sensitive data to local drupal.
   */
  private array $submittedFormData = [];

  /**
   * Application type.
   *
   * @var string
   */
  protected string $applicationType = '';

  /**
   * Applicant type.
   *
   * Private / registered / UNregistered.
   *
   * @var string
   */
  protected string $applicantType = '';

  /**
   * Application type ID.
   *
   * @var string
   */
  protected string $applicationTypeID = '';

  /**
   * Generated application number.
   *
   * @var string
   */
  protected string $applicationNumber = '';

  /**
   * Application acting year options.
   *
   * @var array
   */
  protected array $applicationActingYears = [];

  /**
   * Status for updated submission.
   *
   * Old one if no update.
   *
   * @var string
   */
  protected string $newStatus;

  /**
   * Save submit type for methods where it cannot be calculated from form_state.
   */
  protected ?ApplicationSubmitType $submitType = NULL;

  /**
   * Save form for methods where form is not available.
   *
   * @var array
   */
  protected array $formTemp;

  /**
   * Save form sate for methods where it's not available.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected FormStateInterface $formStateTemp;

  /**
   * Are we redirecting?
   *
   * @var bool
   */
  protected bool $isRedirect = FALSE;

  /**
   * The account proxy interface.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The helsinki profiili user data service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $userExternalData;

  /**
   * The grants profile service.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected DateFormatter $dateFormatter;

  /**
   * The attachment handler service.
   *
   * @var \Drupal\grants_attachments\AttachmentHandler
   */
  protected AttachmentHandler $attachmentHandler;

  /**
   * The grants handler navigation helper.
   *
   * @var \Drupal\grants_handler\GrantsHandlerNavigationHelper
   */
  protected GrantsHandlerNavigationHelper $grantsFormNavigationHelper;

  /**
   * The application validator.
   *
   * @var \Drupal\grants_handler\ApplicationValidator
   */
  protected ApplicationValidator $applicationValidator;

  /**
   * The application status service.
   *
   * @var \Drupal\grants_handler\ApplicationStatusService
   */
  protected ApplicationStatusService $applicationStatusService;

  /**
   * The form lock service.
   *
   * @var \Drupal\grants_handler\FormLockService
   */
  protected FormLockService $formLockService;

  /**
   * The drupal kernel.
   *
   * @var \Drupal\Core\DrupalKernel
   */
  protected DrupalKernel $kernel;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestStack;

  /**
   * The application data service.
   *
   * @var \Drupal\grants_metadata\ApplicationDataService
   */
  protected ApplicationDataService $applicationDataService;

  /**
   * The application initialization service.
   *
   * @var \Drupal\grants_handler\ApplicationInitService
   */
  protected ApplicationInitService $applicationInitService;

  /**
   * The application uplaod service.
   *
   * @var \Drupal\grants_handler\ApplicationUploaderService
   */
  protected ApplicationUploaderService $applicationUploaderService;

  /**
   * The application getter service.
   *
   * @var \Drupal\grants_handler\ApplicationGetterService
   */
  protected ApplicationGetterService $applicationGetterService;

  /**
   * The attachment remover.
   *
   * @var \Drupal\grants_attachments\AttachmentRemover
   */
  protected AttachmentRemover $attachmentRemover;

  /**
   * Event dispatcher.
   */
  private EventDispatcherInterface $eventDispatcher;

  /**
   * {@inheritDoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): WebformHandlerBase|GrantsHandler|ContainerFactoryPluginInterface {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->currentUser = $container->get('current_user');
    $instance->userExternalData = $container->get('helfi_helsinki_profiili.userdata');
    $instance->grantsProfileService = $container->get('grants_profile.service');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->attachmentHandler = $container->get('grants_attachments.attachment_handler');
    $instance->grantsFormNavigationHelper = $container->get('grants_handler.navigation_helper');
    $instance->applicationValidator = $container->get('grants_handler.application_validator');
    $instance->applicationStatusService = $container->get('grants_handler.application_status_service');
    $instance->formLockService = $container->get('grants_handler.form_lock_service');
    assert($container->get('kernel') instanceof DrupalKernel);
    $instance->kernel = $container->get('kernel');
    $instance->requestStack = $container->get('request_stack');
    $instance->applicationDataService = $container->get('grants_metadata.application_data_service');
    $instance->applicationInitService = $container->get('grants_handler.application_init_service');
    $instance->applicationUploaderService = $container->get('grants_handler.application_uploader_service');
    $instance->applicationGetterService = $container->get('grants_handler.application_getter_service');
    $instance->attachmentRemover = $container->get('grants_attachments.attachment_remover');

    $instance->attachmentHandler->setDebug($instance->isDebug());
    $instance->applicationValidator->setDebug($instance->isDebug());
    $instance->applicationStatusService->setDebug($instance->isDebug());

    $instance->eventDispatcher = $container->get(EventDispatcherInterface::class);

    return $instance;
  }

  /**
   * Convert EUR format value to float.
   *
   * @param string|null $value
   *   Value to be converted.
   *
   * @return float|null
   *   Floated value.
   */
  public static function convertToFloat(?string $value = ''): ?float {
    if (is_null($value)) {
      return NULL;
    }

    if ($value === '') {
      return NULL;
    }

    $value = str_replace(['€', ',', ' '], ['', '.', ''], $value);
    return (float) $value;
  }

  /**
   * Convert EUR format value to "int" .
   *
   * @param string|null $value
   *   Value to be converted.
   *
   * @return int|null
   *   Int value.
   */
  public static function convertToInt(?string $value = ''): ?int {
    if (is_null($value)) {
      return NULL;
    }

    if ($value === '') {
      return NULL;
    }

    $value = str_replace(['€', ',', ' ', '_'], ['', '.', '', ''], $value);
    $value = (int) $value;
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'debug' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    // Development.
    $tOpts = ['context' => 'grants_handler'];

    $form['development'] = [
      '#type' => 'details',
      '#title' => $this->t('Development settings'),
    ];
    $form['development']['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable debugging'),
      '#description' => $this->t('If checked, every handler method invoked will be displayed onscreen to all users.', [], $tOpts),
      '#return_value' => TRUE,
      '#default_value' => $this->configuration['debug'],
    ];

    return $this->setSettingsParents($form);
  }

  /**
   * Calculate & set total values from added elements in webform.
   */
  protected function setTotals(): void {
    $tempTotal = 0;

    if (isset($this->submittedFormData['myonnetty_avustus']) &&
      is_array($this->submittedFormData['myonnetty_avustus'])) {
      $tempTotal = 0;
      foreach ($this->submittedFormData['myonnetty_avustus'] as $item) {
        $amount = self::convertToFloat($item['amount']);
        $tempTotal += $amount;
      }
      $this->submittedFormData['myonnetty_avustus_total'] = $tempTotal;
    }

    if (isset($this->submittedFormData['haettu_avustus_tieto']) &&
      is_array($this->submittedFormData['haettu_avustus_tieto'])) {
      $tempTotal = 0;
      foreach ($this->submittedFormData['haettu_avustus_tieto'] as $item) {
        $amount = self::convertToFloat($item['amount']);
        $tempTotal += $amount;
      }
      $this->submittedFormData['haettu_avustus_tieto_total'] = $tempTotal;
    }

    $this->submittedFormData['compensation_total_amount'] = $tempTotal;
  }

  /**
   * Format form values to be consumed with typedata.
   *
   * @param \Drupal\webform\Entity\WebformSubmission $webform_submission
   *   Submission object.
   *
   * @return array
   *   Massaged values.
   */
  protected function massageFormValuesFromWebform(WebformSubmission $webform_submission): array {
    $values = $webform_submission->getData();

    if (isset($this->formStateTemp)) {
      $formValues = $this->formStateTemp->getValues();
    }

    $this->setFromThirdPartySettings($webform_submission->getWebform());

    if (isset($this->applicationType) && $this->applicationType != '') {
      $values['application_type'] = $this->applicationType;
    }
    if (isset($this->applicationTypeID) && $this->applicationTypeID != '') {
      $values['application_type_id'] = $this->applicationTypeID;
    }

    if (isset($values['community_address']) && $values['community_address'] !== NULL) {
      unset($values['community_address']);
      unset($values['community_address_select']);

      if (isset($formValues["community_address"]["community_street"]) && !empty($formValues["community_address"]["community_street"])) {
        $values["community_street"] = $formValues["community_address"]["community_street"];
      }
      if (isset($formValues["community_address"]["community_city"]) && !empty($formValues["community_address"]["community_city"])) {
        $values["community_city"] = $formValues["community_address"]["community_city"];
      }
      if (isset($formValues["community_address"]["community_post_code"]) &&
        !empty($formValues["community_address"]["community_post_code"])) {
        $values["community_post_code"] = $formValues["community_address"]["community_post_code"];
      }
      $values["community_country"] = 'Suomi';
    }

    if (isset($values['bank_account']) && $values['bank_account'] !== NULL) {
      $status = $values['status'] ?? '';
      $checkBankFileStatus = ['DRAFT', 'SENT', 'SUBMITTED', 'RECEIVED'];

      // Make sure the bank account still exists on profile,
      // but only in case the application is still editable.
      // If the application is being processed,
      // we don't want to mess with this value.
      $correctAccount = TRUE;
      if (in_array($status, $checkBankFileStatus)) {
        $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
        $profile = $this->grantsProfileService->getGrantsProfileContent($selectedCompany);

        $correctAccount = array_find(
          $profile['bankAccounts'],
          fn($account) => $account['bankAccount'] === $values['bank_account']['account_number']
        );
      }

      if ($correctAccount) {
        $values['account_number'] = $values['bank_account']['account_number'];

        if (isset($values['bank_account']['account_number_owner_name']) && !empty($values['bank_account']['account_number_owner_name'])) {
          $values['account_number_owner_name'] = $values['bank_account']['account_number_owner_name'];
        }
        if (isset($values['bank_account']['account_number_ssn']) && !empty($values['bank_account']['account_number_ssn'])) {
          $values['account_number_ssn'] = $values['bank_account']['account_number_ssn'];
        }
      }

      unset($values['bank_account']);
    }

    $budgetFields = NestedArray::filter($values, function ($i) {
      if (is_array($i) && (isset($i['costGroupName']) || isset($i['incomeGroupName']))) {
        return TRUE;
      }
      elseif (is_array($i) && !empty(reset($i))) {
        $elem = reset($i);
        return isset($elem['costGroupName']) || isset($elem['incomeGroupName']);
      }

      return FALSE;
    });

    // Force incomeGroupName by found fields.
    $budgetInfo = [];
    foreach ($budgetFields as $fieldKey => $field) {
      $field = reset($values[$fieldKey]);
      if (isset($field['costGroupName'])) {
        $values['costGroupName'] = $field['costGroupName'];
      }
      elseif (isset($field['incomeGroupName'])) {
        $values['incomeGroupName'] = $field['incomeGroupName'];
      }
      $budgetInfo[$fieldKey] = $values[$fieldKey];
    }

    $values['budgetInfo'] = $budgetInfo;

    // If for some reason we don't have application number at this point.
    if (!isset($this->applicationNumber) || $this->applicationNumber == '') {
      // But if one is coming from form (hidden field)
      if (isset($this->submittedFormData['application_number']) && $this->submittedFormData['application_number'] != '') {
        // Use it.
        $this->applicationNumber = $this->submittedFormData['application_number'];
      }
      else {
        // But if we have saved webform earlier, we can get the application
        // number from submission serial.
        if ($webform_submission->serial()) {
          $submissionData = $webform_submission->getData();
          $applicationNumber = $submissionData['application_number'] ?? ApplicationHelpers::createApplicationNumber($webform_submission);

          $this->applicationNumber = $applicationNumber;
          $this->submittedFormData['application_number'] = $this->applicationNumber;
          $values['application_number'] = $this->applicationNumber;
        }
        // Hopefully we never reach here, but there should be additional checks
        // for application number to exists.
        // and it's no biggie since we can always get it from the method above
        // as long as we have our submission object.
      }
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$values) {
    $currentUserRoles = $this->currentUser->getRoles();

    if (in_array('helsinkiprofiili', $currentUserRoles)) {
      // These both are required to be selected.
      // probably will change when we have proper company selection process.
      $selectedCompany = $this->grantsProfileService->getSelectedRoleData();

      if ($selectedCompany == NULL) {
        throw new CompanySelectException('User not authorised');
      }

      $webform = Webform::load($values['webform_id']);
      $this->setFromThirdPartySettings($webform);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\grants_mandate\CompanySelectException
   */
  public function prepareForm(WebformSubmissionInterface $webform_submission, $operation, FormStateInterface $form_state): void {
    $tOpts = ['context' => 'grants_handler'];

    $currentUserRoles = $this->currentUser->getRoles();

    // If user is not authenticated via HP we don't do anything here.
    if (!in_array('helsinkiprofiili', $currentUserRoles)) {
      return;
    }

    $webform = $webform_submission->getWebform();

    // If we're coming here with ADD operator, then we redirect user to
    // new application endpoint and from there they're redirected back ehre
    // with newly initialized application. And edit operator.
    // Redirecting inside a handler is not a supported function
    // so we can not just stop code execution. Redirect boolean is used
    // to prevent unnecessary code execution.
    if ($operation == 'add') {
      $webform_id = $webform->id();
      $url = Url::fromRoute('grants_handler.new_application', [
        'webform_id' => $webform_id,
      ]);
      $redirect = new RedirectResponse($url->toString());
      $redirect->send();
      $this->isRedirect = TRUE;
      return;
    }

    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();

    if ($selectedCompany == NULL) {
      throw new CompanySelectException('User does not have proper mandate.');
    }

    $thirdPartySettings = $webform->getThirdPartySettings('grants_metadata');

    // Old applications have only single selection, we need to support this.
    if (!is_array($thirdPartySettings["applicantTypes"])) {
      $formApplicationTypes[] = $thirdPartySettings["applicantTypes"];
    }
    else {
      $formApplicationTypes = array_values($thirdPartySettings["applicantTypes"]);
    }
    // If user selected role is not in forms roles, throw an error.
    if (!in_array($selectedCompany["type"], $formApplicationTypes)) {
      throw new CompanySelectException('User role is not allowed to use this form.');
    }

    try {
      $grantsProfileDocument = $this->grantsProfileService->getGrantsProfile($selectedCompany);
      if ($grantsProfileDocument instanceof AtvDocument) {
        $grantsProfile = $grantsProfileDocument->getContent();
      }
      else {
        throw new \Exception();
      }
    }
    catch (\Exception) {
      $this->messenger()
        ->addWarning($this->t('You must have grants profile created.', [], $tOpts));

      $this->terminateWithRedirect(Url::fromRoute('grants_profile.edit'));
      return;
    }

    if (empty($grantsProfile["addresses"]) || empty($grantsProfile["bankAccounts"])) {
      if (empty($grantsProfile["addresses"])) {
        $this->messenger()
          ->addWarning($this->t('You must have address saved to your profile.', [], $tOpts));
      }
      if (empty($grantsProfile["bankAccounts"])) {
        $this->messenger()
          ->addWarning($this->t('You must have bank account saved to your profile.', [], $tOpts));
      }

      $this->terminateWithRedirect(Url::fromRoute('grants_profile.edit'));
      return;
    }

    parent::prepareForm($webform_submission, $operation, $form_state);
  }

  /**
   * Terminates the request and redirects to the given URL.
   */
  private function terminateWithRedirect(Url $redirect): void {
    $response = new RedirectResponse($redirect->toString());
    $request = $this->requestStack->getCurrentRequest();
    // Save the session so things like messages get saved.
    $request->getSession()->save();
    $response->prepare($request);
    // Make sure to trigger kernel events.
    $this->kernel->terminate($request, $response);
    $response->send();
  }

  /**
   * Get application acting years.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform.
   *
   * @return array
   *   Years for acting_year field.
   */
  public static function getApplicationActingYears(Webform $webform): array {
    $yearsType = $webform->getThirdPartySetting('grants_metadata', 'applicationActingYearsType') ?? 'fixed';
    $yearsCount = $webform->getThirdPartySetting('grants_metadata', 'applicationActingYearsNextCount');

    $actingYearOptions = [];
    $currentYear = (int) date("Y");

    // Fixed years.
    $applicationActingYears = $webform->getThirdPartySetting('grants_metadata', 'applicationActingYears');
    if ($yearsType === 'fixed' && $applicationActingYears) {
      $actingYearOptions = array_combine($applicationActingYears, $applicationActingYears);
    }
    // Current year + x following years.
    elseif ($yearsType === 'current_and_next_x_years') {
      for ($i = 0; $i <= $yearsCount; $i++) {
        $actingYearOptions[$currentYear + $i] = $currentYear + $i;
      }
    }
    // Following years only.
    elseif ($yearsType === 'next_x_years') {
      for ($i = 1; $i <= $yearsCount; $i++) {
        $actingYearOptions[$currentYear + $i] = $currentYear + $i;
      }
    }
    // Fallback behaviour - Current + 2 years.
    else {
      for ($i = 0; $i <= 2; $i++) {
        $actingYearOptions[$currentYear + $i] = $currentYear + $i;
      }
    }

    return $actingYearOptions;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Exception
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {
    if ($this->isRedirect) {
      return;
    }
    $tOpts = ['context' => 'grants_handler'];

    $roles = $this->currentUser->getRoles();

    if (!in_array('helsinkiprofiili', $roles)) {
      return;
    }

    $form['#disable_inline_form_error_messages'] = TRUE;

    $this->alterFormNavigation($form, $form_state, $webform_submission);

    $form['#webform_submission'] = $webform_submission;

    $this->setFromThirdPartySettings($webform_submission->getWebform());

    // If submission has applicant type set, ie we're editing submission
    // use that, if not then get selected from profile.
    // we know that.
    $submissionData = $this->massageFormValuesFromWebform($webform_submission);

    $form_state->setValue('applicant_type', $submissionData["hakijan_tiedot"]["applicantType"]);
    $form["elements"]["applicant_type"]["#value"] = $submissionData["hakijan_tiedot"]["applicantType"];
    $form["elements"]["1_hakijan_tiedot"]["applicant_type"]["#value"] = $submissionData["hakijan_tiedot"]["applicantType"];

    // If we have webform summation field present (agreed location)
    if (isset($form["elements"]['avustukset_summa']) && $form["elements"]['avustukset_summa']) {
      // Then we calculate tota sum.
      $subventionsTotalAmount = 0;
      if (isset($submissionData["subventions"]) && is_array($submissionData["subventions"])) {
        foreach ($submissionData["subventions"] as $sub) {
          $subventionsTotalAmount += self::convertToFloat($sub['amount']);
        }
      }

      /*
       * And set the value to form. This allows the fields to be visible on
       * initial form load.
       */
      $form["elements"]["avustukset_summa"]["#default_value"] = $subventionsTotalAmount;
      $form_state->setValue('avustukset_summa', $subventionsTotalAmount);
    }

    // Process summation fields.
    foreach ($form['elements'] as $element) {
      if (!isset($element['#type'])) {
        continue;
      }

      if ($element['#type'] !== 'grants_webform_summation_field') {
        continue;
      }

      $subventionType = $element['#subvention_type'] ?? NULL;
      $elementKey = $element['#webform_key'];
      $elementTotal = 0;
      if (!$subventionType) {
        continue;
      }

      if (isset($submissionData["subventions"]) && is_array($submissionData["subventions"])) {
        foreach ($submissionData["subventions"] as $sub) {
          if ($sub['subventionType'] == $subventionType) {
            $elementTotal = self::convertToFloat($sub['amount']);
            break;
          }
        }
      }

      $form["elements"][$elementKey]["#default_value"] = $elementTotal;
      $form_state->setValue($elementKey, $elementTotal);
    }

    $form["elements"]["2_avustustiedot"]["avustuksen_tiedot"]["acting_year"]["#options"] = $this->applicationActingYears;

    $dataIntegrityStatus = '';

    if ($this->applicationNumber) {
      $dataIntegrityStatus = $this->applicationDataService->validateDataIntegrity(
        $submissionData,
        $this->applicationNumber,
        $submissionData['metadata']['saveid'] ?? '');

      if ($dataIntegrityStatus != 'OK') {
        $form['#disabled'] = TRUE;
        $this->messenger()
          ->addWarning($this->t('Your data is safe, but not all the
information in your application has been updated yet. Please wait a
moment and reload the page.',
            [],
            $tOpts));
      }

      $webform = $webform_submission->getWebform();
      $breakingChanges = ApplicationHelpers::hasBreakingChangesInNewerVersion($webform);

      if ($breakingChanges && $submissionData['status'] === 'RECEIVED') {
        $form['#disabled'] = TRUE;
        $this->messenger()
          ->addWarning(
            $this->t('Application form has changed. You cannot do any further edits.',
              [],
              $tOpts));
      }

      $locked = $this->formLockService->isApplicationFormLocked($this->applicationNumber);
      if ($locked) {
        $form['#disabled'] = TRUE;
        $this->messenger()
          ->addWarning(
            $this->t('This application is being modified by other person
            currently, you cannot do any modifications while the application
            is locked for them.',
              [],
              $tOpts));
      }
      else {
        $this->formLockService->createOrRefreshApplicationLock($this->applicationNumber);
      }
    }
    // This will remove rebuild action
    // in practice this will allow redirect after processing DRAFT statuses.
    if (isset($form['actions']['draft']['#submit']) && is_array($form['actions']['draft']['#submit'])) {
      WebformArrayHelper::removeValue($form['actions']['draft']['#submit'], '::rebuild');
    }

    // It's possible to edit sent application, until handler
    // has changed status from RECEIVED.
    //
    // Drafts should be able to edited, unless the webform has changed,
    // eg: editing draft ouside application period is ok, unless the underlying
    // webform has changed.
    //
    if (!$this->applicationStatusService->isSubmissionChangesAllowed($webform_submission)) {
      $status = $this->applicationStatusService->getWebformStatus($webform_submission->getWebform());
      $errorMsg = '';
      switch ($status) {
        case 'archived':
          $errorMsg = $this->t('The application form has changed, make a new application.');
          $form['#disabled'] = TRUE;
          break;

        default:
          // We show integrity error earlier, so suppress error here.
          if ($dataIntegrityStatus == 'OK') {
            $errorMsg = $this->t('The application is being processed. The application cannot be edited or submitted.');
          }

          $form['actions']['submit']['#disabled'] = TRUE;
          break;
      }

      if ($errorMsg != '') {
        $this->messenger()
          ->addError($errorMsg);
      }
    }

    $all_current_errors = $this->grantsFormNavigationHelper->getAllErrors($webform_submission);
    $errors = [];

    // Loop through errors.
    foreach ($all_current_errors as $pageName => $page) {
      // Loop through errors in one page.
      foreach ($page as $errorKey => $error) {
        // Some errors are built like errorName][errorSelectValue.
        // These variables separate the array keys in them.
        $errorName = strtok($errorKey, ']');
        $errorSelectValue = substr($errorKey, strpos($errorKey, '[') + 1);
        $valuePath = explode('][', $errorKey);
        $isMultiValue = in_array('_item_', $valuePath);

        if (isset($form['elements'][$pageName][$errorName])) {
          $form['elements'][$pageName][$errorName]['#attributes']['class'][] = 'has-error';
        }
        else {
          foreach ($form['elements'][$pageName] as $fieldName => $element) {
            if (!str_starts_with($fieldName, '#')) {
              if ($isMultiValue) {
                NestedArray::setValue($errors, [
                  ...$valuePath,
                  'class',
                ], 'has-errors');
                NestedArray::setValue($errors, [
                  ...$valuePath,
                  'label',
                ], $error);
              }
              elseif (isset($form['elements'][$pageName][$fieldName][$errorName]['#webform_composite_elements'][$errorSelectValue])) {
                $errors[$errorName]['class'] = 'has-errors';
                $errors[$errorName]['label'] = $error;
                $errors[$errorName]['errors'][$errorSelectValue] = [
                  'class' => 'has-errors',
                  'label' => $error,
                ];
              }
              elseif (isset($form['elements'][$pageName][$fieldName][$errorName])) {
                $form['elements'][$pageName][$fieldName][$errorName]['#attributes']['class'][] = 'has-error';
                $form['elements'][$pageName][$fieldName][$errorName]['#attributes']['error_label'] = $error;
              }
              else {
                // Check if there is field sets with given field.
                foreach ($form['elements'][$pageName] as $pageElementValue) {
                  if (!is_array($pageElementValue)) {
                    continue;
                  }

                  foreach ($pageElementValue as $subKey => $subElement) {
                    if (!is_array($subElement) || ($subElement['#type'] ?? NULL) !== 'fieldset') {
                      continue;
                    }

                    $pathToFieldSet = $this->findKeyPath($form, $subKey);
                    if ($pathToFieldSet && isset($subElement[$errorName])) {
                      $pathToErrorElement = [
                        ...$pathToFieldSet,
                        $errorName,
                        '#attributes',
                      ];

                      $elementClasses = NestedArray::getValue($form, [
                        ...$pathToErrorElement,
                        'class',
                      ]);

                      $elementClasses[] = 'has-error';

                      NestedArray::setValue($form, [
                        ...$pathToErrorElement,
                        'class',
                      ], $elementClasses);

                      NestedArray::setValue($form, [
                        ...$pathToFieldSet,
                        '#attributes',
                        'error_label',
                      ], $error);
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    GrantsErrorStorage::setErrors($errors);

    if (
      $this->grantsFormNavigationHelper->getCurrentPage($webform_submission) == 'webform_preview' &&
      count($this->grantsFormNavigationHelper->getUnvisitedPages($webform_submission)) > 0
    ) {
      $form['actions']['submit']['#disabled'] = TRUE;
    }
  }

  /**
   * Alter navigation elements on forms.
   *
   * @param array $form
   *   Form in question.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Forms state.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The submission.
   *
   * @throws \Exception
   */
  public function alterFormNavigation(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // Log the current page.
    // This is null on initial form load but navigationhelper handles that.
    $current_page = $webform_submission->getCurrentPage();
    $webform = $webform_submission->getWebform();
    // Actions to perform if there are pages.
    if ($webform->hasWizardPages()) {
      $validations = [
        '::validateForm',
        '::noValidate',
        // '::draft',
      ];
      // Allow forward access to all but the confirmation page.
      foreach ($form_state->get('pages') as $page_key => $page) {
        // Allow user to access all but the confirmation page.
        if ($page_key != 'webform_confirmation') {
          $form['pages'][$page_key]['#access'] = TRUE;
          $form['pages'][$page_key]['#validate'] = $validations;
        }
      }
      // Set our loggers to the draft update if it is set.
      if (isset($form['actions']['draft'])) {
        // Add a logger to the next validators.
        $form['actions']['draft']['#validate'] = $validations;
      }
      // Set our loggers to the previous update if it is set.
      if (isset($form['actions']['wizard_prev'])) {
        // Add a logger to the next validators.
        $form['actions']['wizard_prev']['#validate'] = $validations;
      }
      // Add a custom validator to the final submit.
      // $form['actions']['submit']['#validate'][] =
      // 'grants_handler_submission_validation';
      // Log the page visit.
      $visited = $this->grantsFormNavigationHelper->hasVisitedPage($webform_submission, $current_page);
      // Log the page if it has not been visited before.
      if (!$visited) {
        $this->grantsFormNavigationHelper->logPageVisit($webform_submission, $current_page);
      }

      // If there's errors on the form (any page), disable form submit.
      $all_current_errors = $this->grantsFormNavigationHelper->getAllErrors($webform_submission);
      if (is_array($all_current_errors) && !Helpers::emptyRecursive($all_current_errors)) {
        $form["actions"]["submit"]['#disabled'] = TRUE;
      }
    }
  }

  /**
   * Get form submit type from form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface|null $form_state
   *   Form state.
   *
   * @return \Drupal\grants_handler\ApplicationSubmitType|null
   *   Form submit type if there's one.
   */
  public function getSubmitType(?FormStateInterface $form_state): ApplicationSubmitType|null {
    if (!$this->submitType) {
      $triggeringElement = $form_state->getTriggeringElement();
      if (isset($triggeringElement['#submit']) && is_string($triggeringElement['#submit'][0])) {
        $this->submitType = match($triggeringElement['#submit'][0]) {
          '::submit' => ApplicationSubmitType::SUBMIT,
          '::submitForm' => ApplicationSubmitType::SUBMIT_DRAFT,
          // Other options can be ::next, ::previous, etc.
          default => NULL,
        };
      }
    }

    return $this->submitType;
  }

  /**
   * Method to figure out if formUpdate should be false/true?
   *
   * The thing is that the Avustus2 is not very smart about when it fetches
   * data from ATV. Initial import from ATV MUST have fromUpdate FALSE, and
   * any subsequent update will have to have it as TRUE. The application status
   * handling makes this possibly very complicated, hence separate method
   * figuring it out.
   *
   * @return bool
   *   Set form update value either TRUE / FALSE
   */
  private function getFormUpdate(): bool {
    $applicationNumber = !empty($this->applicationNumber) ? $this->applicationNumber : $this->submittedFormData["application_number"] ?? '';
    $newStatus = $this->submittedFormData["status"];
    $oldStatus = '';

    if ($applicationNumber != '') {
      // Get document from ATV.
      try {
        $document = $this->applicationGetterService->getAtvDocument($applicationNumber);
        $oldStatus = $document->getStatus();
      }
      catch (TempStoreException | AtvDocumentNotFoundException | AtvFailedToConnectException | GuzzleException $e) {
        // If block has comment, sonarcloud likes it?
      }
    }

    $applicationStatuses = $this->applicationStatusService->getApplicationStatuses();

    // If new status is submitted, ie save to Avus2..
    if ($newStatus == $applicationStatuses['SUBMITTED']) {
      // ..and if application is not yet in Avus2, form update needs to be FALSE
      // or we get error updating nonexistent application
      if ($oldStatus == $applicationStatuses['DRAFT']) {
        return FALSE;
      }
      // also, if this is new application but put directly to submitted mode,
      // we need to have update also FALSE.
      elseif ($oldStatus == '') {
        return FALSE;
      }
      // In all other cases we can have update as TRUE since we want to
      // actually update data in Avus2 & ATV.
      else {
        return TRUE;
      }
    }

    // If new status is DRAFT, we don't really care about this value since
    // these are not uploaded to Avus2 just put it to false in case of some
    // other things need this.
    if ($newStatus == $applicationStatuses['DRAFT']) {
      return FALSE;
    }

    // In other statuses and situations we can just return true bc we want to
    // actually update data.
    return TRUE;
  }

  /**
   * Save logged errors to webform state.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Submission object.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param array $form
   *   Form render array.
   *
   * @return array|null
   *   All current errors.
   */
  public function validate(
    WebformSubmissionInterface $webform_submission,
    FormStateInterface $form_state,
    array &$form,
  ): ?array {
    try {
      // Validate form.
      parent::validateForm($form, $form_state, $webform_submission);
      // Log current errors.
      $current_errors = $this->grantsFormNavigationHelper->logPageErrors($webform_submission, $form_state);
    }
    catch (\Exception $e) {
      $current_errors = [];
    }
    return $current_errors;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function validateForm(
    array &$form,
    FormStateInterface $form_state,
    WebformSubmissionInterface $webform_submission,
  ): void {
    $tOpts = ['context' => 'grants_handler'];

    // These need to be set here to the handler object, bc we do the saving to
    // ATV in postSave and in that method these are not available.
    // and the triggering element is pivotal in figuring if we're
    // saving draft or not.
    $submitType = $this->getSubmitType($form_state);
    // Form values are needed for parsing attachment in postSave.
    $this->formTemp = $form;
    $this->formStateTemp = $form_state;
    // Does these need to be done in validate??
    // maybe the submittedData is even not required?
    $this->submittedFormData = $this->massageFormValuesFromWebform($webform_submission);

    // Calculate totals for checking.
    $this->setTotals();

    // Merge form sender data from handler.
    try {
      $this->submittedFormData = array_merge(
        $this->submittedFormData,
        $this->applicationDataService->parseSenderDetails());
    }
    catch (ApplicationException $e) {
    }

    $this->submittedFormData['applicant_type'] = $form_state
      ->getValue('applicant_type');

    foreach ($this->submittedFormData["myonnetty_avustus"] as $key => $value) {
      $this->submittedFormData["myonnetty_avustus"][$key]['issuerName'] =
        $value['issuer_name'];
      unset($this->submittedFormData["myonnetty_avustus"][$key]['issuer_name']);
    }
    if ($this->submittedFormData["haettu_avustus_tieto"]) {
      foreach ($this->submittedFormData["haettu_avustus_tieto"] as $key => $value) {
        $this->submittedFormData["haettu_avustus_tieto"][$key]['issuerName'] =
          $value['issuer_name'];
        unset($this->submittedFormData["haettu_avustus_tieto"][$key]['issuer_name']);
      }
    }

    if ($this->submittedFormData['email']) {
      $form_state->setValue('email', mb_strtolower($this->submittedFormData['email']));
    }

    // Set form timestamp to current time.
    // apparently this is always set to latest submission.
    $dt = new \DateTime();
    $dt->setTimezone(new \DateTimeZone('Europe/Helsinki'));
    $this->submittedFormData['form_timestamp'] = $dt->format('Y-m-d\TH:i:s');

    // New application.
    if (empty($this->submittedFormData['application_number'])) {
      $this->submittedFormData['form_timestamp_created'] = $dt->format('Y-m-d\TH:i:s');
    }

    // Get regdate from profile data and format it for Avustus2
    // This data is immutable for end user so safe to this way.
    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    $grantsProfile = $this->grantsProfileService->getGrantsProfileContent($selectedCompany);

    if (isset($grantsProfile["registrationDate"])) {
      $regDate = new DrupalDateTime($grantsProfile["registrationDate"], 'Europe/Helsinki');
      $this->submittedFormData["registration_date"] = $regDate->format('Y-m-d\TH:i:s');
    }

    // Set form update value based on new & old status + Avus2 logic.
    $this->submittedFormData["form_update"] = $this->getFormUpdate();

    // Parse 3rd party settings from webform.
    $this->setFromThirdPartySettings($webform_submission->getWebform());

    // Figure out status for this application.
    $this->newStatus = $this->applicationStatusService->getNewStatus(
      $submitType,
      $this->submittedFormData,
      $webform_submission
    );
    // Set status for data.
    $this->submittedFormData['status'] = $this->newStatus;

    // Application submitted.
    if ($this->applicationStatusService->getNewStatusHeader() == $this->applicationStatusService->getApplicationStatuses()['SUBMITTED']) {
      $this->submittedFormData['form_timestamp_submitted'] = $dt->format('Y-m-d\TH:i:s');
    }
    $this->validate($webform_submission, $form_state, $form);
    $all_errors = $this->grantsFormNavigationHelper->getAllErrors($webform_submission);

    if ($submitType == ApplicationSubmitType::SUBMIT && ($all_errors === NULL || Helpers::emptyRecursive($all_errors))) {
      $applicationData = $this->applicationDataService->webformToTypedData(
        $this->submittedFormData);

      $violations = $this->applicationValidator->validateApplication(
        $applicationData,
        $form_state,
        $webform_submission
      );

      $allPagesVisited = count(
        $this->grantsFormNavigationHelper->getUnvisitedPages($webform_submission)
      ) === 0;

      if ($allPagesVisited && $violations->count() === 0) {
        // If we have no violations clear all errors.
        $form_state->clearErrors();
        $this->grantsFormNavigationHelper->deleteSubmissionLogs($webform_submission, GrantsHandlerNavigationHelper::ERROR_OPERATION);
      }
      if (!$allPagesVisited) {
        $form_state->setErrorByName('unvisited-pages', $this->t('You must visit all pages in the form before you can submit the application.', [], $tOpts));
      }
      if ($violations->count() > 0) {
        // If we HAVE errors, then refresh them from the.
        $this->messenger()
          ->addError($this->t('The application cannot be submitted because not all
mandatory questions have been answered. Return to the application form and fill in
at least those questions and fields that are marked with an asterisk (*). You can
submit the application only after you have provided all the necessary information.', [], $tOpts));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission): void {
    // If for some reason we don't have application number at this point.
    if (!isset($this->applicationNumber)) {
      // But if one is coming from form (hidden field)
      if (isset($this->submittedFormData['application_number'])) {
        // Use it.
        $this->applicationNumber = $this->submittedFormData['application_number'];
      }
      else {
        // But if we have saved webform earlier, we can get the application
        // number from submission serial.
        if ($webform_submission->id()) {
          $this->applicationNumber = ApplicationHelpers::createApplicationNumber($webform_submission);
        }
        // Hopefully we never reach here, but there should be additional checks
        // for application number to exists.
        // and it's no biggie since we can always get it from the method above
        // as long as we have our submission object.
      }
    }

    // These need to be set here to the handler object, bc we do the saving to
    // ATV in postSave and in that method these are not available.
    // and the triggering element is pivotal in figuring if we're
    // saving draft or not.
    $this->submitType = $this->getSubmitType($form_state);
    // Form values are needed for parsing attachment in postSave.
    $this->formTemp = $form;
    $this->formStateTemp = $form_state;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\grants_handler\GrantsException
   */
  public function preSave(WebformSubmissionInterface $webform_submission) {
    // don't save ip address.
    $webform_submission->setRemoteAddr('');

    if (empty($this->submittedFormData)) {
      // Submission data is not saved in storage controller,
      // so save data here for later usage.
      $this->submittedFormData = $this->massageFormValuesFromWebform($webform_submission);
    }

    // If for some reason applicant type is not present, make sure it gets
    // added otherwise validation fails.
    if (!isset($this->submittedFormData['applicant_type'])) {
      $this->submittedFormData['applicant_type'] = $this->grantsProfileService->getApplicantType();
    }

    if (!isset($this->applicationNumber) || $this->applicationNumber == '') {
      // We are getting custom serialized settings from notes field here
      // as we need to check if we actually use the serial number of submission
      // or figure out new application number.
      // submissionObjectFromApplicationNumber@ApplicationHandler sets already
      // a correct serial id from ATV document. But
      // initApplication@ApplicationHandler needs a new unused application id.
      $skipCheck = WebformSubmissionNotesHelper::getValue(
        $webform_submission,
        'skip_available_number_check',
      );

      if ($skipCheck === TRUE) {
        $this->applicationNumber = ApplicationHelpers::createApplicationNumber($webform_submission);
      }
      else {
        try {
          $this->applicationNumber = ApplicationHelpers::getAvailableApplicationNumber($webform_submission);
        }
        catch (\Throwable $e) {
          throw new GrantsException('Getting application number failed.');
        }
      }
    }
  }

  /**
   * PostSave handling when submit trigger is ::submit.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function postSaveSubmit(WebformSubmissionInterface $webform_submission): void {
    // Submit is trigger when exiting from confirmation page.
    // Parse attachments to data structure.
    try {
      $this->attachmentHandler->deleteRemovedAttachmentsFromAtv($this->formStateTemp, $this->submittedFormData);
      $this->attachmentHandler->parseAttachments(
        $this->formTemp,
        $this->submittedFormData,
        $this->applicationNumber
      );
    }
    catch (\Exception $e) {
      $this->getLogger('grants_handler')->error($e->getMessage());
    }

    // Try to update status only if it's allowed.
    if ($this->applicationStatusService->canSubmissionBeSubmitted($webform_submission, NULL)) {
      $this->submittedFormData['status'] = $this->applicationStatusService->getApplicationStatuses()['SUBMITTED'];
    }
  }

  /**
   * PostSave handling when submit trigger is ::submitForm.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function postSaveSubmitForm(): void {
    $this->attachmentHandler->deleteRemovedAttachmentsFromAtv($this->formStateTemp, $this->submittedFormData);
    $applicationData = NULL;
    // submitForm is triggering element when saving as draft.
    // Parse attachments to data structure.
    try {
      $this->attachmentHandler->deleteRemovedAttachmentsFromAtv($this->formStateTemp, $this->submittedFormData);
      $this->attachmentHandler->parseAttachments(
        $this->formTemp,
        $this->submittedFormData,
        $this->applicationNumber
      );
      $fileIds = $this->attachmentHandler->getAttachmentFileIds();

      $fileIdsArray = [];
      foreach ($fileIds as $fileId) {
        $fileIdsArray[$fileId] = ['upload' => TRUE];
      }

      // While files should be removed in the parsing process,
      // let's make sure no file entities get left behind.
      $this->attachmentRemover->removeGrantAttachments(
        $fileIds,
        $fileIdsArray,
        $this->applicationNumber,
        $this->isDebug(),
        0
      );
    }
    catch (\Throwable $e) {
    }
    try {
      $applicationData = $this->applicationDataService->webformToTypedData(
        $this->submittedFormData
      );
    }
    catch (ReadOnlyException $e) {
      // Fix here: https://helsinkisolutionoffice.atlassian.net/browse/AU-545
    }
    $redirectUrl = Url::fromRoute(
      'grants_oma_asiointi.front',
      [
        'attributes' => [
          'data-drupal-selector' => 'application-saving-failed-link',
        ],
      ]
    );
    try {
      $applicationUploadStatus = $this->applicationUploaderService->handleApplicationUploadToAtv(
        $applicationData,
        $this->applicationNumber,
        $this->submittedFormData
      );
      if ($applicationUploadStatus) {
        $this->messenger()
          ->addStatus(
            $this->t(
              'Grant application (<span id="saved-application-number">@number</span>) saved as DRAFT',
              [
                '@number' => $this->applicationNumber,
              ]
            )
          );

        $redirectUrl = Url::fromRoute('grants_oma_asiointi.front');
      }
      else {
        $redirectUrl = Url::fromRoute(
          'grants_oma_asiointi.front',
          [
            'attributes' => [
              'data-drupal-selector' => 'application-saving-failed-link',
            ],
          ]
        );

        $this->messenger()
          ->addError(
            $this->t(
              'Grant application (<span id="saved-application-number">@number</span>) saving failed. Please contact support.',
              [
                '@number' => $this->applicationNumber,
              ]
            ),
            TRUE
          );
      }
    }
    catch (\Exception | GuzzleException $e) {
      $this->getLogger('grants_handler')
        ->error('Error uploading application: @error', ['@error' => $e->getMessage()]);
    }

    $this->formLockService->releaseApplicationLock($this->applicationNumber);

    $redirectResponse = new RedirectResponse($redirectUrl->toString());

    $this->applicationUploaderService->clearCache($this->applicationNumber);

    $redirectResponse->send();
  }

  /**
   * Handles application number during postSave method.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission.
   */
  public function postSaveHandleApplicationNumber(WebformSubmissionInterface $webform_submission): void {
    if (!isset($this->submittedFormData['application_number']) || $this->submittedFormData['application_number'] == '') {
      if (!isset($this->applicationNumber) || $this->applicationNumber == '') {
        $this->applicationNumber = ApplicationHelpers::createApplicationNumber($webform_submission);
      }
      if (isset($this->applicationTypeID) || $this->applicationTypeID == '') {
        $this->submittedFormData['application_type_id'] = $this->applicationTypeID;
      }
      if (isset($this->applicationType) || $this->applicationType == '') {
        $this->submittedFormData['application_type'] = $this->applicationType;
      }
      if (isset($this->applicationNumber) || $this->applicationNumber == '') {
        $this->submittedFormData['application_number'] = $this->applicationNumber;
      }
    }
    else {
      $this->applicationNumber = $this->submittedFormData['application_number'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE): void {
    // Invalidate cache for this submission.
    $this->entityTypeManager
      ->getViewBuilder($webform_submission->getWebform()->getEntityTypeId())
      ->resetCache([$webform_submission]);

    if (empty($this->submittedFormData)) {
      return;
    }

    $this->postSaveHandleApplicationNumber($webform_submission);

    if ($this->submitType) {
      // Let other parts of the system to react to the form submit.
      $this->eventDispatcher->dispatch(new ApplicationSubmitEvent($this->submitType));
    }

    try {
      // If triggering element is either draft save or proper one,
      // we want to parse attachments from form.
      switch ($this->submitType) {
        case ApplicationSubmitType::SUBMIT_DRAFT:
          $this->postSaveSubmitForm();
          break;

        case ApplicationSubmitType::SUBMIT:
          $this->postSaveSubmit($webform_submission);
          break;
      }
    }
    catch (GuzzleException $e) {
      $this->messenger->addError($this->t('Error saving application. please contact support.'));
      $this->getLogger('grants_handler')
        ->error('Error saving application: @error', ['@error' => $e->getMessage()]);

      \Sentry\captureException($e);
    }
  }

  /**
   * This method is called when form SUBMIT button is created.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Submission object.
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function confirmForm(
    array &$form,
    FormStateInterface $form_state,
    WebformSubmissionInterface $webform_submission,
  ): void {
    try {
      // Get new status from method that figures that out.
      $this->submittedFormData['status'] = $this->applicationStatusService->getNewStatus(
        $this->submitType,
        $this->submittedFormData,
        $webform_submission
      );

      // Build application data for sending to Avus2.
      $applicationData = $this->applicationDataService->webformToTypedData(
        $this->submittedFormData
      );

      // Upload application via integration.
      $applicationUploadStatus = $this->applicationUploaderService->handleApplicationUploadViaIntegration(
        $applicationData,
        $this->applicationNumber,
        $this->submittedFormData
      );

      // If application uploaded succesfully.
      if ($applicationUploadStatus) {
        // Erase other messages.
        $this->messenger()->deleteAll();
        // Show message.
        $this->messenger()
          ->addStatus(
            $this->t(
              'Grant application (<span id="saved-application-number">@number</span>) saved.',
              [
                '@number' => $this->applicationNumber,
              ]
            )
          );
        // And redirect user to completion page.
        $form_state->setRedirect(
          'grants_handler.completion',
          ['submission_id' => $this->applicationNumber],
          [
            'attributes' => [
              'data-drupal-selector' => 'application-saved-successfully-link',
            ],
          ]
        );
      }
      else {
        $this->messenger()
          ->addError(
            $this->t(
              'Grant application (@number) saving failed. Error has been logged.',
              [
                '@number' => $this->applicationNumber,
              ]
            )
          );
      }
    }
    catch (\Exception $e) {
      $this->getLogger('grants_handler')
        ->error('Error: %error', ['%error' => $e->getMessage()]);

      $this->messenger()
        ->addError(
          $this->t(
            'Grant application (@number) saving failed. Error has been logged.',
            [
              '@number' => $this->applicationNumber,
            ]
          )
        );

      \Sentry\captureException($e);
    }
  }

  /**
   * Helper to find out if we're debugging or not.
   *
   * @return bool
   *   If debug mode is on or not.
   */
  public function isDebug(): bool {
    return !empty($this->configuration['debug']);
  }

  /**
   * Display the invoked plugin method to end user.
   *
   * @param string $method_name
   *   The invoked method name.
   * @param string $context1
   *   Additional parameter passed to the invoked method name.
   */
  public function debug($method_name, $context1 = NULL) {
    $tOpts = ['context' => 'grants_handler'];

    if (!empty($this->configuration['debug'])) {
      $t_args = [
        '@id' => $this->getHandlerId(),
        '@class_name' => get_class($this),
        '@method_name' => $method_name,
        '@context1' => $context1,
      ];
      $this->messenger()
        ->addWarning($this->t('Invoked @id: @class_name:@method_name @context1', $t_args, $tOpts), TRUE);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['debug'] = (bool) $form_state->getValue('debug');
  }

  /**
   * Cleans up non-array values from array structure.
   *
   * This is due to some configuration error with messages/statuses/events
   * that I'm not able to find.
   *
   * @param array|null $value
   *   Array we need to flatten.
   *
   * @return array
   *   Fixed array
   */
  public static function cleanUpArrayValues(mixed $value): array {
    $retval = [];
    if (is_array($value)) {
      foreach ($value as $v) {
        if (is_array($v)) {
          $retval[] = $v;
        }
      }
    }
    return $retval;
  }

  /**
   * Parse things from form 3rd party settings to this application.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *   Webform used.
   */
  protected function setFromThirdPartySettings(Webform $webform): void {
    // Make sure we have application type id set.
    if (!isset($this->applicationTypeID) || $this->applicationTypeID == '') {
      if (isset($this->submittedFormData['application_type_id'])) {
        $this->applicationTypeID = $this->submittedFormData['application_type_id'];
      }
      else {
        $this->applicationTypeID = $webform
          ->getThirdPartySetting('grants_metadata', 'applicationTypeID');
        $this->submittedFormData['application_type_id'] = $this->applicationTypeID;
      }
    }

    // Make sure we have our application type set.
    if (!isset($this->applicationType) || $this->applicationType == '') {
      if (isset($this->submittedFormData['application_type']) && $this->submittedFormData['application_type'] != '') {
        $this->applicationTypeID = $this->submittedFormData['application_type'];
      }
      else {
        $this->applicationType = $webform
          ->getThirdPartySetting('grants_metadata', 'applicationType');
        $this->submittedFormData['application_type'] = $this->applicationType;
      }
    }
    if (!isset($this->applicationActingYears) || empty($this->applicationActingYears)) {
      $this->applicationActingYears = self::getApplicationActingYears($webform);
    }
  }

  /**
   * Get path for key.
   *
   * Recursively searches for a specific key in a multidimensional array and
   * retrieves its path.
   *
   * @param array $array
   *   The multidimensional array to search in.
   * @param mixed $keyToFind
   *   The key to search for.
   * @param array $currentPath
   *   [optional] The current path within the array (used for recursion).
   *
   * @return array|null
   *   The path to the key if found, or null if the key is not found.
   */
  public function findKeyPath(array $array, mixed $keyToFind, array $currentPath = []): ?array {
    foreach ($array as $key => $value) {
      // Update the current path with the current key.
      $path = [...$currentPath, $key];

      if ($key === $keyToFind) {
        // Return the path if the key is found.
        return $path;
      }
      elseif (is_array($value)) {
        // Recursively search in nested arrays.
        $result = $this->findKeyPath($value, $keyToFind, $path);
        if ($result !== NULL) {
          return $result;
        }
      }
    }

    // Key was not found.
    return NULL;
  }

}
