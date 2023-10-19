<?php

namespace Drupal\grants_profile\Form;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\Core\Url;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\grants_profile\Plugin\Validation\Constraint\ValidPostalCodeValidator;
use Drupal\grants_profile\TypedData\Definition\GrantsProfileUnregisteredCommunityDefinition;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use GuzzleHttp\Exception\GuzzleException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

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
    $this->typedDataManager = $typed_data_manager;
    $this->grantsProfileService = $grantsProfileService;
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

    $selectedRoleData = $this->grantsProfileService->getSelectedRoleData();

    // Load grants profile.
    $grantsProfile = $this->grantsProfileService->getGrantsProfile($selectedRoleData, TRUE);

    // If no profile exist.
    if ($grantsProfile == NULL) {
      // Create one and.
      $grantsProfile = $this->grantsProfileService->createNewProfile($selectedRoleData);
    }

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
    $form['#tree'] = TRUE;

    $form['#after_build'] = ['Drupal\grants_profile\Form\GrantsProfileFormUnregisteredCommunity::afterBuild'];

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

    $form['newItem'] = [
      '#type' => 'hidden',
      '#value' => NULL,
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
   * Ajax callback for removing item from form.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   Form state.
   */
  public static function removeOne(array &$form, FormStateInterface $formState) {
    $tOpts = ['context' => 'grants_profile'];

    $triggeringElement = $formState->getTriggeringElement();
    [
      $fieldName,
      $deltaToRemove,
    ] = explode('--', $triggeringElement['#name']);

    $fieldValue = $formState->getValue($fieldName);

    if ($fieldName == 'bankAccountWrapper') {
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
  public function addOne(array &$form, FormStateInterface $formState) {
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

    if (array_key_exists('companyNameWrapper', $values)) {
      $grantsProfileContent['companyName'] = $values["companyNameWrapper"]["companyName"];
    }

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
   * Add Bank Account bits in separate method to improve readability.
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
          self::accountsAreEqual($bankAccount['bankAccount'],
            $profileAccount['bankAccount'])) {
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
        '#element_validate' => ['\Drupal\grants_profile\Form\GrantsProfileFormUnregisteredCommunity::validateUpload'],
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
          if (!preg_match("/([0-2]\d|3[0-1])(0\d|1[0-2])(\d{2})([\+\-A])(\d{3})([0-9A-Z])/",
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
