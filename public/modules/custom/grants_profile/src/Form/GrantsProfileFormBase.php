<?php

namespace Drupal\grants_profile\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\file\Element\ManagedFile;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use GuzzleHttp\Exception\GuzzleException;
use PHP_IBAN\IBAN;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

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
  private array $tOpts = ['context' => 'grants_profile'];

  /**
   * Constructs a new GrantsProfileForm object.
   *
   * @param \Drupal\Core\TypedData\TypedDataManager $typed_data_manager
   *   Data manager.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Profile.
   */
  public function __construct(TypedDataManager $typed_data_manager, GrantsProfileService $grantsProfileService) {
    $this->typedDataManager = $typed_data_manager;
    $this->grantsProfileService = $grantsProfileService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GrantsProfileFormBase|static {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('grants_profile.service')
    );
  }

  /**
   * Helper method so we can have consistent dialog options.
   *
   * @return string[]
   *   An array of jQuery UI elements to pass on to our dialog form.
   */
  public static function getDataDialogOptions(): array {
    return [
      'width' => '33%',
    ];
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
  public function validateFormActions(array $triggeringElement, FormStateInterface &$formState) {
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
   * @param array $fieldValue
   *   Field contents.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state object.
   *
   * @return bool
   *   Result of deletion.
   */
  public static function deleteAttachmentFile(array $fieldValue, FormStateInterface $formState): bool {
    $fieldToRemove = $fieldValue;

    $storage = $formState->getStorage();
    /** @var \Drupal\helfi_atv\AtvDocument $grantsProfileDocument */
    $grantsProfileDocument = $storage['profileDocument'];

    // Try to look for a attachment from document.
    $attachmentToDelete = array_filter(
      $grantsProfileDocument->getAttachments(),
      function ($item) use ($fieldToRemove) {
        if ($item['filename'] == $fieldToRemove['confirmationFileName']) {
          return TRUE;
        }
        return FALSE;
      });

    $attachmentToDelete = reset($attachmentToDelete);
    $hrefToDelete = NULL;

    // If attachment is found.
    if ($attachmentToDelete) {
      // Get href for deletion.
      $hrefToDelete = $attachmentToDelete['href'];
    }
    else {
      // Attachment not found, so we must have just added one.
      $triggeringElement = $formState->getTriggeringElement();
      // Get delta for deleting.
      $name = explode('--', $triggeringElement["#name"]);
      $delta = $name[1];
      // Upload function has added the attachment information earlier.
      if ($justAddedElement = $storage["confirmationFiles"][(int) $delta]) {
        // So we can just grab that href and delete it from ATV.
        $hrefToDelete = $justAddedElement["href"];
      }
    }

    if (!$hrefToDelete) {
      return FALSE;
    }

    /** @var \Drupal\helfi_atv\AtvService $atvService */
    $atvService = \Drupal::service('helfi_atv.atv_service');
    /** @var \Drupal\helfi_audit_log\AuditLogService $auditLogService */
    $auditLogService = \Drupal::service('helfi_audit_log.audit_log');

    try {
      // Delete attachment by href.
      $deleteResult = $atvService->deleteAttachmentByUrl($hrefToDelete);

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
   * Handle possible errors after form is built.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   *
   * @return array
   *   Updated form.
   */
  public static function afterBuild(array $form, FormStateInterface &$formState): array {

    return $form;
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
  protected static function accountsAreEqual(string $account1, string $account2) {
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

    $triggeringElement = $formState->getTriggeringElement();

    /** @var \Drupal\helfi_atv\AtvService $atvService */
    $atvService = \Drupal::service('helfi_atv.atv_service');

    // Figure out paths on form & element.
    $valueParents = $element["#parents"];

    if (str_contains($triggeringElement["#name"], 'confirmationFile_upload_button')) {
      foreach ($element["#files"] as $file) {
        try {

          // Upload attachment to document.
          $attachmentResponse = $atvService->uploadAttachment(
            $grantsProfileDocument->getId(),
            $file->getFilename(),
            $file
          );

          $storage['confirmationFiles'][$valueParents[1]] = $attachmentResponse;

        }
        catch (AtvDocumentNotFoundException | AtvFailedToConnectException | GuzzleException $e) {
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
  public function validateBankAccounts(array $values, FormStateInterface $formState): void {
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
  private function validateBankAccountWrapper(array $bankAccountWrapper, FormStateInterface $formState) {
    $validIbans = [];
    foreach ($bankAccountWrapper as $key => $accountData) {
      $elementName = 'bankAccountWrapper][' . $key . '][bank][bankAccount';

      if (!empty($accountData['bankAccount'])) {
        $myIban = new IBAN($accountData['bankAccount']);
        $ibanValid = FALSE;

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
   * Ajax callback for removing item from form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   */
  public static function removeOne(array &$form, FormStateInterface $formState) : void {
    $tOpts = ['context' => 'grants_profile'];

    $triggeringElement = $formState->getTriggeringElement();
    [
      $fieldName,
      $deltaToRemove,
    ] = explode('--', $triggeringElement['#name']);

    $fieldValue = $formState->getValue($fieldName);

    if ($fieldName == 'bankAccountWrapper' && $fieldValue[$deltaToRemove]['bank']['confirmationFileName']) {
      $attachmentDeleteResults = self::deleteAttachmentFile($fieldValue[$deltaToRemove]['bank'], $formState);

      if ($attachmentDeleteResults) {
        \Drupal::messenger()
          ->addStatus(t('Bank account & verification attachment deleted.', [], $tOpts));
      }
      else {
        \Drupal::messenger()
          ->addError(t('Attachment deletion failed, error has been logged. Please contact customer support.',
            [], $tOpts));
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
   * Add address bits in separate method to improve readability.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array|null $bankAccounts
   *   Current officials.
   * @param string|null $newItem
   *   New item.
   */
  public function addBankAccountBits(
    array &$form,
    FormStateInterface $formState,
    ?array $helsinkiProfileContent,
    ?array $bankAccounts,
    ?string $newItem
  ) {

    $form['bankAccountWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Bank account numbers', [], $this->tOpts),
      '#prefix' => '<div id="bankaccount-wrapper">',
      '#suffix' => '</div>',
    ];

    if (!$bankAccounts) {
      $bankAccounts = [];
    }

    $sessionHash = sha1(\Drupal::service('session')->getId());
    $uploadLocation = 'private://grants_profile/' . $sessionHash;
    $maxFileSizeInBytes = (1024 * 1024) * 20;

    $bankAccountValues = $formState->getValue('bankAccountWrapper') ?? $bankAccounts;

    unset($bankAccountValues['actions']);
    $delta = -1;
    foreach ($bankAccountValues as $delta => $bankAccount) {
      if (array_key_exists('bank', $bankAccount) && !empty($bankAccount['bank'])) {
        $temp = $bankAccount['bank'];
        unset($bankAccountValues[$delta]['bank']);
        $bankAccountValues[$delta] = array_merge($bankAccountValues[$delta], $temp);
        $bankAccount = $bankAccount['bank'];
      }

      // Make sure we have proper UUID as address id.
      if (!isset($bankAccount['bank_account_id']) ||
        !$this->grantsProfileService->isValidUuid($bankAccount['bank_account_id'])) {
        $bankAccount['bank_account_id'] = Uuid::uuid4()->toString();
      }
      $nonEditable = FALSE;
      foreach ($bankAccounts as $profileAccount) {
        if (isset($bankAccount['bankAccount']) &&
          isset($profileAccount['bankAccount']) &&
          self::accountsAreEqual($bankAccount['bankAccount'],
            $profileAccount['bankAccount'])) {
          $nonEditable = TRUE;
          break;
        }
      }
      $attributes = [];
      $attributes['readonly'] = $nonEditable;

      $form['bankAccountWrapper'][$delta]['bank'] = $this->buildBankArray(
        $delta,
        [
          'maxSize' => $maxFileSizeInBytes,
          'uploadLocation' => $uploadLocation,
          'confFilename' => $bankAccount['confirmationFileName'] ?? $bankAccount['confirmationFile'],
        ],
        $attributes,
        $nonEditable,
        $bankAccount['bankAccount'],
      );
    }

    if ($newItem == 'bankAccountWrapper') {
      $nextDelta = $delta + 1;

      $form['bankAccountWrapper'][$nextDelta]['bank'] = $this->buildBankArray(
        $nextDelta,
        [
          'maxSize' => $maxFileSizeInBytes,
          'uploadLocation' => $uploadLocation,
          'confFilename' => NULL,
        ],
        NULL,
        FALSE,
        TRUE,
        '',
        TRUE,
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
      ],
      '#prefix' => '<div class="profile-add-more"">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Builder function for bank account arrays for profile form.
   *
   * @param array $owner
   *   Owner info from profile.
   * @param int $delta
   *   Current Delta.
   * @param array $file
   *   Array with file-related info.
   * @param array $attributes
   *   Attributes for the bank account text field.
   * @param bool $nonEditable
   *   Is the bank account text field noneditable.
   * @param string $bankAccount
   *   Bank account number.
   * @param bool $newDelta
   *   If this is a new Bank Array or old one.
   *
   * @return array
   *   Bank account element in array form.
   */
  private function buildBankArray(
    array $owner,
    int $delta,
    array $file,
    array|null $attributes = NULL,
    bool $nonEditable = FALSE,
    string $bankAccount = '',
    bool $newDelta = FALSE
  ) {
    $ownerValues = FALSE;
    if (count($owner) > 0) {
      $ownerName = $owner['name'];
      $ownerSSN = $owner['SSN'];
      $ownerValues = TRUE;
    }

    $maxFileSizeInBytes = $file['maxSize'];
    $uploadLocation = $file['uploadLocation'];
    $confFilename = $file['confFilename'];
    $fields = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personal bank account', [], $this->tOpts),
      '#description_display' => 'before',
      '#description' => $this->t('You can only fill in your own bank account information.', [], $this->tOpts),
      'bankAccount' => [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Finnish bank account number in IBAN format', [], $this->tOpts),
        '#default_value' => $bankAccount,
        '#readonly' => $nonEditable,
        '#attributes' => $attributes,
      ],

      'confirmationFileName' => [
        '#title' => $this->t('Confirmation file', [], $this->tOpts),
        '#default_value' => $confFilename,
        '#type' => ($confFilename != NULL ? 'textfield' : 'hidden'),
        '#attributes' => ['readonly' => 'readonly'],
      ],
      'confirmationFile' => [
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
      ],
      'bank_account_id' => [
        '#type' => 'hidden',
      ],
      'deleteButton' => [
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
        ],
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
      $fields['ownerSsn'] = $ownerSSNArray;]
    }

    return $fields;
  }

  /**
   * Parse and report errors in the correct places.
   *
   * @param \Symfony\Component\Validator\ConstraintViolationListInterface $violations
   *   Found violations.
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $addressArrayKeys
   *   Address array keys.
   * @param array $officialArrayKeys
   *   Officials array keys.
   * @param array $bankAccountArrayKeys
   *   Bank account array keys.
   *
   * @return void
   *   Returns nothing
   */
  public function reportValidatedErrors(ConstraintViolationListInterface $violations,
                                         array $form,
                                         FormStateInterface &$formState,
                                         array $addressArrayKeys = [],
                                         array $officialArrayKeys = [],
                                         array $bankAccountArrayKeys = []) {
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
            $errorElement = $form["addressWrapper"];
            $errorMessage = 'You must add one address';
            break;
          }
          $propertyPath = 'addressWrapper][' . $addressArrayKeys[$propertyPathArray[1]] .
            '][address][' . $propertyPathArray[2];
          break;

        case 'bankAccounts':
          if (count($propertyPathArray) == 1) {
            $errorElement = $form["bankAccountWrapper"];
            $errorMessage = 'You must add one bank account';
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
      }
      else {
        $formState->setErrorByName(
          $propertyPath,
          $violation->getMessage()
        );
      }
    }
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
  public function profileContentFromWrappers(array &$values, array &$grantsProfileContent) : void {
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
  public function getGrantsProfile() : AtvDocument|bool {
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

    $form['#tree'] = TRUE;

    return $form;
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
        \Drupal::messenger()->addStatus('Grants profile creation canceled.');
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

}
