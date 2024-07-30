<?php

namespace Drupal\grants_profile\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\grants_handler\ApplicationGetterService;
use Drupal\grants_handler\Helpers;
use Drupal\grants_mandate\GrantsMandateService;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\helfi_atv\AtvService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a confirmation form to confirm deletion of current company.
 *
 * @phpstan-consistent-constructor
 */
class CompanyDeleteConfirmForm extends ConfirmFormBase {

  /**
   * Variable for translation context.
   *
   * @var array|string[] Translation context for class
   */
  private array $tOpts = ['context' => 'grants_profile'];

  /**
   * Class constructor.
   */
  public function __construct(
    protected GrantsProfileService $grantsProfileService,
    protected GrantsMandateService $grantsMandateService,
    protected ApplicationGetterService $applicationGetterService,
    protected AtvService $atvService,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): CompanyDeleteConfirmForm|static {
    return new static(
      $container->get('grants_profile.service'),
      $container->get('grants_mandate.service'),
      $container->get('grants_handler.application_getter_service'),
      $container->get('helfi_atv.atv_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form = parent::buildForm($form, $form_state);

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#attributes' => ['class' => ['button', 'hds-button--secondary']],
      '#weight' => 10,
      '#limit_validation_errors' => [],
      '#submit' => ['::cancelForm'],
    ];
    return $form;

  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    $result = $this->removeProfile($selectedCompany);

    if ($result['success']) {
      $this->messenger()
        ->addStatus($this->t('Community removed', [], $this->tOpts), TRUE);
      $this->grantsMandateService->setPrivatePersonRole();
      $returnUrl = Url::fromRoute('grants_mandate.mandateform');
    }
    else {
      $this->messenger()
        ->addError($this->t(
          'Unable to remove the community, @reason',
          ['@reason' => $result['reason']],
          $this->tOpts
        ), TRUE);
      $returnUrl = Url::fromRoute('grants_profile.show');
    }

    $form_state->setRedirectUrl($returnUrl);
  }

  /**
   * Cancel and redirect.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state): void {
    $url = $this->getCancelUrl();
    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() : string {
    return 'company_delete_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('grants_profile.show');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Do you want to delete community and all of its content?', [], $this->tOpts);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('This removes the community and all applications in draft state.
Removal can not be done if there are sent applications. This cannot be undone.', [], $this->tOpts);
  }

  /**
   * Remove profile.
   *
   * @param array $companyData
   *   Company to remove.
   *
   * @return array
   *   Was the removal successful
   *
   * @throws \Drupal\grants_profile\GrantsProfileException
   */
  public function removeProfile(array $companyData): array {
    if ($companyData['type'] !== 'unregistered_community') {
      return [
        'reason' => $this->t('You can not remove this profile', [], $this->tOpts),
        'success' => FALSE,
      ];
    }
    /** @var \Drupal\helfi_atv\AtvDocument $atvDocument */
    $atvDocument = $this->grantsProfileService->getGrantsProfile($companyData);
    if (!$atvDocument->isDeletable()) {
      return [
        'reason' => $this->t('You can not remove this profile', [], $this->tOpts),
        'success' => FALSE,
      ];
    }

    $appEnv = Helpers::getAppEnv();

    try {
      // Get applications from ATV.
      $applications = $this->applicationGetterService->getCompanyApplications(
        $companyData,
        $appEnv,
        FALSE,
        TRUE,
        'application_list_item'
      );
      $drafts = [];
      if (isset($applications['DRAFT'])) {
        $drafts = $applications['DRAFT'];
        unset($applications['DRAFT']);
      }
      if (!empty($applications)) {
        return [
          'reason' => $this->t('Community has applications in progress.', [], $this->tOpts),
          'success' => FALSE,
        ];
      }
    }
    catch (\Throwable $e) {
      $this->logger('company_delete_confirm_form')->error('Error fetching data from ATV: @e', ['@e' => $e->getMessage()]);
      return [
        'reason' => $this->t('Connection error', [], $this->tOpts),
        'success' => FALSE,
      ];
    }
    try {
      foreach ($drafts as $draft) {
        $this->atvService->deleteDocument($draft['#document']);
      }
      $this->atvService->deleteDocument($atvDocument);
    }
    catch (\Throwable $e) {
      $id = $atvDocument->getId();
      $this->logger('company_delete_confirm_form')->error('Error removing profile (id: @id) from ATV: @e',
        ['@e' => $e->getMessage(), '@id' => $id],
      );
      return [
        'reason' => $this->t('Connection error', [], $this->tOpts),
        'success' => FALSE,
      ];
    }
    return [
      'reason' => '',
      'success' => TRUE,
    ];
  }

}
