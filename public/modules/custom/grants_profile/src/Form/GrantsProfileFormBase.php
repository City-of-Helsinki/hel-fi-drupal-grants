<?php

namespace Drupal\grants_profile\Form;

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\ComplexDataDefinitionBase;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\file\Element\ManagedFile;
use Drupal\grants_profile\GrantsProfileException;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use PHP_IBAN\IBAN;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Provides a Grants Profile form base.
 */
abstract class GrantsProfileFormBase extends FormBase {

  use StringTranslationTrait;

  /**
   * Drupal\Core\TypedData\TypedDataManager definition.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected TypedDataManager $typedDataManager;

  /**
   * Get session.
   *
   * @var \Symfony\Component\HttpFoundation\Session\Session
   */
  protected Session $session;

  /**
   * Access to grants profile services.
   *
   * @var \Drupal\grants_profile\GrantsProfileService
   */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Variable for translation context.
   *
   * @var array|string[] Translation context for class
   */
  protected array $tOpts = ['context' => 'grants_profile'];

  /**
   * Constructs a new GrantsProfileForm object.
   *
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   Data manager.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Profile.
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   Session data.
   */
  public function __construct(TypedDataManager $typed_data_manager,
                              GrantsProfileService $grantsProfileService,
                              Session $session) {
    $this->typedDataManager = $typed_data_manager;
    $this->grantsProfileService = $grantsProfileService;
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('grants_profile.service'),
      $container->get('session'),
    );
  }

  /**
   * Ajax callback for removing item from form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   */
  public static function removeOne(array &$form, FormStateInterface $formState) : void {
    $triggeringElement = $formState->getTriggeringElement();
    [
      $fieldName,
      $deltaToRemove,
    ] = explode('--', $triggeringElement['#name']);

    $fieldValue = $formState->getValue($fieldName);

    if ($fieldName == 'bankAccountWrapper' && $fieldValue[$deltaToRemove]['bank']['confirmationFileName']) {
      // Save file href and remove it after submit.
      $attachmentsToRemove = $formState->get('attachments_to_remove');
      if (!$attachmentsToRemove) {
        $attachmentsToRemove = [];
      }

      $fileHref = self::parseFileHref($fieldValue[$deltaToRemove]['bank'], $formState);
      if ($fileHref) {
        $attachmentsToRemove[] = $fileHref;
        $formState->set('attachments_to_remove', $attachmentsToRemove);
      }
    }

    // Remove item from items.
    unset($fieldValue[$deltaToRemove]);
    $formState->setValue($fieldName, $fieldValue);
    $formState->setRebuild();

  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Forms state.
   */
  public function addOne(array &$form, FormStateInterface $formState) : void {
    $triggeringElement = $formState->getTriggeringElement();
    [
      $fieldName,
    ] = explode('--', $triggeringElement['#name']);

    $formState
      ->setValue('newItem', $fieldName);

    // Since our buildForm() method relies on the value of 'num_names' to
    // generate 'name' form elements, we have to tell the form to rebuild. If we
    // don't do this, the form builder will not call buildForm().
    $formState
      ->setRebuild();
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Forms state.
   *
   * @return mixed
   *   Form element for replacing.
   */
  public static function addmoreCallback(array &$form, FormStateInterface $formState): mixed {

    $triggeringElement = $formState->getTriggeringElement();
    [
      $fieldName,
    ] = explode('--', $triggeringElement['#name']);

    return $form[$fieldName];
  }

  /**
   * Check the cases where we're working on Form Actions.
   *
   * @param array $triggeringElement
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The Form State.
   *
   * @return bool
   *   Is this form action
   */
  public function validateFormActions(array $triggeringElement, FormStateInterface &$formState): bool {
    $returnValue = FALSE;

    if ($triggeringElement["#id"] !== 'edit-actions-submit') {
      $returnValue = TRUE;
    }

    // Clear validation errors if we are adding or removing fields.
    if (
      strpos($triggeringElement['#id'], 'deletebutton') !== FALSE ||
      strpos($triggeringElement['#id'], 'add') !== FALSE ||
      strpos($triggeringElement['#id'], 'remove') !== FALSE
    ) {
      $formState->clearErrors();
    }

    // In case of upload, we want ignore all except failed upload.
    if (strpos($triggeringElement["#id"], 'upload-button') !== FALSE) {
      $errors = $formState->getErrors();
      $parents = $triggeringElement['#parents'];
      array_pop($parents);
      $parentsKey = implode('][', $parents);
      $errorsForUpload = [];

      // Found a file upload error. Remove all and the add the correct error.
      if (isset($errors[$parentsKey])) {
        $errorsForUpload[$parentsKey] = $errors[$parentsKey];
        $formValues = $formState->getValues();
        // Reset failing file to default.
        NestedArray::setValue($formValues, $parents, '');
        $formState->setValues($formValues);
        $formState->setRebuild();
      }

      $formState->clearErrors();

      // Set file upload errors to state.
      if (!empty($errorsForUpload)) {
        foreach ($errorsForUpload as $errorKey => $errorValue) {
          $formState->setErrorByName($errorKey, $errorValue);
        }
      }
      $returnValue = TRUE;

    }

    return $returnValue;
  }

  /**
   * Delete given attachment from ATV.
   *
   * @param string $file
   *   Href of the file.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   *
   * @return bool
   *   Result of deletion.
   */
  public static function deleteAttachmentFile(string $file, FormStateInterface $formState): bool {
    $storage = $formState->getStorage();
    /** @var \Drupal\helfi_atv\AtvDocument $grantsProfileDocument */
    $grantsProfileDocument = $storage['profileDocument'];
    /** @var \Drupal\helfi_atv\AtvService $atvService */
    $atvService = \Drupal::service('helfi_atv.atv_service');
    /** @var \Drupal\helfi_audit_log\AuditLogService $auditLogService */
    $auditLogService = \Drupal::service('helfi_audit_log.audit_log');

    try {
      // Delete attachment by href.
      $deleteResult = $atvService->deleteAttachmentByUrl($file);

      $message = [
        "operation" => "GRANTS_APPLICATION_ATTACHMENT_DELETE",
        "status" => "SUCCESS",
        "target" => [
          "id" => $grantsProfileDocument->getId(),
          "type" => $grantsProfileDocument->getType(),
          "name" => $grantsProfileDocument->getTransactionId(),
        ],
      ];
      $auditLogService->dispatchEvent($message);

    }
    catch (\Throwable $e) {

      $deleteResult = FALSE;

      $message = [
        "operation" => "GRANTS_APPLICATION_ATTACHMENT_DELETE",
        "status" => "FAILURE",
        "target" => [
          "id" => $grantsProfileDocument->getId(),
          "type" => $grantsProfileDocument->getType(),
          "name" => $grantsProfileDocument->getTransactionId(),
        ],
      ];
      $auditLogService->dispatchEvent($message);

      \Drupal::logger('grants_profile')
        ->error('Attachment deletion failed, @error', ['@error' => $e->getMessage()]);
    }

    return $deleteResult;
  }

  /**
   * Compare two account numbers.
   *
   * @param string $account1
   *   The 1st account number.
   * @param string $account2
   *   The 2nd account number.
   *
   * @return bool
   *   Are account numbers equal
   */
  protected static function accountsAreEqual(?string $account1, ?string $account2): bool {
    if (!$account1 || !$account2) {
      return FALSE;
    }
    $account1Cleaned = strtoupper(str_replace(' ', '', $account1));
    $account2Cleaned = strtoupper(str_replace(' ', '', $account2));
    return $account1Cleaned == $account2Cleaned;
  }

  /**
   * Validate & upload file attachment.
   *
   * @param array $element
   *   Element tobe validated.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $form
   *   The form.
   */
  public static function validateUpload(array &$element, FormStateInterface $formState, array &$form) {

    $storage = $formState->getStorage();
    $grantsProfileDocument = $storage['profileDocument'];

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');

    $triggeringElement = $formState->getTriggeringElement();

    // Figure out paths on form & element.
    $valueParents = $element["#parents"];

    if (str_contains($triggeringElement["#name"], 'confirmationFile_upload_button')) {
      foreach ($element["#files"] as $file) {
        try {

          // Upload attachment to document.
          $attachmentResponse = $grantsProfileService->uploadAttachment(
            $grantsProfileDocument->getId(),
            $file->getFilename(),
            $file
          );

          $storage['confirmationFiles'][$valueParents[1]] = $attachmentResponse;

        }
        catch (GrantsProfileException $e) {
          // Set error to form.
          $formState->setError($element, 'File upload failed, error has been logged.');
          // Log error.
          \Drupal::logger('grants_profile')->error($e->getMessage());

          $element['#value'] = NULL;
          $element['#default_value'] = NULL;
          unset($element['fids']);

          $element['#files'] = $element['#files'] ?? [];
          foreach ($element['#files'] as $delta => $file2) {
            unset($element['file_' . $delta]);
          }

          unset($element['#label_for']);

        }
      }
    }

    $formState->setStorage($storage);
  }

  /**
   * Validate bank accounts.
   *
   * To reduce complexity.
   *
   * @param array $values
   *   Form values.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   */
  protected function validateBankAccounts(array $values, FormStateInterface $formState): void {
    if (!array_key_exists('bankAccountWrapper', $values)) {
      return;
    }
    if (empty($values["bankAccountWrapper"])) {
      $elementName = 'bankAccountWrapper]';
      $formState->setErrorByName($elementName, $this->t('You must add one bank account', [], $this->tOpts));
      return;
    }

    $validIbans = $this->validateBankAccountWrapper($values["bankAccountWrapper"], $formState);

    if (count($validIbans) !== count(array_unique($validIbans))) {
      $elementName = 'bankAccountWrapper]';
      $formState->setErrorByName(
        $elementName,
        $this->t('You can add an account only once.', [], $this->tOpts)
      );
    }
  }

  /**
   * Go through the Bank Account Wrapper array, see if each account is valid.
   *
   * @param array $bankAccountWrapper
   *   The Bank Account Wrapper itself.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The form state.
   *
   * @return array
   *   The valid ibans
   */
  private function validateBankAccountWrapper(array $bankAccountWrapper, FormStateInterface $formState): array {
    $validIbans = [];
    foreach ($bankAccountWrapper as $key => $accountData) {
      $elementName = 'bankAccountWrapper][' . $key . '][bank][bankAccount';

      if (!empty($accountData['bankAccount'])) {
        $myIban = new IBAN($accountData['bankAccount']);
        $ibanValid = FALSE;

        if (!preg_match('/^[A-Za-z0-9]*$/', $accountData['bankAccount'])) {
          $formState->setErrorByName($elementName,
            $this->t('Not valid Finnish IBAN: @iban', ['@iban' => $accountData["bankAccount"]], $this->tOpts)
          );
          continue;
        }

        if ($myIban->Verify() && $myIban->Country() == 'FI') {
          // If so, return true.
          $ibanValid = TRUE;
          $validIbans[] = $myIban->MachineFormat();
        }
        if (!$ibanValid) {
          $formState->setErrorByName($elementName,
            $this->t('Not valid Finnish IBAN: @iban', ['@iban' => $accountData["bankAccount"]], $this->tOpts)
          );
        }
      }
      else {
        $formState->setErrorByName($elementName, $this->t('You must enter valid Finnish iban',
          [], $this->tOpts));
      }
      if (empty($accountData["confirmationFileName"]) && empty($accountData["confirmationFile"]['fids'])) {
        $elementName = 'bankAccountWrapper][' . $key . '][bank][confirmationFile';
        $formState->setErrorByName(
          $elementName,
          $this->t(
            'You must add confirmation file for account: @iban',
            ['@iban' => $accountData["bankAccount"]],
            $this->tOpts
          )
        );
      }
    }
    return $validIbans;
  }

  /**
   * Add address bits in separate method to improve readability.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $helsinkiProfileContent
   *   Helsinki profile user info for versions of bank account that need it.
   * @param array|null $bankAccounts
   *   Current bank accounts in grants profile.
   * @param string|null $newItem
   *   New item.
   * @param array|null $strings
   *   Array containing alternative texts for bank account bits.
   */
  protected function addBankAccountBits(
    array &$form,
    FormStateInterface $formState,
    array $helsinkiProfileContent,
    ?array $bankAccounts,
    ?string $newItem,
    array|null $strings = [],
  ) {

    $form['bankAccountWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Bank account numbers', [], $this->tOpts),
      '#prefix' => '<div id="bankaccount-wrapper">',
      '#suffix' => '</div>',
    ];

    // Add a container for errors since the errors don't
    // show up the webform_section element.
    $form = $this->addErrorElement('bankAccountWrapper', $form);

    if (!$bankAccounts) {
      $bankAccounts = [];
    }

    $sessionHash = Crypt::hashBase64($this->session->getId());

    $uploadLocation = 'private://grants_profile/' . $sessionHash;
    $maxFileSizeInBytes = (1024 * 1024) * 20;

    $bankAccountValues = $formState->getValue('bankAccountWrapper') ?? $bankAccounts;

    unset($bankAccountValues['actions']);
    $delta = -1;
    /*
     * Handle edge case where user inputs same account number twice with
     * the help of this variable.
     */
    $nonEditableIbans = [];
    foreach ($bankAccountValues as $delta => $bankAccount) {
      if (array_key_exists('bank', $bankAccount) && !empty($bankAccount['bank'])) {
        $temp = $bankAccount['bank'];
        unset($bankAccountValues[$delta]['bank']);
        $bankAccountValues[$delta] = array_merge($bankAccountValues[$delta], $temp);
        $bankAccount = $bankAccount['bank'];
      }

      // Make sure we have proper UUID as address id.
      $this->ensureBankAccountIdExists($bankAccount);
      $nonEditable = FALSE;
      foreach ($bankAccounts as $profileAccount) {
        if (!self::accountsAreEqual($bankAccount['bankAccount'], $profileAccount['bankAccount'])) {
          continue;
        }
        $cleanedAccount = strtoupper(str_replace(' ', '', $profileAccount['bankAccount']));
        // Check for doubles.
        if (in_array($cleanedAccount, $nonEditableIbans)) {
          break;
        }
        $nonEditable = TRUE;
        $nonEditableIbans[] = $cleanedAccount;
        break;
      }
      $attributes = [];
      $attributes['readonly'] = $nonEditable;

      $form['bankAccountWrapper'][$delta]['bank'] = $this->buildBankArray(
        $helsinkiProfileContent,
        $delta,
        [
          'maxSize' => $maxFileSizeInBytes,
          'uploadLocation' => $uploadLocation,
          'confFilename' => $bankAccount['confirmationFileName'] ?? $bankAccount['confirmationFile'],
        ],
        $attributes,
        $strings,
        $nonEditable,
        $bankAccount['bankAccount'],
        FALSE,
        $bankAccount['bank_account_id'] ?? '',
      );
    }

    if ($newItem == 'bankAccountWrapper') {
      $nextDelta = $delta + 1;

      $form['bankAccountWrapper'][$nextDelta]['bank'] = $this->buildBankArray(
        $helsinkiProfileContent,
        $nextDelta,
        [
          'maxSize' => $maxFileSizeInBytes,
          'uploadLocation' => $uploadLocation,
          'confFilename' => NULL,
        ],
        NULL,
        $strings,
        FALSE,
        '',
        TRUE,
        $bankAccount['bank_account_id'] ?? '',
      );
      $formState->setValue('newItem', NULL);
    }

    $form['bankAccountWrapper']['actions']['add_bankaccount'] = [
      '#type' => 'submit',
      '#value' => $this
        ->t('Add bank account', [], $this->tOpts),
      '#is_supplementary' => TRUE,
      '#icon_left' => 'plus-circle',
      '#name' => 'bankAccountWrapper--1',
      '#submit' => [
        '::addOne',
      ],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'bankaccount-wrapper',
        'disable-refocus' => TRUE,
      ],
      '#prefix' => '<div class="profile-add-more"">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Validates that the bank account has an ID.
   *
   * @param array $bankAccount
   *   Bank account data array.
   */
  private function ensureBankAccountIdExists(array &$bankAccount) {
    // Make sure we have proper UUID as address id.
    if (!isset($bankAccount['bank_account_id']) ||
      !$this->grantsProfileService->isValidUuid($bankAccount['bank_account_id'])
      ) {
      $bankAccount['bank_account_id'] = Uuid::uuid4()->toString();
    }
  }

  /**
   * Builder function for bank account arrays for profile form.
   *
   * @param array $helsinkiProfileContent
   *   Owner info from profile.
   * @param int $delta
   *   Current Delta.
   * @param array $file
   *   Array with file-related info.
   * @param array|null $attributes
   *   Attributes for the bank account text field.
   * @param array|null $strings
   *   Array containing alternative texts for bank account bits.
   * @param bool $nonEditable
   *   Is the bank account text field noneditable.
   * @param string|null $bankAccount
   *   Bank account number.
   * @param bool $newDelta
   *   If this is a new Bank Array or old one.
   * @param string $bankAccountId
   *   Bank account id, if it exists already.
   *
   * @return array
   *   Bank account element in array form.
   */
  private function buildBankArray(
    array $helsinkiProfileContent,
    int $delta,
    array $file,
    array|null $attributes = NULL,
    array|null $strings = [],
    bool $nonEditable = FALSE,
    string|null $bankAccount = NULL,
    bool $newDelta = FALSE,
    string $bankAccountId = '',
  ): array {
    $ownerValues = FALSE;
    if (!empty($helsinkiProfileContent)) {
      $ownerName = $helsinkiProfileContent['myProfile']['verifiedPersonalInformation']['firstName'] .
      ' ' . $helsinkiProfileContent['myProfile']['verifiedPersonalInformation']['lastName'];
      $ownerSSN = $helsinkiProfileContent['myProfile']['verifiedPersonalInformation']['nationalIdentificationNumber'];
      $ownerValues = TRUE;
    }

    $maxFileSizeInBytes = $file['maxSize'];
    $uploadLocation = $file['uploadLocation'];
    $confFilename = $file['confFilename'];
    $fields = [
      '#type' => 'fieldset',
      '#title' => $strings['#title'] ?? $this->t('Bank account', [], $this->tOpts),
      '#description_display' => 'before',
      '#description' => $strings['#description'] ?? '',
      'bankAccount' => [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Finnish bank account number in IBAN format', [], $this->tOpts),
        '#default_value' => $bankAccount,
        '#readonly' => $nonEditable,
        '#attributes' => $attributes,
      ],
    ];
    if ($ownerValues) {
      $ownerNameArray = [
        '#title' => $this->t('Bank account owner name', [], $this->tOpts),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      $ownerSSNArray = [
        '#title' => $this->t('Bank account owner SSN', [], $this->tOpts),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#attributes' => ['readonly' => 'readonly'],
      ];
      if ($newDelta) {
        $ownerNameArray['#value'] = $ownerName;
        $ownerSSNArray['#value'] = $ownerSSN;
      }
      else {
        $ownerNameArray['#default_value'] = $ownerName;
        $ownerSSNArray['#default_value'] = $ownerSSN;
      }
      $fields['ownerName'] = $ownerNameArray;
      $fields['ownerSsn'] = $ownerSSNArray;
    }
    $fields['confirmationFileName'] = [
      '#title' => $this->t('Confirmation file', [], $this->tOpts),
      '#default_value' => $confFilename,
      '#type' => ($confFilename != NULL ? 'textfield' : 'hidden'),
      '#attributes' => ['readonly' => 'readonly'],
    ];
    $fields['confirmationFile'] = [
      '#type' => 'managed_file',
      '#required' => TRUE,
      '#process' => [[self::class, 'processFileElement']],
      '#title' => $this->t("Attach a certificate of account access: bank's notification
of the account owner or a copy of a bank statement.", [], $this->tOpts),
      '#multiple' => FALSE,
      '#uri_scheme' => 'private',
      '#file_extensions' => 'doc,docx,gif,jpg,jpeg,pdf,png,ppt,pptx,rtf,
        txt,xls,xlsx,zip',
      '#upload_validators' => [
        'file_validate_extensions' => [
          'doc docx gif jpg jpeg pdf png ppt pptx rtf txt xls xlsx zip',
        ],
        'file_validate_size' => [$maxFileSizeInBytes],
      ],
      '#element_validate' => ['\Drupal\grants_profile\Form\GrantsProfileFormBase::validateUpload'],
      '#upload_location' => $uploadLocation,
      '#sanitize' => TRUE,
      '#description' => $this->t('Only one file.<br>Limit: 20 MB.<br>
Allowed file types: doc, docx, gif, jpg, jpeg, pdf, png, ppt, pptx,
rtf, txt, xls, xlsx, zip.', [], $this->tOpts),
      '#access' => $confFilename == NULL || is_array($confFilename),
    ];
    $fields['bank_account_id'] = [
      '#type' => 'hidden',
      '#value' => $bankAccountId,
    ];
    $fields['deleteButton'] = [
      '#icon_left' => 'trash',
      '#type' => 'submit',
      '#value' => $this->t('Delete', [], $this->tOpts),
      '#name' => 'bankAccountWrapper--' . $delta,
      '#submit' => [
        '::removeOne',
      ],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'bankaccount-wrapper',
        'disable-refocus' => TRUE,
      ],
    ];

    return $fields;
  }

  /**
   * Go through the three Wrappers and get profile content from them.
   *
   * @param array $values
   *   Form Values.
   * @param array $grantsProfileContent
   *   Grants Profile Content.
   *
   * @return void
   *   returns void
   */
  protected function profileContentFromWrappers(array &$values, array &$grantsProfileContent) : void {
    if (array_key_exists('addressWrapper', $values)) {
      unset($values["addressWrapper"]["actions"]);
      $grantsProfileContent['addresses'] = $values["addressWrapper"];
    }

    if (array_key_exists('officialWrapper', $values)) {
      unset($values["officialWrapper"]["actions"]);
      $grantsProfileContent['officials'] = $values["officialWrapper"];
    }

    if (array_key_exists('bankAccountWrapper', $values)) {
      unset($values["bankAccountWrapper"]["actions"]);
      $grantsProfileContent['bankAccounts'] = $values["bankAccountWrapper"];
    }

    if (array_key_exists('phoneWrapper', $values)) {
      $grantsProfileContent['phone_number'] = $values["phoneWrapper"]['phone_number'];
    }

    if (array_key_exists('emailWrapper', $values)) {
      $grantsProfileContent['email'] = $values["emailWrapper"]['email'];
    }

    if (array_key_exists('companyNameWrapper', $values)) {
      $grantsProfileContent['companyName'] = $values["companyNameWrapper"]["companyName"];
    }

  }

  /**
   * Returns the user's grants profile document from ATV.
   *
   * @return \Drupal\helfi_atv\AtvDocument|bool
   *   The ATV Document
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  protected function getGrantsProfileDocument() : AtvDocument|bool {
    $selectedRoleData = $this->grantsProfileService->getSelectedRoleData();

    // Load grants profile.
    $grantsProfile = $this->grantsProfileService->getGrantsProfile($selectedRoleData, TRUE);

    // If no profile exist.
    if ($grantsProfile == NULL) {
      // Create one and.
      $grantsProfile = $this->grantsProfileService->createNewProfile($selectedRoleData);
    }

    return $grantsProfile;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Attach pattern error library.
    $form['#attached']['library'][] = 'grants_profile/pattern_error';

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save own information', [], $this->tOpts),
    ];

    $form['actions']['submit_cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button', 'hds-button--secondary']],
      '#weight' => 10,
      '#limit_validation_errors' => [],
      '#submit' => ['Drupal\grants_profile\Form\GrantsProfileFormBase::formCancelCallback'],
    ];

    $form['status_messages'] = [
      '#type' => 'status_messages',
    ];

    $form['profileform_info_wrapper'] = [
      '#type' => 'webform_section',
      '#title' => '&nbsp;',
    ];

    $form['profileform_info_wrapper']['profileform_info'] = [
      '#theme' => 'hds_notification',
      '#type' => 'notification',
      '#class' => '',
      '#label' => $this->t('Fields marked with an asterisk * are required information.', [], $this->tOpts),
      '#body' => $this->t('Fill all fields first and save in the end.', [], $this->tOpts),
    ];

    $form['newItem'] = [
      '#type' => 'hidden',
      '#value' => NULL,
    ];

    $form['updatelink']['link'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get updated information', [], $this->tOpts),
      '#name' => 'refresh_profile',
      '#submit' => [[$this, 'profileDataRefreshSubmitHandler']],
      '#ajax' => [
        'callback' => [$this, 'profileDataRefreshAjaxCallback'],
        'wrapper' => 'form',
        'disable-refocus' => TRUE,
      ],
      '#limit_validation_errors' => [],
    ];

    $form['#tree'] = TRUE;

    $form['actions']['submit']['#submit'][] = 'Drupal\grants_profile\Form\GrantsProfileFormBase::removeAttachments';
    $form['actions']['submit']['#submit'][] = [$this, 'submitForm'];

    return $form;
  }

  /**
   * Remove attachments submit handler.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   */
  public static function removeAttachments(array &$form, FormStateInterface $formState): void {
    $attachments = $formState->get('attachments_to_remove');
    if (!$attachments) {
      return;
    }

    foreach ($attachments as $fileHref) {
      self::deleteAttachmentFile($fileHref, $formState);
    }
  }

  /**
   * Parse file url from the field structure.
   *
   * @param array $field
   *   Field data.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   *
   * @return string
   *   File href.
   */
  public static function parseFileHref(array $field, FormStateInterface $formState): string {
    $storage = $formState->getStorage();
    /** @var \Drupal\helfi_atv\AtvDocument $grantsProfileDocument */
    $grantsProfileDocument = $storage['profileDocument'];

    // Try to look for a attachment from document.
    $attachmentToDelete = array_filter(
      $grantsProfileDocument->getAttachments(),
      function ($item) use ($field) {
        if ($item['filename'] == $field['confirmationFileName']) {
          return TRUE;
        }
        return FALSE;
      });

    $attachmentToDelete = reset($attachmentToDelete);
    $href = '';

    // If attachment is found.
    if ($attachmentToDelete) {
      // Get href for deletion.
      $href = $attachmentToDelete['href'];
    }
    else {
      // Attachment not found, so we must have just added one.
      $triggeringElement = $formState->getTriggeringElement();
      // Get delta for deleting.
      [$fieldName, $delta] = explode('--', $triggeringElement["#name"]);
      unset($fieldName);
      // Upload function has added the attachment information earlier.
      if ($justAddedElement = $storage["confirmationFiles"][(int) $delta]) {
        // So we can just grab that href and delete it from ATV.
        $href = $justAddedElement["href"];
      }
    }
    return $href;
  }

  /**
   * Cancel form edit callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public static function formCancelCallback(array &$form, FormStateInterface &$form_state) {

    $storage = $form_state->getStorage();
    /** @var \Drupal\helfi_atv\AtvDocument $profileDocument */
    $profileDocument = $storage['profileDocument'];

    if ($profileDocument->getTransactionId() == GrantsProfileService::DOCUMENT_TRANSACTION_ID_INITIAL) {
      /** @var \Drupal\helfi_atv\AtvService $atvService */
      $atvService = \Drupal::service('helfi_atv.atv_service');

      try {
        $atvService->deleteDocument($profileDocument);
        \Drupal::messenger()->addStatus(t('Grants profile creation canceled.', [], ['context' => 'grants_profile']));
      }
      catch (\Throwable $e) {
        \Drupal::logger('grants_profile')
          ->error('Grants Profile deletion failed. Profile Document ID: @id',
            ['@id' => $profileDocument->getId()]);
      }
      $route_name = 'grants_mandate.mandateform';
    }
    else {
      $route_name = 'grants_profile.show';
    }
    $form_state->setRedirect($route_name);
  }

  /**
   * Render API callback: Expands the managed_file element type.
   *
   * Remove #limit_validation fields, as these cause dynamically added
   * fields to dissapear.
   */
  public static function processFileElement($element, &$form_state, &$complete_form) {
    ManagedFile::processManagedFile($element, $form_state, $complete_form);
    unset($element['upload_button']['#limit_validation_errors']);
    unset($element['remove_button']['#limit_validation_errors']);
    return $element;
  }

  /**
   * Clean up form values.
   *
   * @param array $values
   *   Form values.
   * @param array $input
   *   User input.
   * @param array $storage
   *   Form storage.
   *
   * @return array
   *   Cleaned up Form Values.
   */
  protected function cleanUpFormValues(array $values, array $input, array $storage): array {
    // Clean up empty values from form values.
    foreach ($values as $key => $value) {
      if (!is_array($value)) {
        continue;
      }

      $values[$key] = $input[$key] ?? [];
      $values[$key]['actions'] = NULL;
      unset($values[$key]['actions']);
      if (!array_key_exists($key, $input)) {
        continue;
      }
      foreach ($value as $key2 => $value2) {

        if ($key == 'addressWrapper') {
          $values[$key][$key2]['address_id'] = $value2["address_id"] ?? Uuid::uuid4()
            ->toString();
          $temp = $value2['address'] ?? [];
          unset($values[$key][$key2]['address']);
          $values[$key][$key2] = array_merge($values[$key][$key2], $temp);
          continue;
        }
        elseif ($key == 'officialWrapper') {
          $values[$key][$key2]['official_id'] = $value2["official_id"] ?? Uuid::uuid4()
            ->toString();
          $temp = $value2['official'] ?? [];
          unset($values[$key][$key2]['official']);
          $values[$key][$key2] = array_merge($values[$key][$key2], $temp);
          continue;
        }
        elseif ($key != 'bankAccountWrapper') {
          continue;
        }
        // Set value without fieldset.
        $values[$key][$key2] = $value2['bank'] ?? NULL;

        // If we have added a new account,
        // then we need to create id for it.
        $value2['bank']['bank_account_id'] = $value2['bank']['bank_account_id'] ?? '';
        if (!$this->grantsProfileService->isValidUuid($value2['bank']['bank_account_id'])) {
          $values[$key][$key2]['bank_account_id'] = Uuid::uuid4()
            ->toString();
        }

        $values[$key][$key2]['confirmationFileName'] = $storage['confirmationFiles'][$key2]['filename'] ??
          $values[$key][$key2]['confirmationFileName'] ??
          NULL;

        $values[$key][$key2]['confirmationFile'] = $values[$key][$key2]['confirmationFileName'] ??
          $storage['confirmationFiles'][$key2]['filename'] ??
          $values[$key][$key2]['confirmationFile'] ??
          NULL;

      }
    }
    return $values;
  }

  /**
   * Handle found violations on a form.
   *
   * @param \Drupal\Core\TypedData\ComplexDataDefinitionBase $grantsProfileDefinition
   *   The Profile definition.
   * @param array $grantsProfileContent
   *   The actual contents of the profile.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form State.
   * @param array $form
   *   Form Object.
   * @param array $addressArrayKeys
   *   Address object keys for placing validation errors.
   * @param array $officialArrayKeys
   *   Officials object keys for placing validation errors.
   * @param array $bankAccountArrayKeys
   *   Bank Accounts object keys for placing validation errors.
   *
   * @return void
   *   Returns void.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  protected function handleViolations(
    ComplexDataDefinitionBase $grantsProfileDefinition,
    array $grantsProfileContent,
    FormStateInterface &$formState,
    array $form,
    array $addressArrayKeys,
    array $officialArrayKeys,
    array $bankAccountArrayKeys
  ) {
    // Create data object.
    $grantsProfileData = $this->typedDataManager->create($grantsProfileDefinition);
    $grantsProfileData->setValue($grantsProfileContent);
    // Validate inserted data.
    $violations = $grantsProfileData->validate();
    // If there's violations in data.
    if ($violations->count() == 0) {
      // Move addressData object to form_state storage.
      $freshStorageState = $formState->getStorage();
      $freshStorageState['grantsProfileData'] = $grantsProfileData;
      $formState->setStorage($freshStorageState);
      return;
    }
    /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
    foreach ($violations as $violation) {
      // Print errors by form item name.
      $propertyPathArray = explode('.', $violation->getPropertyPath());
      $errorElement = NULL;
      $errorMessage = NULL;

      $propertyPath = '';

      switch ($propertyPathArray[0]) {
        case 'addresses':
          if (count($propertyPathArray) == 1) {
            $errorElement = $form["addressWrapper"]['error_container'];
            $errorMessage = $this->t('You must add one address', [], $this->tOpts);
            break;
          }
          $propertyPath = 'addressWrapper][' . $addressArrayKeys[$propertyPathArray[1]] .
            '][address][' . $propertyPathArray[2];
          break;

        case 'bankAccounts':
          if (count($propertyPathArray) == 1) {
            $errorElement = $form["bankAccountWrapper"]['error_container'];
            $errorMessage = $this->t('You must add one bank account');
            break;
          }
          $propertyPath = 'bankAccountWrapper][' . $bankAccountArrayKeys[$propertyPathArray[1]] .
            '][bank][' . $propertyPathArray[2];
          break;

        case 'businessPurpose':
          $propertyPath = 'businessPurposeWrapper][businessPurpose';
          break;

        case 'companyNameShort':
          $propertyPath = 'companyNameShortWrapper][companyNameShort';
          break;

        case 'companyHomePage':
          $propertyPath = 'companyHomePageWrapper][companyHomePage';
          break;

        case 'email':
          $propertyPath = 'emailWrapper][email';
          break;

        case 'foundingYear':
          $propertyPath = 'foundingYearWrapper][foundingYear';
          break;

        case 'officials':
          if (count($propertyPathArray) > 1) {
            $propertyPath = 'officialWrapper][' . $officialArrayKeys[$propertyPathArray[1]] .
              '][official][' . $propertyPathArray[2];
          }
          break;

        default:
          $propertyPath = $violation->getPropertyPath();
      }

      if ($errorElement) {
        $formState->setError(
          $errorElement,
          $errorMessage
        );
        continue;
      }
      $formState->setErrorByName(
        $propertyPath,
        $violation->getMessage()
      );
    }

  }

  /**
   * The addErrorElement method.
   *
   * This method adds an "error_container" to a
   * desired $parentElement form element.
   *
   * @param string $parentElement
   *   The parent element we want to add an error element to.
   * @param array $form
   *   An associative array containing the structure of the form.
   *
   * @return array
   *   The passed in form with an added error container.
   */
  protected function addErrorElement(string $parentElement, array $form): array {
    $form[$parentElement]['error_container'] = [
      '#type' => 'fieldset',
      '#attributes' => [
        'class' => [
          'inline-error-message',
        ],
      ],
    ];
    return $form;
  }

  /**
   * Profile data refresh ajax callback.
   */
  public function profileDataRefreshAjaxCallback(array $form) {
    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand('form', $form));
    return $response;
  }

}
