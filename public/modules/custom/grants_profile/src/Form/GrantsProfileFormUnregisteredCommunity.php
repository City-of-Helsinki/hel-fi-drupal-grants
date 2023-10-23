<?php

namespace Drupal\grants_profile\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\Url;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\grants_profile\Plugin\Validation\Constraint\ValidPostalCodeValidator;
use Drupal\grants_profile\TypedData\Definition\GrantsProfileUnregisteredCommunityDefinition;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Grants Profile form.
 */
class GrantsProfileFormUnregisteredCommunity extends GrantsProfileFormBase {

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
    HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
  ) {
    parent::__construct($typed_data_manager, $grantsProfileService);
    $this->helsinkiProfiiliUserData = $helsinkiProfiiliUserData;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GrantsProfileFormUnregisteredCommunity|static {
    return new static(
      $container->get('typed_data_manager'),
      $container->get('grants_profile.service'),
      $container->get('helfi_helsinki_profiili.userdata')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grants_profile_unregistered_community';
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
    $form['#theme'] = 'own_profile_form_unregistered_community';
    $form['#after_build'] = ['Drupal\grants_profile\Form\GrantsProfileFormUnregisteredCommunity::afterBuild'];

    $form['companyNameWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Name of the community or group', [], $this->tOpts),
    ];
    $form['companyNameWrapper']['companyName'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Name of the community or group', [], $this->tOpts),
      '#default_value' => $grantsProfileContent['companyName'],
      '#help' => $this->t("The name of the community or group will be visible in the
applications, decisions, and other similar contexts as the applicant's name. If the
community's or group's name includes names of individual persons, they may be published
as part of the name also on the internet.", [], $this->tOpts),
    ];

    $newItem = $form_state->getValue('newItem');

    $this->addAddressBits($form, $form_state, $grantsProfileContent['addresses'], $newItem);
    $this->addbankAccountBits(
      $form,
      $form_state,
      $helsinkiProfileContent,
      $grantsProfileContent['bankAccounts'],
      $newItem
    );
    $this->addOfficialBits($form, $form_state, $grantsProfileContent['officials'] ?? [], $newItem);

    $form['#profilecontent'] = $grantsProfileContent;

    $profileEditUrl = Url::fromUri(getenv('HELSINKI_PROFIILI_URI'));
    $profileEditUrl->mergeOptions([
      'attributes' => [
        'title' => $this->t('If you want to change the information from Helsinki-profile
you can do that by going to the Helsinki-profile from this link.', [], $this->tOpts),
        'target' => '_blank',
      ],
    ]);
    $editHelsinkiProfileLink = Link::fromTextAndUrl(
      $this->t('Go to Helsinki-profile to edit your information.', [], $this->tOpts), $profileEditUrl);

    $form['#basic_info'] = [
      '#theme' => 'grants_profile__basic_info__private_person',
      '#myProfile' => $helsinkiProfileContent['myProfile'],
      '#editHelsinkiProfileLink' => $editHelsinkiProfileLink,
    ];

    $form_state->setStorage($storage);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {

    $triggeringElement = $formState->getTriggeringElement();

    if (parent::validateFormActions($triggeringElement, $formState)) {
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

    $addressArrayKeys = [];
    $officialArrayKeys = [];
    $bankAccountArrayKeys = [];

    if (array_key_exists('addressWrapper', $input)) {
      $addressArrayKeys = array_keys($input["addressWrapper"]);
      $values["addressWrapper"] = $input["addressWrapper"];
    }

    if (array_key_exists('officialWrapper', $input)) {
      $officialArrayKeys = array_keys($input["officialWrapper"]);
      $values["officialWrapper"] = $input["officialWrapper"];
    }

    foreach (($input["bankAccountWrapper"] ?? []) as $key => $accountData) {
      $bankAccountArrayKeys = array_keys($input["bankAccountWrapper"]);
      $values["bankAccountWrapper"] = $input["bankAccountWrapper"];

      if (!empty($accountData['bank']['bankAccount'])) {
        $myIban = str_replace(' ', '', $accountData['bank']['bankAccount']);
        $values['bankAccountWrapper'][$key]['bank']['bankAccount'] = $myIban;
      }
    }

    $values = $this->cleanUpFormValues($values, $input, $storage);

    // Set clean values to form state.
    $formState->setValues($values);

    parent::profileContentFromWrappers($values, $grantsProfileContent);

    $this->validateBankAccounts($values, $formState);
    $this->validateOfficials($values, $formState);

    parent::validateForm($form, $formState);

    $grantsProfileDefinition = GrantsProfileUnregisteredCommunityDefinition::create(
      'grants_profile_unregistered_community');
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
    parent::reportValidatedErrors(
      $violations,
      $form,
      $formState,
      $addressArrayKeys,
      $officialArrayKeys,
      $bankAccountArrayKeys
    );

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

    $selectedRoleData = $this->grantsProfileService->getSelectedRoleData();

    $profileDataArray = $grantsProfileData->toArray();

    try {
      $success = $this->grantsProfileService->saveGrantsProfile($profileDataArray);
      $selectedRoleData['name'] = $profileDataArray['companyName'];
      $this->grantsProfileService->setSelectedRoleData($selectedRoleData);
    }
    catch (\Exception $e) {
      $success = FALSE;
      $this->logger('grants_profile')
        ->error('Grants profile saving failed. Error: @error', ['@error' => $e->getMessage()]);
    }
    catch (GuzzleException $e) {
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
          $this->t(
            'Your profile information has been saved. You can go to the application via the @link.',
            [
              '@link' => $applicationSearchLink->toString(),
            ],
            $this->tOpts
          )
        );
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
   * @param array $addresses
   *   Current addresses.
   * @param string|null $newItem
   *   New item title.
   */
  public function addAddressBits(
    array &$form,
    FormStateInterface $formState,
    array $addresses,
    ?string $newItem
  ) {

    $form['addressWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Addresses', [], $this->tOpts),
      '#prefix' => '<div id="addresses-wrapper">',
      '#suffix' => '</div>',
    ];

    $addressValues = $formState->getValue('addressWrapper') ?? $addresses;

    unset($addressValues['actions']);
    foreach ($addressValues as $delta => $address) {
      if (array_key_exists('address', $address)) {
        $address = $address['address'];
      }
      // Make sure we have proper UUID as address id.
      if (!isset($address['address_id']) || !$this->grantsProfileService->isValidUuid($address['address_id'])) {
        $address['address_id'] = Uuid::uuid4()->toString();
      }

      $form['addressWrapper'][$delta]['address'] = [
        '#type' => 'fieldset',
        '#description_display' => 'before',
        '#description' => $this->t('The address must be your official address.
One address is mandatory information in your personal information and on the application.', [], $this->tOpts),
        '#title' => $this->t('Community or group address', [], $this->tOpts),
      ];
      $form['addressWrapper'][$delta]['address']['street'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Street address', [], $this->tOpts),
        '#default_value' => $address['street'],
      ];
      $form['addressWrapper'][$delta]['address']['postCode'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('Postal code', [], $this->tOpts),
        '#default_value' => $address['postCode'],
        '#pattern' => ValidPostalCodeValidator::$postalCodePattern,
        '#maxlength' => 8,
        '#attributes' => [
          'data-pattern-error' => $this->t('Use the format FI-XXXXX or enter a five-digit postcode.', [], $this->tOpts),
        ],
      ];
      $form['addressWrapper'][$delta]['address']['city'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('City/town', [], ['context' => 'Profile Address']),
        '#default_value' => $address['city'],
      ];
      $form['addressWrapper'][$delta]['address']['country'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Country', [], ['context' => 'Profile Address']),
        '#attributes' => ['readonly' => 'readonly'],
        '#default_value' => $address['country'] ?? 'Suomi',
        '#value' => $address['country'] ?? 'Suomi',
      ];
      // We need the delta / id to create delete links in element.
      $form['addressWrapper'][$delta]['address']['address_id'] = [
        '#type' => 'hidden',
        '#value' => $address['address_id'],
      ];
    }

    if ($newItem == 'addressWrapper') {

      $form['addressWrapper'][] = [
        'address' => [
          '#type' => 'fieldset',
          '#title' => $this->t('Community or group address', [], $this->tOpts),
          '#help_display' => 'before',
          '#description' => $this->t('The address must be your official address.
One address is mandatory information in your personal information and on the application.', [], $this->tOpts),
          'street' => [
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => $this->t('Street address', [], $this->tOpts),
          ],
          'postCode' => [
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => $this->t('Postal code', [], $this->tOpts),
            '#pattern' => ValidPostalCodeValidator::$postalCodePattern,
            '#maxlength' => 8,
            '#attributes' => [
              'data-pattern-error' => $this->t('Use the format FI-XXXXX or enter a five-digit postcode.',
                [], $this->tOpts),
            ],
          ],
          'city' => [
            '#type' => 'textfield',
            '#required' => TRUE,
            '#title' => $this->t('City/town', [], ['context' => 'Profile Address']),
          ],
          'country' => [
            '#type' => 'textfield',
            '#title' => $this->t('Country', [], ['context' => 'Profile Address']),
            '#attributes' => ['readonly' => 'readonly'],
            '#default_value' => 'Suomi',
            '#value' => 'Suomi',
          ],
          // We need the delta / id to create delete links in element.
          'address_id' => [
            '#type' => 'hidden',
            '#value' => Uuid::uuid4()->toString(),
          ],

        ],
      ];
      $formState->setValue('newItem', NULL);
    }

  }

  /**
   * Add official bits in separate method to improve readability.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array|null $officials
   *   Current officials.
   * @param string|null $newItem
   *   Name of new item.
   */
  public function addOfficialBits(
    array &$form,
    FormStateInterface $formState,
    ?array $officials,
    ?string $newItem
  ) {

    $form['officialWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Persons responsible for operations', [], $this->tOpts),
      '#prefix' => '<div id="officials-wrapper">',
      '#suffix' => '</div>',
    ];

    if (!$officials) {
      $officials = [];
    }

    $roles = [
      0 => $this->t('Select', [], $this->tOpts),
    ] + GrantsProfileFormRegisteredCommunity::getOfficialRoles();

    $officialValues = $formState->getValue('officialWrapper') ?? $officials;
    unset($officialValues['actions']);
    foreach ($officialValues as $delta => $official) {

      // Make sure we have proper UUID as address id.
      if (!isset($official['official_id']) || !$this->grantsProfileService->isValidUuid($official['official_id'])) {
        $official['official_id'] = Uuid::uuid4()->toString();
      }

      $form['officialWrapper'][$delta]['official'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Community or group official', [], $this->tOpts),
        'name' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Name', [], $this->tOpts),
          '#default_value' => $official['name'] ?? '',
        ],
        'role' => [
          '#type' => 'select',
          '#options' => $roles,
          '#title' => $this->t('Role', [], $this->tOpts),
          '#default_value' => $official['role'] ?? 11,
        ],
        'email' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Email address', [], $this->tOpts),
          '#default_value' => $official['email'] ?? '',
        ],
        'phone' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Telephone', [], $this->tOpts),
          '#default_value' => $official['phone'] ?? '',
        ],
        'official_id' => [
          '#type' => 'hidden',
          '#default_value' => $official['official_id'] ?? '',
        ],
        'deleteButton' => [
          '#type' => 'submit',
          '#icon_left' => 'trash',
          '#value' => $this
            ->t('Delete', [], $this->tOpts),
          '#name' => 'officialWrapper--' . $delta,
          '#submit' => [
            '::removeOne',
          ],
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => 'officials-wrapper',
          ],
        ],
      ];
    }

    if ($newItem == 'officialWrapper') {
      $nextDelta = isset($delta) ? $delta + 1 : 0;

      $form['officialWrapper'][$nextDelta]['official'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Community official', [], $this->tOpts),
        'name' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Name', [], $this->tOpts),
        ],
        'role' => [
          '#type' => 'select',
          '#options' => $roles,
          '#title' => $this->t('Role', [], $this->tOpts),
        ],
        'email' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Email address', [], $this->tOpts),
        ],
        'phone' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Telephone', [], $this->tOpts),
        ],
        'official_id' => [
          '#type' => 'hidden',
          '#value' => Uuid::uuid4()->toString(),
        ],
        'deleteButton' => [
          '#type' => 'submit',
          '#icon_left' => 'trash',
          '#value' => $this
            ->t('Delete', [], $this->tOpts),
          '#name' => 'officialWrapper--' . $nextDelta,
          '#submit' => [
            '::removeOne',
          ],
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => 'officials-wrapper',
          ],
        ],
      ];
      $formState->setValue('newItem', NULL);
    }

    $form['officialWrapper']['actions']['add_official'] = [
      '#type' => 'submit',
      '#value' => $this
        ->t('Add official', [], $this->tOpts),
      '#is_supplementary' => TRUE,
      '#icon_left' => 'plus-circle',
      '#name' => 'officialWrapper--1',
      '#submit' => [
        '::addOne',
      ],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'officials-wrapper',
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
      '#title' => $this->t('Community or group bank account', [], $this->tOpts),
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

      $values[$key] = $input[$key] ?? [];
      $values[$key]['actions'] = '';
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
   * Validate officials for toimintaryhmä.
   *
   * Make sure officials have atleast one responsible person added.
   *
   * @param array $values
   *   Cleaned form values.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state object.
   */
  public function validateOfficials(array $values, FormStateInterface $formState): void {
    // Do we have responsibles?
    $responsibles = array_filter(
      ($values["officialWrapper"] ?? ['role' => 0]),
      fn($item) => $item['role'] == '11'
    );

    // If no, then show error on every official added.
    if (!empty($responsibles)) {
      return;
    }
    foreach ($values["officialWrapper"] as $key => $element) {
      $elementName = 'officialWrapper][' . $key . '][official][role';
      $formState->setErrorByName(
        $elementName,
        $this->t(
          "Choose the role 'Responsible person' for at least one person responsible for operations.",
          [],
          ['context' => 'grants_profile']
        )
      );
    }
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
    parent::validateBankAccounts($values, $formState);
    if (array_key_exists('bankAccountWrapper', $values)) {
      foreach ($values["bankAccountWrapper"] as $key => $accountData) {
        if (empty($accountData['ownerName'])) {
          $elementName = 'bankAccountWrapper][' . $key . '][bank][ownerName';
          $formState->setErrorByName(
            $elementName,
            $this->t(
              '@fieldname field is required',
              ['@fieldname' => 'Bank account owner name'],
              ['context' => 'grants_profile']
            )
          );
        }
        if (empty($accountData['ownerSsn'])) {
          $elementName = 'bankAccountWrapper][' . $key . '][bank][ownerSsn';
          $formState->setErrorByName(
            $elementName,
            $this->t('@fieldname field is required',
              ['@fieldname' => 'Bank account owner SSN'],
              ['context' => 'grants_profile']
            )
          );
        }
        else {
          // Check for valid Finnish SSN.
          if (!preg_match("/([0-2]\d|3[0-1])(0\d|1[0-2])(\d{2})([+\-A-FU-Y])(\d{3})([\dA-FHJ-NPR-Y])/",
            $accountData['ownerSsn'])) {
            $elementName = 'bankAccountWrapper][' . $key . '][bank][ownerSsn';
            $formState->setErrorByName(
              $elementName,
              $this->t(
                '%value is not valid Finnish social security number',
                ['%value' => $accountData['ownerSsn']],
                ['context' => 'grants_profile']
              )
            );
          }
        }
      }
    }
  }

}
