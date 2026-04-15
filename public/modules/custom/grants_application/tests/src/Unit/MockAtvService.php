<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvServiceInterface;

/**
 * The mock atv service.
 */
class MockAtvService implements AtvServiceInterface {

  /**
   * {@inheritDoc}
   */
  public function searchDocuments(array $searchParams, bool $refetch = FALSE): array {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getUserDocuments(string $sub, string $transaction_id = ''): array {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function createDocument(array $values): AtvDocument {
    return AtvDocument::create($values);
  }

  /**
   * {@inheritDoc}
   */
  public function checkDocumentExistsByTransactionId(string $id) {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function postDocument(AtvDocument $document): AtvDocument {
    return $document;
  }

  /**
   * {@inheritDoc}
   */
  public function patchDocument(string $id, array $dataArray): bool|AtvDocument|null {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getAttachment(string $url): bool|FileInterface {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteDocument(AtvDocument $document) {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteAttachment(string $documentId, string $attachmentId): AtvDocument|bool|array|FileInterface {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteAttachmentByUrl(string $attachmentUrl): AtvDocument|bool|array|FileInterface {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function deleteAttachmentViaIntegrationId(string $integrationId): AtvDocument|bool|array|FileInterface {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function uploadAttachment(string $documentId, string $filename, File $file): mixed {
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getDocument(string $id, bool $refetch = FALSE): AtvDocument {
    return AtvDocument::create([]);
  }

}
