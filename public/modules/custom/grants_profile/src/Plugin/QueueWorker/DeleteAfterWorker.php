<?php

declare(strict_types=1);

namespace Drupal\grants_profile\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\helfi_atv\AtvDocumentNotFoundException;

/**
 * Update ATV document's deleteAfter-value if it has not been set yet.
 *
 * @QueueWorker(
 *   id = "delete_after_queue",
 *   title = @Translation("Delete after -queue"),
 *   cron = {"time" = 60}
 * )
 */
final class DeleteAfterWorker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly LoggerInterface $logger,
    private readonly AtvService $atvService,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.channel.grants_profile'),
      $container->get(AtvService::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $document_id = $data['document_id'];
    $delete_after = $data['delete_after'];

    try {
      $document = $this->atvService->getDocument($document_id, TRUE);
    }
    catch (AtvDocumentNotFoundException $e) {
      $this->logger->info(
        'Tried to update document, not found and skipping: @document_id',
        ['@document_id' => $document_id]);
      return;
    }
    catch (\Exception $e) {
      $this->logger->warning(
        'Tried to update document, something went wrong: @message',
        ['@message' => $e->getMessage()]
      );
      throw $e;
    }

    if ($document->getDeleteAfter()) {
      return;
    }

    $this->setDeleteAfter($document, $delete_after);
  }

  /**
   * Set the delete after value.
   *
   * @param AtvDocument $document
   *   The document.
   * @param string $delete_after
   *   The value to set, for example 2030-01-01.
   */
  private function setDeleteAfter(AtvDocument $document, string $delete_after): void {
    $document->setDeleteAfter($delete_after);
    $document_id = $document->getId();

    try {
      $this->atvService->patchDocument($document_id, $document->toArray());
    }
    catch (AtvDocumentNotFoundException $e) {
      $this->logger->info(
        'Tried to update document, not found and skipping: @document_id',
        ['@document_id' => $document_id]);
      return;
    }
    catch (\Exception $e) {
      $this->logger->warning(
        'Tried to update document, something went wrong: @message',
        ['@message' => $e->getMessage()]
      );
      throw $e;
    }

    $this->logger->info('updated delete-after for document @document_id', ['@document_id' => $document_id]);
  }

}
