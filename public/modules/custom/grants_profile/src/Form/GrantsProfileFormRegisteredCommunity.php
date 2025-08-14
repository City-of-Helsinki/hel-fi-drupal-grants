<?php

namespace Drupal\grants_profile\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\grants_handler\FormLockService;
use Drupal\grants_metadata\Validator\EmailValidator;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\grants_profile\Plugin\Validation\Constraint\ValidPostalCodeValidator;
use Drupal\grants_profile\PRHUpdaterService;
use Drupal\grants_profile\ProfileFetchTimeoutException;
use Drupal\grants_profile\TypedData\Definition\GrantsProfileRegisteredCommunityDefinition;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provides a Grants Profile form.
 *
 * @phpstan-consistent-constructor
 */
class GrantsProfileFormRegisteredCommunity extends GrantsProfileFormBase {

  /**
   * PRH data update service class.
   */
  public function __construct(
    TypedDataManagerInterface $typed_data_manager,
    GrantsProfileService $grantsProfileService,
    SessionInterface $session,
    UuidInterface $uuid,
    protected PRHUpdaterService $prhUpdaterService,
    protected FormLockService $lockService,
  ) {
    parent::__construct($typed_data_manager, $grantsProfileService, $session, $uuid);
  }

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
      12 => t('Executive director', [], $tOpts),
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    try {
      $grantsProfile = $this->getGrantsProfileDocument();
    }
    catch (ProfileFetchTimeoutException $e) {
      $this->messenger()
        ->addError(
        $this->t(
            'Fetching the profile timed out. Please try again in a moment.',
            [],
            ['context' => 'grants_oma_asiointi']
          )
        );
    }

    if ($grantsProfile == NULL) {
      return [];
    }

    $isNewGrantsProfile = $grantsProfile->getTransactionId();

