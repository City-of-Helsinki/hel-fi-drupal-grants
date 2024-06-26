<?php

namespace Drupal\grants_admin_applications\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\grants_admin_applications\Service\HandleDocumentsBatchService;
use Drupal\grants_handler\Helpers;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a grants_admin_applications form.
 *
 * @phpstan-consistent-constructor
 */
class DeleteByTransactionIdForm extends FormBase {

  /**
   * Access to ATV.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * Document batch processing service.
   *
   * @var \Drupal\grants_admin_applications\Service\HandleDocumentsBatchService
   */
  protected HandleDocumentsBatchService $handleDocumentsBatchService;

  /**
   * Constructs a new GrantsProfileForm object.
   */
  public function __construct(AtvService $atvService, HandleDocumentsBatchService $handleDocumentsBatchService) {
    $this->atvService = $atvService;
    $this->handleDocumentsBatchService = $handleDocumentsBatchService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): DeleteByTransactionIdForm|static {
    return new static(
      $container->get('helfi_atv.atv_service'),
      $container->get('grants_admin_applications.handle_documents_batch_service'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'grants_admin_applications_delete_applications_by_transaction_id';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (str_contains(strtolower(Helpers::getAppEnv()), 'prod')) {
      $this->messenger()->addError('No deleting profiles in PROD environment');
      return [];
    }

    // Get user inputs.
    $input = $form_state->getUserInput();
    $transactionIds = $input['transactionIds'] ?? NULL;

    // Build the form.
    $form['transaction_ids'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Transaction IDs'),
      '#required' => FALSE,
      '#default_value' => $transactionIds ?? '',
      '#description' => $this->t('Enter a comma-separated list of transaction IDs, e.g. TEST-061-0000028, TEST-061-0000029'),
    ];

    $form['actions']['submit_transactions'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete by transaction ID'),
      '#attributes' => ['onclick' => 'if(!confirm("Delete entered transaction IDs?")){return false;}'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $form_state->clearErrors();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $transactionIds = $form_state->getValue('transaction_ids');

    if (!$transactionIds) {
      $noDocumentsMessage = $this->t('No documents to delete.');
      $this->messenger()->addError($noDocumentsMessage);
      return;
    }

    $transactionIds = $this->parseTransactionIds($transactionIds);
    $documentsToDelete = [];

    try {
      foreach ($transactionIds as $transactionId) {
        $searchParams = ['transaction_id' => $transactionId];
        $document = $this->atvService->searchDocuments($searchParams);
        $document = reset($document);

        if (!$document instanceof AtvDocument) {
          $this->messenger()->addError("Failed fetching application for: $transactionId.");
          continue;
        }
        $documentsToDelete[] = $document;
      }
      $this->handleDocumentsBatchService->run($documentsToDelete);
    }
    catch (AtvDocumentNotFoundException | AtvFailedToConnectException | GuzzleException $e) {
      $this->messenger()->addError('Failed fetching applications.');
      $this->messenger()->addError($e->getMessage());
    }
  }

  /**
   * The parseTransactionIds function.
   *
   * This function parses the passed in string of transaction IDs
   * to an array of transaction IDs. The string is also cleaned
   * of extra dots, spaces and html characters.
   *
   * @param string $transactionIds
   *   The passed in string of transaction IDs.
   *
   * @return array
   *   An array of transaction IDs.
   */
  private function parseTransactionIds(string $transactionIds): array {
    $transactionIds = str_replace(' ', '', $transactionIds);
    $transactionIds = str_replace('.', '', $transactionIds);
    $transactionIds = explode(',', $transactionIds);
    $transactionIds = array_values($transactionIds);
    $transactionIds = array_map(function ($id) {
      return Html::escape($id);
    }, $transactionIds);
    return array_unique($transactionIds);
  }

}
