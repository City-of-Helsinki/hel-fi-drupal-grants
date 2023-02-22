<?php

namespace Drupal\grants_profile\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\TypedData\Exception\ReadOnlyException;
use Drupal\Core\TypedData\TypedDataManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\grants_profile\TypedData\Definition\GrantsProfileDefinition;
use Drupal\helfi_yjdh\Exception\YjdhException;

/**
 * Provides a Grants Profile form.
 */
class GrantsProfileForm extends FormBase {

  /**
   * Drupal\Core\TypedData\TypedDataManager definition.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager
   */
  protected TypedDataManager $typedDataManager;

  /**
   * Constructs a new GrantsProfileForm object.
   */
  public function __construct(TypedDataManager $typed_data_manager) {
    $this->typedDataManager = $typed_data_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): GrantsProfileForm|static {
    return new static(
      $container->get('typed_data_manager')
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
      'width' => '25%',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'grants_profile_grants_profile';
  }

  /**
   * Get officials' roles.
   *
   * @return array
   *   Available roles.
   */
  public static function getOfficialRoles(): array {
    return [
      1 => t('Chairperson'),
      2 => t('Contact person'),
      3 => t('Other'),
      4 => t('Treasurer'),
      5 => t('Auditor'),
      7 => t('Secretary'),
      8 => t('Deputy chairperson'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');
    $selectedCompanyArray = $grantsProfileService->getSelectedCompany();
    $selectedCompany = $selectedCompanyArray['identifier'];

    // Load grants profile.
    $grantsProfile = $grantsProfileService->getGrantsProfile($selectedCompany, TRUE);

    // If no profile exist.
    if ($grantsProfile == NULL) {
      try {
        // Initialize a new one.
        // This fetches company details from yrtti / ytj.
        $grantsProfileContent = $grantsProfileService->initGrantsProfile($selectedCompany, []);
      }
      catch (YjdhException $e) {
        // If no company data is found, we cannot continue.
        $this->messenger()
          ->addError($this->t('Community details not found in registries. Please contact customer service'));
        $this->logger(
          'grants_profile')
          ->error('Error fetching community data. Error: %error', [
            '%error' => $e->getMessage(),
          ]
                );
        $form['#disabled'] = TRUE;
        return $form;
      }

    }
    else {
      // Get content from document.
      $grantsProfileContent = $grantsProfile->getContent();
    }

    // Use custom theme hook.
    $form['#theme'] = 'own_profile_form';

    // Set profile content for other fields than this form.
    $form_state->setStorage(['grantsProfileContent' => $grantsProfileContent]);
    $form['foundingYearWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Year of establishment'),
    ];
    $form['foundingYearWrapper']['foundingYear'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Year of establishment'),
      '#default_value' => $grantsProfileContent['foundingYear'],
    ];
    $form['foundingYearWrapper']['foundingYear']['#attributes']['class'][] = 'webform--small';

    $form['companyNameShortWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Abbreviated name'),
    ];
    $form['companyNameShortWrapper']['companyNameShort'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Abbreviated name'),
      '#default_value' => $grantsProfileContent['companyNameShort'],
    ];
    $form['companyNameShortWrapper']['companyNameShort']['#attributes']['class'][] = 'webform--large';
    $form['companyHomePageWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Website address'),
    ];
    $form['companyHomePageWrapper']['companyHomePage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Website address'),
      '#default_value' => $grantsProfileContent['companyHomePage'],
    ];