    // Handle multiple editors.
    $locked = $this->lockService->isProfileFormLocked($grantsProfile->getId());
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
      $this->lockService->createOrRefreshProfileFormLock($grantsProfile->getId());
    }

    // Get content from document.
    $grantsProfileContent = $grantsProfile->getContent();

    // Prevent the code from dying if the user managed to
    // "corrupt" the profile document data in atv.
    if (!isset($grantsProfileContent['addresses'])) {
      $grantsProfileContent['addresses'] = [];
    }

    if (!isset($grantsProfileContent['officials'])) {
      $grantsProfileContent['officials'] = [];
    }

    if (!isset($grantsProfileContent['bankAccounts'])) {
      $grantsProfileContent['bankAccounts'] = [];
    }

    $storage = $form_state->getStorage();
    $storage['profileDocument'] = $grantsProfile;

    // Use custom theme hook.
    $form['#theme'] = 'own_profile_form';
    $form['isNewProfile'] = [
      '#type' => 'hidden',
      '#title' => 'isNewProfile',
      '#value' => $isNewGrantsProfile,
    ];
    $form['basicDetailsWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Basic details', [], $this->tOpts),
      '#title_tag' => 'h4',
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
      'companyNameShort' => [
        '#type' => 'textfield',
        '#title' => $this->t('Abbreviated name', [], $this->tOpts),
        '#default_value' => $grantsProfileContent['companyNameShort'],
        '#attributes' => [
          'class' =>
          [
            'webform--large',
          ],
        ],
      ],
      'companyHomePage' => [
        '#type' => 'textfield',
        '#title' => $this->t('Website address', [], $this->tOpts),
        '#default_value' => $grantsProfileContent['companyHomePage'],
      ],
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
            'webform--large',
          ],
        ],
      ],
    ];

    $newItem = $form_state->getValue('newItem');

    $stringsArray = [
      '#description' => '',
      '#title' => $this->t('Community bank account', [], $this->tOpts),
    ];

    $this->addAddressBits($form, $form_state, $grantsProfileContent['addresses'], $newItem);
    $this->addOfficialBits($form, $form_state, $grantsProfileContent['officials'], $newItem);
    $this->addbankAccountBits($form, $form_state, [], $grantsProfileContent['bankAccounts'], $newItem, $stringsArray);

    $form['#profilecontent'] = $grantsProfileContent;

    $form_state->setStorage($storage);

    $form['actions']['submit_cancel']["#submit"] = [
      [self::class, 'formCancelCallback'],
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function profileDataRefreshSubmitHandler(array $form, FormStateInterface $form_state): array {
    $storage = $form_state->getStorage();

    $document = $storage['profileDocument'];

    try {
      $this->prhUpdaterService->update($document);

      $this->messenger()->addStatus(
        $this->t('Data from PRH successfully updated.', [], $this->tOpts)
      );
    }
    catch (\Exception $e) {
      $this->logger('grants_profile')
        ->error(
          'Grants profile PRH update failed. Error: @error',
          ['@error' => $e->getMessage()]
        );
      $this->messenger()->addError(
        $this->t('Updating PRH data failed.', [], $this->tOpts)
      );
    }

    $form_state->setRebuild();
    return $form;
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

    parent::profileContentFromWrappers($values, $grantsProfileContent);

    $grantsProfileContent["foundingYear"] = $values["basicDetailsWrapper"]["foundingYear"];
    $grantsProfileContent["companyNameShort"] = $values["basicDetailsWrapper"]["companyNameShort"];
    $grantsProfileContent["companyHomePage"] = $values["basicDetailsWrapper"]["companyHomePage"];
    $grantsProfileContent["businessPurpose"] = $values["basicDetailsWrapper"]["businessPurpose"];
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

      foreach ($values['officialWrapper'] as &$official) {
        $official['official']['email'] = mb_strtolower($official['official']['email']);
      }

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
    $this->handleViolations(
      $grantsProfileDefinition,
      $grantsProfileContent,
      $formState,
      $form,
      $addressArrayKeys,
      $officialArrayKeys,
      $bankAccountArrayKeys
    );
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
    ?string $newItem,
  ) {

    $form['addressWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Addresses', [], $this->tOpts),
      '#title_tag' => 'h4',
      '#prefix' => '<div id="addresses-wrapper">',
      '#suffix' => '</div>',
    ];

    // Add a container for errors since the errors don't
    // show up the webform_section element.
    $form = $this->addErrorElement('addressWrapper', $form);

    $addressValues = $formState->getValue('addressWrapper') ?? $addresses;
    unset($addressValues['actions']);
    $deltaindex = 0;
    foreach ($addressValues as $delta => $address) {
      $deltaindex = $delta;
      if (array_key_exists('address', $address)) {
        $address = $address['address'];
      }
      // Make sure we have proper UUID as address id.
      if (!isset($address['address_id']) || !$this->isValidUuid($address['address_id'])) {
        $address['address_id'] = $this->uuid->generate();
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
          'disable-refocus' => TRUE,
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
            '#value' => $this->uuid->generate(),
          ],
          'deleteButton' => [
            '#type' => 'submit',
            '#icon_left' => 'trash',
            '#value' => $this
              ->t('Delete', [], $this->tOpts),
            '#name' => 'addressWrapper--' . ($deltaindex + 1),
            '#submit' => [
              '::removeOne',
            ],
            '#ajax' => [
              'callback' => '::addmoreCallback',
              'disable-refocus' => TRUE,
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
        'disable-refocus' => TRUE,
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
    ?string $newItem,
  ) {

    $form['officialWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Persons responsible for operations', [], $this->tOpts),
      '#title_tag' => 'h4',
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
      if (!isset($official['official_id']) || !$this->isValidUuid($official['official_id'])) {
        $official['official_id'] = $this->uuid->generate();
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
          '#pattern' => EmailValidator::PATTERN,
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
            'disable-refocus' => TRUE,
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
          '#pattern' => EmailValidator::PATTERN,
          '#title' => $this->t('Email address', [], $this->tOpts),
        ],
        'phone' => [
          '#type' => 'textfield',
          '#required' => TRUE,
          '#title' => $this->t('Telephone', [], $this->tOpts),
        ],
        'official_id' => [
          '#type' => 'hidden',
          '#value' => $this->uuid->generate(),
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
            'disable-refocus' => TRUE,
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
        'disable-refocus' => TRUE,
      ],
      '#prefix' => '<div class="profile-add-more"">',
      '#suffix' => '</div>',
    ];
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
