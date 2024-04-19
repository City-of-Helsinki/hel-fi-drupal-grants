<?php

namespace Drupal\grants_admin_applications\Service;

use Drupal\Core\Batch\BatchBuilder;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\helfi_atv\AtvDocumentNotFoundException;
use Drupal\helfi_atv\AtvFailedToConnectException;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Provides a HandleDocumentsBatchService service.
 *
 * This service handles batch deletion of
 * ATV documents.
 */
class HandleDocumentsBatchService {

  use DependencySerializationTrait;
  use StringTranslationTrait;

  /**
   * ATV service.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  protected AtvService $atvService;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactory
   */
  protected LoggerChannelFactory $logger;

  /**
   * Module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * Class constructor.
   *
   * @param AtvService $atvService
   *   The AtvService service.
   * @param MessengerInterface $messenger
   *   The MessengerInterface.
   * @param LoggerChannelFactory $loggerFactory
   *   The LoggerChannelFactory.
   * @param ModuleExtensionList $moduleExtensionList
   *   The ModuleExtensionList.
   */
  public function __construct(
    AtvService $atvService,
    MessengerInterface $messenger,
    LoggerChannelFactory $loggerFactory,
    ModuleExtensionList $moduleExtensionList) {
    $this->atvService = $atvService;
    $this->messenger = $messenger;
    $this->logger = $loggerFactory;
    $this->moduleExtensionList = $moduleExtensionList;
  }

  /**
   * The run function.
   *
   * This function creates a batch process of
   * the passed in ATV documents and executes
   * the batch.
   *
   * @param array $documents
   *   An array of ATV documents.
   */
  public function run(array $documents): void {
    if (empty($documents)) {
      $this->messenger->addError('No documents to process.');
      return;
    }

    $moduleName = basename(dirname(__DIR__, 2));
    $modulePath = $this->moduleExtensionList->getPath($moduleName);

    $batchBuilder = new BatchBuilder();
    $batchBuilder
      ->setTitle('Deleting documents')
      ->setInitMessage('Starting to process documents.')
      ->setProgressMessage('Completed @current out of @total batches.')
      ->setErrorMessage('Document processing has encountered an error.')
      ->setFile($modulePath . '/src/Service/' . basename(__FILE__))
      ->setFinishCallback([$this, 'finish']);

    $chunkSize = 10;
    $chunks = array_chunk($documents, $chunkSize);
    $numberOfChunks = count($chunks);

    for ($batchId = 0; $batchId < $numberOfChunks; $batchId++) {
      $batchBuilder->addOperation([$this, 'process'], [$batchId + 1, $chunks[$batchId]]);
    }

    $batch = $batchBuilder->toArray();
    batch_set($batch);
  }

  /**
   * The process function.
   *
   * This function processes ATV documents by
   * deleting them.
   *
   * @param int $batchId
   *   The batch ID.
   * @param array $docsToDelete
   *   The documents to delete this batch.
   * @param array $context
   *   The batch context.
   */
  public function process(int $batchId, array $docsToDelete, array &$context): void {
    if (!isset($context['results']['process'])) {
      $context['results']['process'] = 'Delete documents';
      $context['results']['deleted'] = 0;
      $context['results']['failed'] = 0;
      $context['results']['progress'] = 0;
      $context['results']['deleted_documents'] = [];
    }

    $context['results']['progress'] += count($docsToDelete);

    $context['message'] = t('Processing batch #@batch_id with a batch size of @batch_size.', [
      '@batch_id' => number_format($batchId),
      '@batch_size' => number_format(count($docsToDelete)),
    ]);

    /** @var \Drupal\helfi_atv\AtvDocument $docToDelete */
    foreach ($docsToDelete as $docToDelete) {
      try {
        $transId = $docToDelete->getTransactionId();
        $this->atvService->deleteDocument($docToDelete);
        $context['results']['deleted_documents'][] = $transId;
        $context['results']['deleted']++;
      }
      catch (AtvDocumentNotFoundException | AtvFailedToConnectException | TokenExpiredException | GuzzleException $e) {
        $context['results']['failed']++;
        $this->messenger->addError($e->getMessage());
        $this->logger->get('grants_admin_applications')->error($e->getMessage());
      }
    }
  }

  /**
   * The finish function.
   *
   * This functions logs messages after the process function
   * has finished execution.
   *
   * @param bool $success
   *   TRUE if all batch API tasks were completed successfully.
   * @param array $results
   *   An array of processed documents.
   * @param array $operations
   *   A list of the operations that had not been completed.
   * @param string $elapsed
   *   The elapsed processing time in seconds.
   */
  public function finish(bool $success, array $results, array $operations, string $elapsed): void {
    if (!$success) {
      $errorOperation = reset($operations);
      $message = $this->t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $errorOperation[0],
        '@arguments' => print_r($errorOperation[1], TRUE),
      ]);
      $this->messenger->addError($message);
      return;
    }

    $processMessage = $this->t(
      'Processed @count documents. Deleted documents: @deleted. Failed deletions: @failed. Elapsed time: @elapsed.',[
      '@count' => $results['progress'],
      '@deleted' => $results['deleted'],
      '@failed' => $results['failed'],
      '@elapsed' => $elapsed,
    ]);

    $deletedDocumentsMessage = $this->t('The deleted documents: @documents.', [
      '@documents' => implode(', ', $results['deleted_documents']),
    ]);

    $this->messenger->addMessage($processMessage);
    $this->messenger->addMessage($deletedDocumentsMessage);
    $this->logger->get('grants_admin_applications')->info($processMessage);
    $this->logger->get('grants_admin_applications')->info($deletedDocumentsMessage);
  }
}
