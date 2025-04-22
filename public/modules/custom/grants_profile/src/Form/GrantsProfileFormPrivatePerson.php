<?php

namespace Drupal\grants_profile\Form;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\Core\Url;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\grants_profile\TypedData\Definition\GrantsProfilePrivatePersonDefinition;
use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Provides a Grants Profile form.
 *
 * @phpstan-consistent-constructor
 */
class GrantsProfileFormPrivatePerson extends GrantsProfileFormBase {

  use StringTranslationTrait;

  /**
   * Constructs a new GrantsProfileForm object.
   *
   * @param \Drupal\Core\TypedData\TypedDataManager $typedDataManager
   *   Typed data manager.
   * @param \Drupal\grants_profile\GrantsProfileService $grantsProfileService
   *   Profile service.
   * @param \Symfony\Component\HttpFoundation\Session\Session $session
   *   Session data.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid
   *   Uuid generator.
   * @param \Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData $helsinkiProfiiliUserData
   *   Data for Helsinki Profile.
   */
  public function __construct(
    TypedDataManagerInterface $typedDataManager,
    GrantsProfileService $grantsProfileService,
    SessionInterface $session,
    UuidInterface $uuid,
    #[Autowire(service: 'helfi_helsinki_profiili.userdata')]
    protected HelsinkiProfiiliUserData $helsinkiProfiiliUserData,
  ) {
    parent::__construct($typedDataManager, $grantsProfileService, $session, $uuid);
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
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Form
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $grantsProfile = $this->getGrantsProfileDocument();

    $isNewGrantsProfile = $grantsProfile->getTransactionId();
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

    $newItem = $form_state->getValue('newItem');

    $address = $grantsProfileContent['addresses'][0] ?? NULL;

    // Make sure we have proper UUID as address id.
    if ($address && !$this->isValidUuid($address['address_id'])) {
      $address['address_id'] = $this->uuid->generate();
    }
    $form['isNewProfile'] = [
      '#type' => 'hidden',
      '#title' => 'isNewProfile',
      '#value' => $isNewGrantsProfile,
    ];
    $form['addressWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Address'),
      '#title_tag' => 'h4',
      '#prefix' => '<div id="addresses-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['addressWrapper'][0] = [];
    $form['addressWrapper'][0]['address'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personal address', [], $this->tOpts),
    ];
    $form['addressWrapper'][0]['address']['street'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Street address', [], $this->tOpts),
      '#default_value' => $address['street'] ?? '',
      '#required' => TRUE,
    ];
    $form['addressWrapper'][0]['address']['postCode'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Postal code', [], $this->tOpts),
      '#default_value' => $address['postCode'] ?? '',
      '#required' => TRUE,
    ];
    $form['addressWrapper'][0]['address']['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City/town', [], ['context' => 'Profile Address']),
      '#default_value' => $address['city'] ?? '',
      '#required' => TRUE,
    ];
    $form['addressWrapper'][0]['address']['country'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Country', [], ['context' => 'Profile Address']),
      '#attributes' => ['readonly' => 'readonly'],
      '#default_value' => $address['country'] ?? 'Suomi',
      '#value' => $address['country'] ?? 'Suomi',
    ];
    // We need the delta / id to create delete links in element.
    $form['addressWrapper'][0]['address']['address_id'] = [
      '#type' => 'hidden',
      '#value' => $address['address_id'] ?? '',
    ];

    $form['phoneWrapper'] = [
      '#type' => 'webform_section',
      '#title' => $this->t('Telephone', [], $this->tOpts),
      '#title_tag' => 'h4',
      '#prefix' => '<div id="phone-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['phoneWrapper']['phone_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Personal telephone', [], $this->tOpts),
      '#default_value' => $grantsProfileContent['phone_number'] ?? '',
      '#required' => TRUE,
    ];

    $stringsArray = [
      '#description' => '',
      '#title' => $this->t('Personal bank account', [], $this->tOpts),
    ];

    $this->addbankAccountBits(
      $form,
      $form_state,
      [],
      $grantsProfileContent['bankAccounts'],
      $newItem,
      $stringsArray);
    $profileEditUrl = Url::fromUri(getenv('HELSINKI_PROFIILI_URI'));
    $profileEditUrl->mergeOptions([
      'attributes' => [
        'title' => $this->t('If you want to change the information from Helsinki-profile
you can do that by going to the Helsinki-profile from this link.', [], $this->tOpts),
        'target' => '_blank',
      ],
    ]);
    $editHelsinkiProfileLink = Link::fromTextAndUrl(
      $this->t('Go to the Helsinki profile to update your email address.', [], $this->tOpts),
      $profileEditUrl
    );

    $form['#basic_info'] = [
      '#theme' => 'grants_profile__basic_info__private_person',
      '#myProfile' => $helsinkiProfileContent['myProfile'] ?? '',
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

    $addressArrayKeys = [0];
    $officialArrayKeys = [];
    $bankAccountArrayKeys = [];

    if (array_key_exists('addressWrapper', $input)) {
      $values["addressWrapper"] = $input["addressWrapper"];
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

    parent::validateForm($form, $formState);

    $grantsProfileDefinition = GrantsProfilePrivatePersonDefinition::create('grants_profile_private_person');
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
   * {@inheritDoc}
   */
  public function profileDataRefreshSubmitHandler(array $form, FormStateInterface $form_state): array {
    $storage = $form_state->getStorage();
    $document = $storage['profileDocument'];
    $originalData = $document->getContent();

    try {

      $tokenData = $this->helsinkiProfiiliUserData->getUserData();
      $timestamp = time();

      if ($tokenData && isset($tokenData['exp']) && $tokenData['exp'] < $timestamp) {
        $this->helsinkiProfiiliUserData->refreshTokens();
      }

      $freshData = $this->helsinkiProfiiliUserData->getUserProfileData(TRUE);

      $possibleChanges = [];

      // Email seems to be the only thing user cannot change by themself.
      if (isset($freshData['myProfile']['primaryEmail'])) {
        $possibleChanges['email'] = $freshData['myProfile']['primaryEmail']['email'];
      }

      $oldData = array_intersect_key($originalData, $possibleChanges);
      $diff = array_diff($possibleChanges, $oldData);
      if (!empty($diff)) {
        $content = array_merge($originalData, $diff);
        $this->grantsProfileService->saveGrantsProfile($content);
      }

      $this->messenger()->addStatus(
        $this->t('Data from Helsinki Profile successfully updated.', [], $this->tOpts)
      );
    }
    catch (\Exception $e) {
      $this->logger('grants_profile')
        ->error(
          'Grants profile Helsinki Profile (unregistered) update failed. Error: @error',
          ['@error' => $e->getMessage()]
        );
      $this->messenger()->addError(
        $this->t('Updating Helsinki Profile data failed.', [], $this->tOpts)
      );
    }

    $form_state->setRebuild();
    return $form;
  }

}
