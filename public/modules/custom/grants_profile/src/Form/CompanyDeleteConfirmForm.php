<?php

namespace Drupal\grants_profile\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a confirmation form to confirm deletion of current company.
 */
class CompanyDeleteConfirmForm extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);
    // $form['actions']['cancel']['#type'] = 'button';
    // var_dump($form['actions']['cancel']['submit']);die();
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
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selectedCompany = \Drupal::service('grants_profile.service')->getSelectedRoleData();
    $result = \Drupal::service('grants_profile.service')->removeProfile($selectedCompany);

    if ($result['success']) {
      $this->messenger()
        ->addStatus($this->t('Community removed'), TRUE);
      \Drupal::service('grants_mandate.service')->setPrivatePersonRole();
      $returnUrl = Url::fromRoute('grants_mandate.mandateform');
    }
    else {
      $this->messenger()
        ->addError($this->t('Unable to remove the community, @reason', ['@reason' => $result['reason']]), TRUE);
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
    return $this->t('Do you want to delete community and all of its content?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This removes the community and all applications in draft state. Removal can not be done if there are sent applications. This cannot be undone.');
  }

}