    $form['businessPurposeWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Purpose of operations'),
    ];
    $form['businessPurposeWrapper']['businessPurpose'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description of the purpose of the activity of the registered association (max. 500 characters)'),
      '#default_value' => $grantsProfileContent['businessPurpose'],
      '#maxlength' => 500,
      '#counter_type' => 'character',
      '#counter_maximum' => 500,
      '#counter_minimum' => 1,
      '#counter_maximum_message' => '%d/500 merkkiä jäljellä',
      '#help' => t('Briefly describe the purpose for which the community is working and how the community is fulfilling its purpose. For example, you can use the text "Community purpose and forms of action" in the Community rules. Please do not describe the purpose of the grant here, it will be asked later when completing the grant application.'),
    ];
    $form['businessPurposeWrapper']['businessPurpose']['#attributes']['class'][] = 'webform--large';

    $form['addressWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Addresses'),
    ];

    $addressValues = [];
    foreach ($grantsProfileContent['addresses'] as $delta => $address) {
      $addressValues[$delta] = $address;

      // Make sure we have proper UUID as address id.
      if (!$this->isValidUuid($address['address_id'])) {
        $addressValues[$delta]['address_id'] = Uuid::uuid4()->toString();
      }
      else {
        $addressValues[$delta]['address_id'] = $address['address_id'];
      }
    }

    if (empty($addressValues)) {
      $addressValues[0]['address_id'] = 0;
    }
    $deleteAddressButton = [
      '#theme' => 'delete_button_link',
      '#icon_left' => 'trash',
      '#text_label' => t('Delete'),
      '#button_type' => 'secondary',
      '#url' => Url::fromRoute('grants_profile.company_addresses.remove_confirm_modal', [
        'address_id' => '{address_delta}',
        'nojs' => 'ajax',
      ]),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode(static::getDataDialogOptions()),
      ],
    ];

    $form['addressWrapper']['addresses'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Addresses'),
      '#required' => TRUE,
      'street' => [
        '#type' => 'textfield',
        '#title' => $this->t('Street address'),
      ],
      'city' => [
        '#type' => 'textfield',
        '#title' => $this->t('City/town'),
      ],
      'postCode' => [
        '#type' => 'textfield',
        '#title' => $this->t('Postal code'),
      ],
      'country' => [
        '#type' => 'textfield',
        '#title' => $this->t('Country'),
      ],
      // We need the delta / id to create delete links in element.
      'address_id' => [
        '#type' => 'hidden',
      ],
      // Address delta is replaced with alter hook in module file.
      'deleteButton' => $deleteAddressButton,
      '#default_value' => $addressValues,
    ];
    $form['addressWrapper']['addresses']['#attributes']['class'][] = 'webform--large';

    $form['officialWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Persons responsible for operations'),
    ];

    $roles = [
      0 => $this->t('Select'),
    ] + self::getOfficialRoles();

    $officialValues = [];
    foreach ($grantsProfileContent['officials'] as $delta => $official) {
      $officialValues[$delta] = $official;

      // Make sure we have proper UUID as official id.
      if (!$this->isValidUuid($official['official_id'])) {
        $officialValues[$delta]['official_id'] = Uuid::uuid4()->toString();
      }
      else {
        $officialValues[$delta]['official_id'] = $official['official_id'];
      }
    }

    $deleteOfficialButton = [
      '#theme' => 'delete_button_link',
      '#icon_left' => 'trash',
      '#text_label' => t('Delete'),
      '#button_type' => 'secondary',
      '#url' => Url::fromRoute('grants_profile.application_official.remove_confirm_modal', [
        'official_id' => '{official_delta}',
        'nojs' => 'ajax',
      ]),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode(static::getDataDialogOptions()),
      ],
    ];

    $form['officialWrapper']['officials'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Persons responsible for operations'),
      'name' => [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
      ],
      'role' => [
        '#type' => 'select',
        '#options' => $roles,
        '#title' => $this->t('Role'),
      ],
      'email' => [
        '#type' => 'textfield',
        '#title' => $this->t('Email address'),
      ],
      'phone' => [
        '#type' => 'textfield',
        '#title' => $this->t('Telephone'),
      ],
      'official_id' => [
        '#type' => 'hidden',
      ],
      'deleteButton' => $deleteOfficialButton,

      '#default_value' => $officialValues,
    ];
    $form['officialWrapper']['officials']['#attributes']['class'][] = 'webform--large';

    $form['bankAccountWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Bank account numbers'),
    ];

    $bankAccountValues = [];
    foreach ($grantsProfileContent['bankAccounts'] as $delta => $account) {
      $bankAccountValues[$delta]['bankAccount'] = $account['bankAccount'];
      $bankAccountValues[$delta]['confirmationFileName'] = $account['confirmationFile'];

      // Make sure we have proper UUID as bank account id.
      if (!$this->isValidUuid($account['bank_account_id'])) {
        $bankAccountValues[$delta]['bank_account_id'] = Uuid::uuid4()->toString();
      }
      else {
        $bankAccountValues[$delta]['bank_account_id'] = $account['bank_account_id'];
      }
    }

    $deleteBankAccountButton = [
      '#theme' => 'delete_button_link',
      '#icon_left' => 'trash',
      '#text_label' => t('Delete'),
      '#button_type' => 'secondary',
      '#url' => Url::fromRoute('grants_profile.bank_account.remove_confirm_modal', [
        'bank_account_id' => '{bank_account_delta}',
        'nojs' => 'ajax',
      ]),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode(static::getDataDialogOptions()),
      ],
    ];

    $sessionHash = sha1(\Drupal::service('session')->getId());
    $upload_location = 'private://grants_profile/' . $sessionHash;

    $form['bankAccountWrapper']['bankAccounts'] = [
      '#type' => 'multivalue',
      '#title' => $this->t('Bank account numbers'),
      '#required' => TRUE,
      'bankAccount' => [
        '#type' => 'textfield',
        '#title' => $this->t('Bank account number'),
        '#required' => TRUE,
      ],
      'confirmationFileName' => [
        '#type' => 'textfield',
        '#title' => $this->t("Saved bank's notification of the account owner or a copy of a bank statement"),
        '#attributes' => ['readonly' => 'readonly'],
      ],
      'confirmationFile' => [
        '#type' => 'managed_file',
        '#title' => $this->t("Bank's notification of the account owner or a copy of a bank statement"),
        '#multiple' => FALSE,
        // '#required' => TRUE,
        '#uri_scheme' => 'private',
        '#file_extensions' => 'doc,docx,gif,jpg,jpeg,pdf,png,ppt,pptx,rtf,txt,xls,xlsx,zip',
        '#upload_validators' => [
          'file_validate_extensions' => ['doc docx gif jpg jpeg pdf png ppt pptx rtf txt xls xlsx zip'],
        ],
        '#upload_location' => $upload_location,
        '#sanitize' => TRUE,
      ],
      'bank_account_id' => [
        '#type' => 'hidden',
      ],
      'deleteButton' => $deleteBankAccountButton,
      '#default_value' => $bankAccountValues,
    ];

    $form['bankAccountWrapper']['bankAccounts']['#attributes']['class'][] = 'webform--large';

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save own information'),
    ];

    $form['#profilecontent'] = $grantsProfileContent;

    return $form;
  }

  /**
   * Check if a given string is a valid UUID.
   *
   * @param string $uuid
   *   The string to check.
   *
   * @return bool
   *   Is valid or not?
   */
  public function isValidUuid($uuid): bool {

    if (!is_string($uuid) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid) !== 1)) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $storage = $form_state->getStorage();
    if (!isset($storage['grantsProfileContent'])) {
      $this->messenger()->addError($this->t('grantsProfileContent not found!'));
      return;
    }

    $values = $form_state->getValues();

    // Clean up empty values from form values.
    foreach ($values as $key => $value) {
      if (is_array($value)) {
        foreach ($value as $key2 => $value2) {
          if ($key == 'addresses') {
            if (
              empty($value2['street']) ||
              empty($value2['city']) ||
              empty($value2['postCode']) ||
              empty($value2['country'])
            ) {
              unset($values[$key][$key2]);
            }
            if (empty($value2["address_id"])) {
              $values[$key][$key2]['address_id'] = Uuid::uuid4()
                ->toString();
              ;
            }
          }
          if ($key == 'officials') {
            if (
              empty($value2['name']) ||
              empty($value2['email']) ||
              empty($value2['phone']) ||
              $value2['role'] == '0'
            ) {
              unset($values[$key][$key2]);
            }
            if (empty($value2["official_id"])) {
              $values[$key][$key2]['official_id'] = Uuid::uuid4()
                ->toString();
              ;
            }
          }
          if ($key == 'bankAccounts') {
            if (!isset($value2['bankAccount']) || empty($value2['bankAccount'])) {
              unset($values[$key][$key2]);
            }
            else {
              // If we have added a new account,
              // then we need to create id for it.
              if (!$this->isValidUuid($value2['bank_account_id'])) {
                $values[$key][$key2]['bank_account_id'] = Uuid::uuid4()
                  ->toString();
              }
              // Parse existing confirmation file to values array.
              if (isset($value2['confirmationFileName']) && !empty($value2['confirmationFileName'])) {
                $values[$key][$key2]['confirmationFile'] = $value2['confirmationFileName'];
              }
              // If we have just uploaded file.
              if (
                isset($value2['confirmationFile']) &&
                is_array($value2['confirmationFile']) &&
                !empty($value2['confirmationFile'])
              ) {
                // Prepend file id with FID- to tell profile service that we
                // need to upload this file as well.
                $values[$key][$key2]['confirmationFile'] = 'FID-' . $value2['confirmationFile'][0] ?? '';
              }

              try {
                if (
                  is_array($value2['confirmationFile']) && empty($value2['confirmationFile'])) {
                  $value2['confirmationFile'] = '';
                }
                elseif (isset($values[$key][$key2]['confirmationFile'])) {
                  $value2['confirmationFile'] = $values[$key][$key2]['confirmationFile'];
                }
              }
              catch (ReadOnlyException $e) {
                $this->messenger()->addError('Data read only');
                $form_state->setError($form, 'Trying to write to readonly value');
              }

            }
          }
        }
      }
    }
    // Set clean values to form state.
    $form_state->setValues($values);
    $grantsProfileContent = $storage['grantsProfileContent'];

    foreach ($grantsProfileContent as $key => $value) {
      if (array_key_exists($key, $values)) {
        $grantsProfileContent[$key] = $values[$key];
      }
    }

    foreach ($values['bankAccounts'] as $key => $accountData) {
      if (!empty($accountData['bankAccount'])) {
        if (
          empty($accountData["confirmationFileName"]) &&
          empty($accountData["confirmationFile"])
        ) {
          $elementName = 'bankAccounts][' . $key . '][confirmationFile';
          $form_state->setErrorByName($elementName, 'You must add confirmation file for account ' . $accountData["bankAccount"]);
        }
      }
    }

    parent::validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    if (empty($errors)) {
      // @todo Created profile needs to be set to cache.
      $grantsProfileDefinition = GrantsProfileDefinition::create('grants_profile_profile');
      // Create data object.
      $grantsProfileData = $this->typedDataManager->create($grantsProfileDefinition);
      $grantsProfileData->setValue($grantsProfileContent);
      // Validate inserted data.
      $violations = $grantsProfileData->validate();
      // If there's violations in data.
      if ($violations->count() != 0) {
        foreach ($violations as $violation) {
          // Print errors by form item name.
          $form_state->setErrorByName(
            $violation->getPropertyPath(),
            $violation->getMessage());
        }
      }
      else {
        // Move addressData object to form_state storage.
        $form_state->setStorage(['grantsProfileData' => $grantsProfileData]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $storage = $form_state->getStorage();
    if (!isset($storage['grantsProfileData'])) {
      $this->messenger()->addError($this->t('grantsProfileData not found!'));
      return;
    }

    $grantsProfileData = $storage['grantsProfileData'];

    /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
    $grantsProfileService = \Drupal::service('grants_profile.service');
    $selectedCompanyArray = $grantsProfileService->getSelectedCompany();
    $selectedCompany = $selectedCompanyArray['identifier'];

    $profileDataArray = $grantsProfileData->toArray();

    try {
      $success = $grantsProfileService->saveGrantsProfile($profileDataArray);
    }
    catch (\Exception $e) {
      $this->logger('grants_profile')->error('Grants profile saving failed. Error: @error', ['@error' => $e->getMessage()]);
    }
    $grantsProfileService->clearCache($selectedCompany);

    if ($success != FALSE) {
      $this->messenger()
        ->addStatus($this->t('Grantsprofile for %c (%s) saved.', [
          '%c' => $selectedCompanyArray['name'],
          '%s' => $selectedCompany,
        ]));
    }

    $form_state->setRedirect('grants_profile.edit');
  }

}
