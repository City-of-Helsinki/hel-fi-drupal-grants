<?php

namespace Drupal\grants_profile\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\grants_mandate\GrantsMandateService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\grants_profile\GrantsProfileService;

/**
 * Defines a confirmation form to confirm deletion of current company.
 */
class CompanyDeleteConfirmForm extends ConfirmFormBase {

  /**
   * Variable for translation context.
   *
   * @var array|string[] Translation context for class
   */
  private array $tOpts = ['context' => 'grants_profile'];

  /** @var \Drupal\grants_mandate\GrantsMandateService $grantsMandateService */
  protected GrantsMandateService $grantsMandateService;

  /** @var \Drupal\grants_profile\GrantsProfileService $grantsProfileService */
  protected GrantsProfileService $grantsProfileService;

  /**
   * Class constructor.
   */
  public function __construct(
    GrantsProfileService $grantsProfileService,
    GrantsMandateService $grantsMandateService) {
    $this->grantsProfileService = $grantsProfileService;
    $this->grantsMandateService = $grantsMandateService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('grants_profile.service'),
      $container->get('grants_mandate.service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Casncel'),
      '#attributes' => ['class' => ['button', 'hds-button--secondary']],
      '#weight' => 10,
      '#limit_validation_errors' => [],
      '#submit' => ['::cancelForm'],
    ];
    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $selectedCompany = $this->grantsProfileService->getSelectedRoleData();
    $result =  $this->grantsProfileService->removeProfile($selectedCompany);

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
  public function cancelForm(array &$form, FormStateInterface $form_state) {
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
  public function getCancelUrl() {
    return new Url('grants_profile.show');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Do you want to delete community and all of its content?', [], $this->tOpts);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This removes the community and all applications in draft state.
Removal can not be done if there are sent applications. This cannot be undone.', [], $this->tOpts);
  }

}
