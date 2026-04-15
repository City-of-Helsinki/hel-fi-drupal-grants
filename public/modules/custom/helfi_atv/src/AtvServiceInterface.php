<?php

namespace Drupal\helfi_atv;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;

/**
 * Atv service interface.
 */
interface AtvServiceInterface {

  /**
   * Search documents with given arguments.
   *
   * @param array $searchParams
   *   Search params.
   * @param bool $refetch
   *   Force refetch from ATV.
   *
   * @return array
   *   Data
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function searchDocuments(array $searchParams, bool $refetch = FALSE): array;

  /**
   * Get metadata for user's documents.
   *
   * If transaction id is given, then use that as a filter. If no value is
   * given, then get metadata of all user's documents.
   *
   * @param string $sub
   *   User id whose documents are fetched.
   * @param string $transaction_id
   *   Transaction id from document.
   *
   * @return array
   *   User documents' public data
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getUserDocuments(string $sub, string $transaction_id = ''): array;

  /**
   * Fetch single document with id.
   *
   * @param string $id
   *   Document id.
   * @param bool $refetch
   *   Force refetch.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   Document from ATV.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException|\Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function createDocument(array $values): AtvDocument;

  /**
   * Check if documents are found with given Trasaction ID.
   *
   * @param string $id
   *   Transaction id.
   *
   * @return bool
   *   Boolean if found.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \Drupal\helfi_atv\AtvUnexpectedResponseException
   * @throws \GuzzleHttp\Exception\GuzzleException|\Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function checkDocumentExistsByTransactionId(string $id);

  /**
   * Save new document.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   Document to be saved.
   *
   * @return \Drupal\helfi_atv\AtvDocument
   *   POSTed document.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function postDocument(AtvDocument $document): AtvDocument;

  /**
   * Run PATCH query in ATV.
   *
   * @param string $id
   *   Document id to be patched.
   * @param array $dataArray
   *   Document data to update.
   *
   * @return bool|\Drupal\helfi_atv\AtvDocument|null
   *   Boolean or updated data.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function patchDocument(string $id, array $dataArray): bool|AtvDocument|null;

  /**
   * Get single attachment.
   *
   * @param string $url
   *   Url for single attachment file.
   *
   * @return bool|\Drupal\file\FileInterface
   *   File or false if failed.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getAttachment(string $url): bool|FileInterface;

  /**
   * Delete document from ATV.
   *
   * @param \Drupal\helfi_atv\AtvDocument $document
   *   Document to be deleted.
   *
   * @return array|bool|\Drupal\file\FileInterface|\Drupal\helfi_atv\AtvDocument
   *   If deletion succeeed.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function deleteDocument(AtvDocument $document);

  /**
   * Delete document attachment from ATV.
   *
   * @param string $documentId
   *   ID of document.
   * @param string $attachmentId
   *   ID of attachment.
   *
   * @return array|bool|\Drupal\file\FileInterface|\Drupal\helfi_atv\AtvDocument
   *   If removal succeeed.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException|\Drupal\helfi_helsinki_profiili\TokenExpiredException
   */
  public function deleteAttachment(string $documentId, string $attachmentId): AtvDocument|bool|array|FileInterface;

  /**
   * Delete document attachment from ATV.
   *
   * @param string $attachmentUrl
   *   Url of an attachment.
   *
   * @return array|bool|\Drupal\file\FileInterface|\Drupal\helfi_atv\AtvDocument
   *   If removal succeeed.
   *
   * @throws \Drupal\helfi_atv\AtvAuthFailedException
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \Drupal\helfi_helsinki_profiili\TokenExpiredException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function deleteAttachmentByUrl(string $attachmentUrl): AtvDocument|bool|array|FileInterface;

  /**
   * Delete document attachment from ATV via intagration Id.
   *
   * @param string $integrationId
   *   Full URI of the attachment.
   *
   * @return array|bool|\Drupal\file\FileInterface|\Drupal\helfi_atv\AtvDocument
   *   If removal succeeed.
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function deleteAttachmentViaIntegrationId(string $integrationId): AtvDocument|bool|array|FileInterface;

  /**
   * Upload single attachment.
   *
   * File has to be saved to managed files for easier processing. Make sure file
   * is deleted after since this method does not delete.
   *
   * @param string $documentId
   *   Id of the document for this attachment.
   * @param string $filename
   *   Filename of the attachment.
   * @param \Drupal\file\Entity\File $file
   *   File to be uploaded.
   *
   * @return mixed
   *   Did upload succeed?
   *
   * @throws \Drupal\helfi_atv\AtvDocumentNotFoundException
   * @throws \Drupal\helfi_atv\AtvFailedToConnectException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function uploadAttachment(string $documentId, string $filename, File $file): mixed;

}
