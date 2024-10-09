<?php

namespace Drupal\grants_attachments;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileUsage\FileUsageInterface;

/**
 * This service handles attachment removals from system.
 */
class AttachmentRemover {

  /**
   * The file.usage service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected FileUsageInterface $fileUsage;

  /**
   * The Messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Database connection for interacting with it.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannel|\Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $loggerChannel;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The storage handler class for files.
   *
   * @var \Drupal\file\FileStorage
   */
  private $fileStorage;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  private $fileSystem;

  /**
   * Debug prints?
   *
   * @var bool
   */
  protected bool $debug;

  /**
   * Constructs an AttachmentRemover object.
   *
   * @param \Drupal\file\FileUsage\FileUsageInterface $file_usage
   *   The file.usage service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Print message to user.
   * @param \Drupal\Core\Logger\LoggerChannelFactory $loggerFactory
   *   Log things.
   * @param \Drupal\Core\Database\Connection $connection
   *   Interact with database.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\File\FileSystem $fileSystem
   *   File system.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    FileUsageInterface $file_usage,
    MessengerInterface $messenger,
    LoggerChannelFactoryInterface $loggerFactory,
    Connection $connection,
    AccountProxyInterface $currentUser,
    EntityTypeManagerInterface $entityTypeManager,
    FileSystem $fileSystem,
  ) {
    $this->fileUsage = $file_usage;
    $this->messenger = $messenger;
    $this->loggerChannel = $loggerFactory->get('grants_attachments');
    $this->connection = $connection;
    $this->currentUser = $currentUser;
    $this->fileStorage = $entityTypeManager->getStorage('file');
    $this->fileSystem = $fileSystem;
  }

  /**
   * If debug is on or not.
   *
   * @return bool
   *   TRue or false depending on if debug is on or not.
   */
  public function isDebug(): bool {
    return $this->debug;
  }

  /**
   * Set debug.
   *
   * @param bool $debug
   *   True or false.
   */
  public function setDebug(bool $debug): void {
    $this->debug = $debug;
  }

  /**
   * Remove given fileIds from filesystem & database.
   *
   * @param array $attachments
   *   List of file ifs to remove.
   * @param array $uploadResults
   *   Array containing status of each file uploaded.
   * @param string $applicationNumber
   *   Generated application number.
   * @param bool $debug
   *   Is debug mode on or off.
   * @param int $webFormSubmissionId
   *   Submission id.
   *
   * @return bool
   *   Return status.
   *
   * @throws \Exception
   */
  public function removeGrantAttachments(
    array $attachments,
    array $uploadResults,
    string $applicationNumber,
    bool $debug,
    int $webFormSubmissionId,
  ): bool {
    $this->setDebug($debug);

    // If no attachments are passed, just return true.
    if (empty($attachments)) {
      return TRUE;
    }

    $retval = FALSE;

    // Loop fileids.
    foreach ($attachments as $fileId) {
      $file = $this->fileStorage->load($fileId);

      if ($file == NULL) {
        continue;
      }

      if ($uploadResults[$fileId]['upload'] === TRUE) {
        $retval = $this->deleteFile($file, $applicationNumber, $webFormSubmissionId) || $retval;
      }
      else {
        $this->saveFailedUpload($file, $applicationNumber, $webFormSubmissionId);
      }
    }

    return $retval;
  }

  /**
   * Delete a file and log the action.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file entity to delete.
   *
   * @return bool
   *   TRUE if the file was deleted, FALSE otherwise.
   * @throws \Exception
   */
  private function deleteFile(File $file): bool {
    try {
      $filename = $file->getFilename();
      $file->delete();

      // Make sure that no rows remain for this FID.
      $this->connection->delete('grants_attachments')
        ->condition('fid', $file->id())
        ->execute();

      if ($this->isDebug()) {
        $this->loggerChannel->notice('Removed file entity & db log row: @filename', [
          '@filename' => $filename,
        ]);
      }

      return TRUE;
    }
    catch (EntityStorageException $e) {
      $this->messenger->addError('File deletion failed');
      return FALSE;
    }
  }

