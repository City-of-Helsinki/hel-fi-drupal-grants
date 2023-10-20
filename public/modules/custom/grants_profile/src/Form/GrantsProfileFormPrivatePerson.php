<?php

namespace Drupal\grants_profile\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\Url;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\grants_profile\TypedData\Definition\GrantsProfilePrivatePersonDefinition;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Grants Profile form.
 */
class GrantsProfileFormPrivatePerson extends GrantsProfileFormBase {

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
   * Helsinki profile service.
   *
   * @var \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData
   */
  protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData;

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
   *   Profile service.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData
   *   Data for Helsinki Profile.
   */
  public function __construct(
    TypedDataManager $typed_data_manager,
    GrantsProfileService $grantsProfileService,
    HelsinkiProfiiliUserData $helsinkiProfiiliUserData
  ) {
    $this->typedDataManager = $typed_data_manager;
    $this->grantsProfileService = $grantsProfileService;
    $this->helsinkiProfiiliUserData = $helsinkiProfiiliUserData;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GrantsProfileFormPrivatePerson|static {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('grants_profile.service'),
      $container->get('helfi_helsinki_profiili.userdata')
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
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'grants_profile_private_person';
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form = parent::buildForm($form, $form_state);
    $grantsProfile = $this->getGrantsProfile();

    if ($grantsProfile == NULL) {
      return [];
    }

    // Get content from document.
    $grantsProfileContent = $grantsProfile->getContent();

    $helsinkiProfileContent = $this->helsinkiProfiiliUserData->getUserProfileData();

    $storage = $form_state->getStorage();
    $storage['profileDocument'] = $grantsProfile;

    // Use custom theme hook.
    $form['#theme'] = 'own_profile_form_private_person';

    $form['#after_build'] = ['Drupal\grants_profile\Form\GrantsProfileFormPrivatePerson::afterBuild'];

    $newItem = $form_state->getValue('newItem');

    $address = $grantsProfileContent['addresses'][0] ?? NULL;

    // Make sure we have proper UUID as address id.
    if ($address && !$this->grantsProfileService->isValidUuid($address['address_id'])) {
      $address['address_id'] = Uuid::uuid4()->toString();
    }

    $form['addressWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Addresses', [], $this->tOpts),
      '#prefix' => '<div id="addresses-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['addressWrapper']['street'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street address', [], $this->tOpts),
      '#default_value' => $address['street'] ?? '',
      '#required' => TRUE,
    ];
    $form['addressWrapper']['postCode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal code', [], $this->tOpts),
      '#default_value' => $address['postCode'] ?? '',
      '#required' => TRUE,
    ];
    $form['addressWrapper']['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City/town', [], ['context' => 'Profile Address']),
      '#default_value' => $address['city'] ?? '',
      '#required' => TRUE,
    ];
    $form['addressWrapper']['country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country', [], ['context' => 'Profile Address']),
      '#attributes' => ['readonly' => 'readonly'],
      '#default_value' => $address['country'] ?? 'Suomi',
      '#value' => $address['country'] ?? 'Suomi',
    ];
    // We need the delta / id to create delete links in element.
    $form['addressWrapper']['address_id'] = [
      '#type' => 'hidden',
      '#value' => $address['address_id'] ?? '',
    ];

    $form['phoneWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Telephone', [], $this->tOpts),
      '#prefix' => '<div id="phone-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['phoneWrapper']['phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Telephone', [], $this->tOpts),
      '#default_value' => $grantsProfileContent['phone_number'] ?? '',
      '#required' => TRUE,
    ];

    $form['emailWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Email address', [], $this->tOpts),
      '#prefix' => '<div id="email-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['emailWrapper']['email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Email address', [], $this->tOpts),
      '#default_value' => $grantsProfileContent['email'] ?? '',
      '#required' => TRUE,
    ];

    $this->addbankAccountBits(
      $form,
      $form_state,
      $helsinkiProfileContent,
      $grantsProfileContent['bankAccounts'],
      $newItem);
    $profileEditUrl = Url::fromUri(getenv('HELSINKI_PROFIILI_URI'));
    $profileEditUrl->mergeOptions([
      'attributes' => [
        'title' => $this->t('If you want to change the information from Helsinki-profile
you can do that by going to the Helsinki-profile from this link.', [], $this->tOpts),
        'target' => '_blank',
      ],
    ]);
    $editHelsinkiProfileLink = Link::fromTextAndUrl(
      $this->t('Go to Helsinki-profile to edit your information.', [], $this->tOpts),
      $profileEditUrl
    );

    $form['#basic_info'] = [
      '#theme' => 'grants_profile__basic_info__private_person',
      '#myProfile' => $helsinkiProfileContent['myProfile'],
      '#editHelsinkiProfileLink' => $editHelsinkiProfileLink,
    ];

    $form['#profilecontent'] = $grantsProfileContent;
    $form['#helsinkiprofilecontent'] = $helsinkiProfileContent;
    $form_state->setStorage($storage);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {

    $triggeringElement = $formState->getTriggeringElement();

    if ($triggeringElement["#id"] !== 'edit-actions-submit') {

      // Clear validation errors if we are adding or removing fields.
      if (
        strpos($triggeringElement["#id"], 'deletebutton') !== FALSE ||
        strpos($triggeringElement["#id"], 'add') !== FALSE ||
        strpos($triggeringElement["#id"], 'remove') !== FALSE
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
          $userInput = $formState->getUserInput();
          // Reset failing file to default.
          NestedArray::setValue($formValues, $parents, '');
          NestedArray::setValue($userInput, $parents, '');

          $formState->setValues($formValues);
          $formState->setUserInput($userInput);
          $formState->setRebuild();
        }

        $formState->clearErrors();

        // Set file upload errors to state.
        if (!empty($errorsForUpload)) {
          foreach ($errorsForUpload as $errorKey => $errorValue) {
            $formState->setErrorByName($errorKey, $errorValue);
          }
        }
      }

      return;
    }

    $storage = $formState->getStorage();
    /** @var \Drupal\helfi_atv\AtvDocument $grantsProfileDocument */
    $grantsProfileDocument = $storage['profileDocument'];

    if (!$grantsProfileDocument) {
      $this->messenger()->addError($this->t('grantsProfileContent not found!', [], $this->tOpts));
      $formState->setErrorByName(NULL, $this->t('grantsProfileContent not found!', [], $this->tOpts));
      return;
    }

    $grantsProfileContent = $grantsProfileDocument->getContent();

    $values = $formState->getValues();
    $input = $formState->getUserInput();

    if (array_key_exists('addressWrapper', $input)) {
      $values["addressWrapper"] = $input["addressWrapper"];
    }

    if (array_key_exists('bankAccountWrapper', $input)) {
      $bankAccountArrayKeys = array_keys($input["bankAccountWrapper"]);
      $values["bankAccountWrapper"] = $input["bankAccountWrapper"];

      foreach ($input["bankAccountWrapper"] as $key => $accountData) {
        if (!empty($accountData['bank']['bankAccount'])) {
          $myIban = str_replace(' ', '', $accountData['bank']['bankAccount']);
          $values['bankAccountWrapper'][$key]['bank']['bankAccount'] = $myIban;
        }
      }
    }

    $values = $this->cleanUpFormValues($values, $input, $storage);

    // Set clean values to form state.
    $formState->setValues($values);

    if (array_key_exists('addressWrapper', $values)) {
      unset($values["addressWrapper"]["actions"]);
      $grantsProfileContent['addresses'] = $values["addressWrapper"];
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

    $this->validateBankAccounts($values, $formState);

    parent::validateForm($form, $formState);

    $grantsProfileDefinition = GrantsProfilePrivatePersonDefinition::create('grants_profile_private_person');
    // Create data object.
    $grantsProfileData = $this->typedDataManager->create($grantsProfileDefinition);
    $grantsProfileData->setValue($grantsProfileContent);
    // Validate inserted data.
    $violations = $grantsProfileData->validate();
    // If there's violations in data.
    if ($violations->count() != 0) {
      /** @var \Symfony\Component\Validator\ConstraintViolationInterface $violation */
      foreach ($violations as $violation) {
        // Print errors by form item name.
        $propertyPathArray = explode('.', $violation->getPropertyPath());
        $errorElement = NULL;
        $errorMessage = NULL;

        $propertyPath = '';

        if ($propertyPathArray[0] == 'addresses') {
          if (count($propertyPathArray) == 1) {
            $errorElement = $form["addressWrapper"];
            $errorMessage = 'You must add one address';
          }
          else {
            $propertyPath = 'addressWrapper][' . $propertyPathArray[2];
          }
        }
        elseif ($propertyPathArray[0] == 'bankAccounts') {
          if (count($propertyPathArray) == 1) {
            $errorElement = $form["bankAccountWrapper"];
            $errorMessage = 'You must add one bank account';
          }
          else {
            $propertyPath = 'bankAccountWrapper][' . $bankAccountArrayKeys[$propertyPathArray[1]]
              . '][bank][' . $propertyPathArray[2];
          }

        }
        elseif ($propertyPathArray[0] == 'email') {
          $propertyPath = 'emailWrapper][email';
        }
        else {
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
    else {
      // Move addressData object to form_state storage.
      $freshStorageState = $formState->getStorage();
      $freshStorageState['grantsProfileData'] = $grantsProfileData;
      $formState->setStorage($freshStorageState);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {

    $storage = $formState->getStorage();
    if (!isset($storage['grantsProfileData'])) {
      $this->messenger()->addError($this->t('grantsProfileData not found!', [], $this->tOpts));
      return;
    }

    $grantsProfileData = $storage['grantsProfileData'];

    $profileDataArray = $grantsProfileData->toArray();

    try {
      $success = $this->grantsProfileService->saveGrantsProfile($profileDataArray);
    }
    catch (\Throwable $e) {
      $success = FALSE;
      $this->logger('grants_profile')
        ->error('Grants profile saving failed. Error: @error', ['@error' => $e->getMessage()]);
    }

    $applicationSearchLink = Link::createFromRoute(
      $this->t('Application search', [], $this->tOpts),
      'view.application_search_search_api.search_page',
      [],
      [
        'attributes' => [
          'class' => 'bold-link',
        ],
      ]);

    if ($success !== FALSE) {
      $this->messenger()
        ->addStatus(
          $this->t('Your profile information has been saved. You can go to the application via the @link.', [
            '@link' => $applicationSearchLink->toString(),
          ], $this->tOpts));
    }

    $formState->setRedirect('grants_profile.show');
  }

  /**
   * Add address bits in separate method to improve readability.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $helsinkiProfileContent
   *   User profile.
   * @param array|null $bankAccounts
   *   Current officials.
   * @param string|null $newItem
   *   New item.
   */
  public function addBankAccountBits(
    array &$form,
    FormStateInterface $formState,
    array $helsinkiProfileContent,
    ?array $bankAccounts,
    ?string $newItem
  ) {

    $form['bankAccountWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Bank account numbers', [], $this->tOpts),
      '#prefix' => '<div id="bankaccount-wrapper">',
      '#suffix' => '</div>',
    ];

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
          self::accountsAreEqual(
            $bankAccount['bankAccount'],
            $profileAccount['bankAccount']
          )
        ) {
          $nonEditable = TRUE;
          break;
        }
      }
      $attributes = [];
      $attributes['readonly'] = $nonEditable;

      $form['bankAccountWrapper'][$delta]['bank'] = $this->buildBankArray(
        [
          'name' => $helsinkiProfileContent['myProfile']['verifiedPersonalInformation']['firstName'] .
          ' ' . $helsinkiProfileContent['myProfile']['verifiedPersonalInformation']['lastName'],
          'SSN' => $helsinkiProfileContent['myProfile']['verifiedPersonalInformation']['nationalIdentificationNumber'],
        ],
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
        [
          'name' => $helsinkiProfileContent['myProfile']['verifiedPersonalInformation']['firstName']
          . ' ' . $helsinkiProfileContent['myProfile']['verifiedPersonalInformation']['lastName'],
          'SSN' => $helsinkiProfileContent['myProfile']['verifiedPersonalInformation']['nationalIdentificationNumber'],
        ],
        $nextDelta,
        [
          'maxSize' => $maxFileSizeInBytes,
          'uploadLocation' => $uploadLocation,
          'confFilename' => NULL,
        ],
        NULL,
        FALSE,
        '',
        TRUE
      );
      $formState->setValue('newItem', NULL);
    }

    $form['bankAccountWrapper']['actions']['add_bankaccount'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add bank account', [], $this->tOpts),
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
    $ownerName = $owner['name'];
    $ownerSSN = $owner['SSN'];

    $maxFileSizeInBytes = $file['maxSize'];
    $uploadLocation = $file['uploadLocation'];
    $confFilename = $file['confFilename'];

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

    return [
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
      'ownerName' => $ownerNameArray,
      'ownerSsn' => $ownerSSNArray,
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
  public function cleanUpFormValues(array $values, array $input, array $storage): array {
    // Clean up empty values from form values.
    foreach ($values as $key => $value) {
      if (!is_array($value)) {
        continue;
      }
      if ($key == 'addressWrapper' && array_key_exists($key, $input)) {
        $values[$key] = [$value];
        unset($values[$key]['actions']);
        if (empty($value["address_id"])) {
          $values[$key][0]['address_id'] = Uuid::uuid4()
            ->toString();
        }
      }
      elseif ($key == 'bankAccountWrapper' && array_key_exists($key, $input)) {

        $values[$key] = $input[$key];
        unset($values[$key]['actions']);
        foreach ($value as $key2 => $loopItem) {
          // Get item from fieldset.
          $value2 = $loopItem['bank'];
          // Set value without fieldset.
          $values[$key][$key2] = $value2;
          // If we have added a new account,
          // then we need to create id for it.
          if (!array_key_exists('bank_account_id', $value2)) {
            $value2['bank_account_id'] = '';
          }
          if (!$this->grantsProfileService->isValidUuid($value2['bank_account_id'])) {
            $values[$key][$key2]['bank_account_id'] = Uuid::uuid4()
              ->toString();
          }

          if (isset($storage['confirmationFiles'][$key2])) {
            $values[$key][$key2]['confirmationFileName'] = $storage['confirmationFiles'][$key2]['filename'];
            $values[$key][$key2]['confirmationFile'] = $storage['confirmationFiles'][$key2]['filename'];
          }
          if (!empty($values[$key][$key2]['confirmationFileName'])) {
            $values[$key][$key2]['confirmationFile'] = $values[$key][$key2]['confirmationFileName'];
          }
        }
      }
    }
    unset($values['actions']);
    return $values;
  }

}
