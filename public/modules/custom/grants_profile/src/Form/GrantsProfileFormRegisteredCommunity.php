<?php

namespace Drupal\grants_profile\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\grants_profile\Plugin\Validation\Constraint\ValidPostalCodeValidator;
use Drupal\grants_profile\TypedData\Definition\GrantsProfileRegisteredCommunityDefinition;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_yjdh\Exception\YjdhException;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Provides a Grants Profile form.
 */
class GrantsProfileFormRegisteredCommunity extends GrantsProfileFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grants_profile_registered_community';
  }

  /**
   * Get officials' roles.
   *
   * @return array
   *   Available roles.
   */
  public static function getOfficialRoles(): array {
    $tOpts = ['context' => 'grants_profile'];

    return [
      1 => t('Chairperson', [], $tOpts),
      2 => t('Contact person', [], $tOpts),
      3 => t('Other', [], $tOpts),
      4 => t('Treasurer', [], $tOpts),
      5 => t('Auditor', [], $tOpts),
      7 => t('Secretary', [], $tOpts),
      8 => t('Deputy chairperson', [], $tOpts),
      9 => t('Chief executive officer', [], $tOpts),
      10 => t('Producer', [], $tOpts),
      11 => t('Responsible person', [], $tOpts),
    ];
  }

  /**
   * Variable for translation context.
   *
   * @var array|string[] Translation context for class
   */
  private array $tOpts = ['context' => 'grants_profile'];

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

    $lockService = \DrupaL::service('grants_handler.form_lock_service');
    $locked = $lockService->isProfileFormLocked($grantsProfile->getId());
    if ($locked) {
      $form['#disabled'] = TRUE;
      $this->messenger()
        ->addWarning($this->t(
          'This form is being modified by other person currently,
you cannot do any modifications while the form is locked for them.',
          [],
          $this->tOpts
        )
        );
    }
    else {
      $lockService->createOrRefreshProfileFormLock($grantsProfile->getId());
    }

    // Get content from document.
    $grantsProfileContent = $grantsProfile->getContent();

    $storage = $form_state->getStorage();
    $storage['profileDocument'] = $grantsProfile;

    // Use custom theme hook.
    $form['#theme'] = 'own_profile_form';

    $form['#after_build'] = ['Drupal\grants_profile\Form\GrantsProfileFormRegisteredCommunity::afterBuild'];

    $form['foundingYearWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Year of establishment', [], $this->tOpts),
      'foundingYear' => [
        '#type' => 'textfield',
        '#title' => $this->t('Year of establishment', [], $this->tOpts),
        '#default_value' => $grantsProfileContent['foundingYear'],
        '#attributes' => [
          'class' => [
            'webform--small',
          ],
        ],
      ],
    ];
    $form['companyNameShortWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Abbreviated name', [], $this->tOpts),
      'companyNameShort' => [
        '#type' => 'textfield',
        '#title' => $this->t('Abbreviated name', [], $this->tOpts),
        '#default_value' => $grantsProfileContent['companyNameShort'],
        '#attributes' => [
          'class' =>
          [
            'webform--large'
          ]
        ],
      ],
    ];

    $form['companyHomePageWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Website address', [], $this->tOpts),
      'companyHomePage' => [
        '#type' => 'textfield',
        '#title' => $this->t('Website address', [], $this->tOpts),
        '#default_value' => $grantsProfileContent['companyHomePage'],
      ],
    ];

    $form['businessPurposeWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Purpose of operations', [], $this->tOpts),
      'businessPurpose' => [
        '#type' => 'textarea',
        '#title' => $this->t(
          'Description of the purpose of the activity of the registered association (max. 500 characters)',
          [],
          $this->tOpts
        ),
        '#default_value' => $grantsProfileContent['businessPurpose'],
        '#maxlength' => 500,
        '#required' => TRUE,
        '#help' =>
        $this->t(
            'Briefly describe the purpose for which the community is working and how the community is
  fulfilling its purpose. For example, you can use the text "Community purpose and
  forms of action" in the Community rules. Please do not describe the purpose of the grant here, it will be asked
  later when completing the grant application.',
            [],
            $this->tOpts
        ),
        '#attributes' => [
          'class' => [
            'webform--large'
          ]
        ]
      ],
    ];

    $newItem = $form_state->getValue('newItem');

    $this->addAddressBits($form, $form_state, $grantsProfileContent['addresses'], $newItem);
    $this->addOfficialBits($form, $form_state, $grantsProfileContent['officials'], $newItem);
    $this->addbankAccountBits($form, $form_state, $grantsProfileContent['bankAccounts'], $newItem);

    $form['#profilecontent'] = $grantsProfileContent;
    $form_state->setStorage($storage);

    $form['actions']['submit_cancel']["#submit"] = [
      [self::class, 'formCancelCallback'],
    ];

    return $form;
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
  private function validateFormActions(array $triggeringElement, FormStateInterface &$formState) {
    $returnValue = FALSE;

    if ($triggeringElement["#id"] !== 'edit-actions-submit') {
      $returnValue = TRUE;
    }

    // Clear validation errors if we are adding or removing fields.
    if (
      strpos($triggeringElement["#id"], 'deletebutton') !== FALSE ||
      strpos($triggeringElement["#id"], 'add') !== FALSE ||
      strpos($triggeringElement["#id"], 'remove') !== FALSE
    ) {
      $formState->clearErrors();
    }
    // In case of upload, we want to ignore all except failed upload.
    elseif (strpos($triggeringElement["#id"], 'upload-button') !== FALSE) {
      $errors = $formState->getErrors();
      $parents = $triggeringElement['#parents'];
      array_pop($parents);
      $parentsKey = implode('][', $parents);
      $errorsForUpload = [];

      // Found a file upload error. Remove all and then add the correct error.
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
    }
    return $returnValue;
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
  private function profileContentFromWrappers(array &$values, array &$grantsProfileContent) : void {

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

    $grantsProfileContent["foundingYear"] = $values["foundingYearWrapper"]["foundingYear"];
    $grantsProfileContent["companyNameShort"] = $values["companyNameShortWrapper"]["companyNameShort"];
    $grantsProfileContent["companyHomePage"] = $values["companyHomePageWrapper"]["companyHomePage"];
    $grantsProfileContent["businessPurpose"] = $values["businessPurposeWrapper"]["businessPurpose"];
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $formState) {
    $triggeringElement = $formState->getTriggeringElement();

    if ($this->validateFormActions($triggeringElement, $formState)) {
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

    $this->profileContentFromWrappers($values, $grantsProfileContent);

    $this->validateBankAccounts($values, $formState);
    $this->validateOfficials($values, $formState);

    parent::validateForm($form, $formState);

    $grantsProfileDefinition = GrantsProfileRegisteredCommunityDefinition::create(
      'grants_profile_registered_community');
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
    $this->reportValidatedErrors(
      $violations,
      $form,
      $formState,
      $addressArrayKeys,
      $officialArrayKeys,
      $bankAccountArrayKeys
    );
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
  private function reportValidatedErrors(ConstraintViolationListInterface $violations,
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

        case 'companyNameShort':
          $propertyPath = 'companyNameShortWrapper][companyNameShort';
          break;

        case 'companyHomePage':
          $propertyPath = 'companyHomePageWrapper][companyHomePage';
          break;

        case 'businessPurpose':
          $propertyPath = 'businessPurposeWrapper][businessPurpose';
          break;

        case 'foundingYear':
          $propertyPath = 'foundingYearWrapper][foundingYear';
          break;

        case 'addresses':
          if (count($propertyPathArray) == 1) {
            $errorElement = $form["addressWrapper"];
            $errorMessage = 'You must add one address';
          }
          else {
            $propertyPath = 'addressWrapper][' . $addressArrayKeys[$propertyPathArray[1]]
              . '][address][' . $propertyPathArray[2];
          }
          break;

        case 'bankAccounts':
          if (count($propertyPathArray) == 1) {
            $errorElement = $form["bankAccountWrapper"];
            $errorMessage = 'You must add one bank account';
          }
          else {
            $propertyPath = 'bankAccountWrapper][' . $bankAccountArrayKeys[$propertyPathArray[1]]
              . '][bank][' . $propertyPathArray[2];
          }
          break;

        case 'officials':
          $propertyPath = 'officialWrapper][' . $officialArrayKeys[$propertyPathArray[1]]
            . '][official][' . $propertyPathArray[2];
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $formState) {

    $storage = $formState->getStorage();
    if (!isset($storage['grantsProfileData'])) {
      $this->messenger()->addError($this->t('grantsProfileData not found!', [], $this->tOpts));
      return;
    }

    $grantsProfileData = $storage['grantsProfileData'];

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');

    $profileDataArray = $grantsProfileData->toArray();

    try {
      $success = $grantsProfileService->saveGrantsProfile($profileDataArray);
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
            $this->tOpts));
    }

    $formState->setRedirect('grants_profile.show');
  }

  /**
   * Create new profile object.
   *
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Profile service.
   * @param mixed $selectedCompany
   *   Customers' selected company.
   * @param array $form
   *   Form array.
   *
   * @return array
   *   New profle.
   */
  public function createNewProfile(
    GrantsProfileService $grantsProfileService,
    mixed $selectedCompany,
    array $form
  ): array {

    try {
      // Initialize a new one.
      // This fetches company details from yrtti / ytj.
      $grantsProfileContent = $grantsProfileService->initGrantsProfileRegisteredCommunity($selectedCompany, []);

      // Initial save of the new profile so we can add files to it.
      $newProfile = $grantsProfileService->saveGrantsProfile($grantsProfileContent);
    }
    catch (YjdhException $e) {
      $newProfile = NULL;
      // If no company data is found, we cannot continue.
      $this->messenger()
        ->addError(
          $this->t(
            'Community details not found in registries. Please contact customer service',
            [],
            $this->tOpts
          )
            );
      $this->logger(
            'grants_profile')
        ->error('Error fetching community data. Error: %error', ['%error' => $e->getMessage()]);
      $form['#disabled'] = TRUE;
    }
    catch (AtvDocumentNotFoundException | AtvFailedToConnectException | GuzzleException $e) {
      $newProfile = NULL;
      // If no company data is found, we cannot continue.
      $this->messenger()
        ->addError(
          $this->t(
            'Community details not found in registries. Please contact customer service',
            [],
            $this->tOpts
          )
            );
      $this->logger('grants_profile')
        ->error('Error fetching community data. Error: %error', ['%error' => $e->getMessage()]);
    }
    return [$newProfile, $form];
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
        '#title' => $this->t('Community address', [], $this->tOpts),
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
          'data-pattern-error' => $this->t('Use the format FI-XXXXX or enter a five-digit postcode.',
            [], $this->tOpts),
        ],
      ];
      $form['addressWrapper'][$delta]['address']['city'] = [
        '#type' => 'textfield',
        '#required' => TRUE,
        '#title' => $this->t('City/town', [], ['context' => 'Profile Address']),
        '#default_value' => $address['city'],
      ];

      // We need the delta / id to create delete links in element.
      $form['addressWrapper'][$delta]['address']['address_id'] = [
        '#type' => 'hidden',
        '#value' => $address['address_id'],
      ];

      $form['addressWrapper'][$delta]['address']['deleteButton'] = [
        '#type' => 'submit',
        '#icon_left' => 'trash',
        '#value' => $this
          ->t('Delete', [], $this->tOpts),
        '#name' => 'addressWrapper--' . $delta,
        '#submit' => [
          '::removeOne',
        ],
        '#ajax' => [
          'callback' => '::addmoreCallback',
          'wrapper' => 'addresses-wrapper',
        ],
      ];
    }

    if ($newItem == 'addressWrapper') {

      $form['addressWrapper'][] = [
        'address' => [
          '#type' => 'fieldset',
          '#title' => $this->t('Community address', [], $this->tOpts),
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
          // We need the delta / id to create delete links in element.
          'address_id' => [
            '#type' => 'hidden',
            '#value' => Uuid::uuid4()->toString(),
          ],
          'deleteButton' => [
            '#type' => 'submit',
            '#icon_left' => 'trash',
            '#value' => $this
              ->t('Delete', [], $this->tOpts),
            '#name' => 'addressWrapper--' . ($delta + 1),
            '#submit' => [
              '::removeOne',
            ],
            '#ajax' => [
              'callback' => '::addmoreCallback',
              'wrapper' => 'addresses-wrapper',
            ],
          ],
        ],
      ];
      $formState->setValue('newItem', NULL);
    }

    $form['addressWrapper']['actions']['add_address'] = [
      '#type' => 'submit',
      '#value' => $this
        ->t('Add address', [], $this->tOpts),
      '#name' => 'addressWrapper--1',
      '#is_supplementary' => TRUE,
      '#icon_left' => 'plus-circle',
      '#submit' => [
        '::addOne',
      ],
      '#ajax' => [
        'callback' => '::addmoreCallback',
        'wrapper' => 'addresses-wrapper',
      ],
      '#prefix' => '<div class="profile-add-more"">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * Add official bits in separate method to improve readability.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   * @param array $officials
   *   Current officials.
   * @param string|null $newItem
   *   Name of new item.
   */
  public function addOfficialBits(
    array &$form,
    FormStateInterface $formState,
    array $officials,
    ?string $newItem
  ) {

    $form['officialWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Persons responsible for operations', [], $this->tOpts),
      '#prefix' => '<div id="officials-wrapper">',
      '#suffix' => '</div>',
    ];

    $roles = [
      0 => $this->t('Select', [], $this->tOpts),
    ] + self::getOfficialRoles();

    $officialValues = $formState->getValue('officialWrapper') ?? $officials;
    unset($officialValues['actions']);

    foreach ($officialValues as $delta => $official) {

      // Make sure we have proper UUID as address id.
      if (!isset($official['official_id']) || !$this->grantsProfileService->isValidUuid($official['official_id'])) {
        $official['official_id'] = Uuid::uuid4()->toString();
      }

      $form['officialWrapper'][$delta]['official'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Community official', [], $this->tOpts),
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
          '#default_value' => $official['role'] ?? 0,
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
   *
   * @return array
   *   Bank account element in array form.
   */
  private function buildBankArray(
    int $delta,
    array $file,
    array|null $attributes = NULL,
    bool $nonEditable = FALSE,
    string $bankAccount = '',
  ) {

    $maxFileSizeInBytes = $file['maxSize'];
    $uploadLocation = $file['uploadLocation'];
    $confFilename = $file['confFilename'];

    return [
      '#type' => 'fieldset',
      '#title' => $this->t('Community bank account', [], $this->tOpts),
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
        '#process' => [[self::class, 'processFileElement']],
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
        ''
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
   * Validate officials.
   *
   * To reduce complexity.
   *
   * @param array $values
   *   Form values.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   */
  public function validateOfficials(array $values, FormStateInterface $formState): void {

    if (!array_key_exists('officialWrapper', $values)) {
      return;
    }
    foreach ($values["officialWrapper"] as $key => $official) {

      if (empty($official["role"]) || $official["role"] == 0) {
        $elementName = 'officialWrapper][' . $key . '][official][role';
        $formState->setErrorByName(
          $elementName,
          $this->t(
            'You must select a role for official',
            [],
            $this->tOpts
          )
        );
      }

    }
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

    $profileService = \Drupal::service('grants_profile.service');
    $lockService    = \Drupal::service('grants_handler.form_lock_service');

    $selectedRoleData = $profileService->getSelectedRoleData();
    $grantsProfile = $profileService->getGrantsProfile($selectedRoleData, TRUE);

    $lockService->releaseProfileFormLock($grantsProfile->getId());

    parent::formCancelCallback($form, $form_state);
  }

}