  /**
   * Save a failed upload to the database for later processing.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file entity.
   * @param string $applicationNumber
   *   The application number.
   * @param int $webFormSubmissionId
   *   The webform submission ID.
   */
  private function saveFailedUpload(
    File $file,
    string $applicationNumber,
    int $webFormSubmissionId
  ): void {
    try {
      $this->connection->insert('grants_attachments')
        ->fields([
          'uid' => $this->currentUser->id(),
          'webform_submission_id' => $webFormSubmissionId,
          'grants_application_number' => $applicationNumber,
          'fid' => $file->id(),
        ])
        ->execute();

      $this->loggerChannel->error('Upload failed, files are saved for retry.');
    }
    catch (\Exception $e) {
      $this->loggerChannel->error('Upload failed, removal failed, adding db row failed: @filename', [
        '@filename' => $file->getFilename(),
      ]);
    }
  }

  /**
   * The purgeAllAttachments functions.
   *
   * This function purges all directories and attachments
   * inside $pathsToClear that don't belong to an active session.
   */
  public function purgeAllAttachments(): void {
    $activeSessions = $this->fetchActiveSessions();
    $pathsToClear = [
      "private://grants_attachments",
      "private://grants_messages",
      "private://grants_profile",
    ];

    foreach ($pathsToClear as $schema) {
      $this->purgeInactiveSessionDirectories($schema, $activeSessions);
    }
  }

  /**
   * The fetchActiveSessions function.
   *
   * This function fetches the active session IDs
   * from the DB. Not that the session IDs are stored
   * and returned in a hashed format.
   *
   * @return array
   *   Active session IDs.
   */
  private function fetchActiveSessions(): array {
    $result = $this->connection->query("SELECT sid FROM {sessions}")
      ->fetchAll();
    return array_map(fn($item) => $item->sid, $result);
  }

  /**
   * The purgeInactiveSessionDirectories function.
   *
   * This function purges directories and their attachments
   * that don't belong to an active session. This is done
   * by calling removeSessionDirectory(), which calls removeSessionAttachment().
   *
   * @param string $schema
   *   The base schema path.
   * @param array $activeSessions
   *   List of active session IDs.
   */
  private function purgeInactiveSessionDirectories(string $schema, array $activeSessions): void {
    $directoryToClear = $this->fileSystem->realpath($schema);
    if (!is_dir($directoryToClear)) {
      return;
    }

    $directories = scandir($directoryToClear);
    if (!$directories) {
      return;
    }

    $sessionDirectories = array_diff($directories, ['.', '..']);
    foreach ($sessionDirectories as $sessionDirectory) {
      // The directories are named after hashed session IDs.
      // If a session isn't active, we remove any files associated with it.
      if (!in_array($sessionDirectory, $activeSessions)) {
        $sessionDirectoryPath = "$directoryToClear/$sessionDirectory";
        $this->removeSessionDirectory($sessionDirectoryPath);
      }
    }
  }

  /**
   * The removeSessionDirectory function.
   *
   * This function removes a session directory and all the files
   * inside it by calling removeSessionAttachment().
   *
   * @param string $sessionDirectoryPath
   *   A path to a session directory.
   */
  public function removeSessionDirectory(string $sessionDirectoryPath): void {
    $directoryContent = scandir($sessionDirectoryPath);

    if ($directoryContent) {
      $sessionAttachments = array_diff($directoryContent, ['.', '..']);

      foreach ($sessionAttachments as $sessionFilename) {
        $fileUri = "$sessionDirectoryPath/$sessionFilename";
        $this->removeSessionAttachment($fileUri);
      }
    }

    $this->loggerChannel->notice("Removing session directory: $sessionDirectoryPath");
    @rmdir($sessionDirectoryPath);
  }

  /**
   * The removeSessionAttachment function.
   *
   * This function deletes a file entity if it exists.
   * Otherwise, delete the file directly.
   *
   * @param string $fileUri
   *   URI of the file to delete.
   */
  private function removeSessionAttachment(string $fileUri): void {
    $fileEntities = $this->fileStorage->loadByProperties(['uri' => $fileUri]);
    $fileEntity = reset($fileEntities);

    if ($fileEntity) {
      try {
        $this->loggerChannel->notice("Removing file entity with URI: $fileUri");
        $fileEntity->delete();
      }
      catch (\Exception $e) {
        $this->loggerChannel->error('Error purging leftover attachments: ' . $e->getMessage());
        $this->messenger->addError('Error purging leftover attachments');
      }
    }
    else {
      $this->loggerChannel->notice("Removing file with URI: $fileUri");
      @unlink($fileUri);
    }
  }

}
