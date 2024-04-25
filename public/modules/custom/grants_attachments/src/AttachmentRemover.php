<?php

namespace Drupal\grants_attachments;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
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
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected LoggerChannel $loggerChannel;

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
   */
  public function __construct(
    FileUsageInterface $file_usage,
    MessengerInterface $messenger,
    LoggerChannelFactory $loggerFactory,
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
   */
  public function removeGrantAttachments(
    array $attachments,
    array $uploadResults,
    string $applicationNumber,
    bool $debug,
    int $webFormSubmissionId
  ): bool {
    $this->setDebug($debug);
    $retval = FALSE;

    $currentUser = $this->currentUser;

    // If no attachments are passed, just return true.
    if (empty($attachments)) {
      return TRUE;
    }

    // Loop fileids.
    foreach ($attachments as $fileId) {

      // Load file.
      /** @var \Drupal\file\Entity\File|null $file */
      $file = $this->fileStorage->load($fileId);

      if ($file == NULL) {
        continue;
      }

      $filename = $file->getFilename();

      // Only if we have positive upload result remove file.
      if ($uploadResults[$fileId]['upload'] === TRUE) {
        try {
          // And delete it.
          $file->delete();
          $retval = TRUE;

          // Make sure that no rows remain for this FID.
          $this->connection->delete('grants_attachments')
            ->condition('fid', $file->id())
            ->execute();

          if ($this->isDebug()) {
            $this->loggerChannel->notice('Removed file entity & db log row: @filename', [
              '@filename' => $filename,
            ]);
          }
        }
        catch (EntityStorageException $e) {
          $this->messenger->addError('File deletion failed');
        }
      }
      else {
        try {
          // Add failed/skipped deletion to db table for later processing.
          $this->connection->insert('grants_attachments')
            ->fields([
              'uid' => $currentUser->id(),
              'webform_submission_id' => $webFormSubmissionId,
              'grants_application_number' => $applicationNumber,
              'fid' => $file->id(),
            ])
            ->execute();

          $this->loggerChannel->error('Upload failed, files are saved for retry.');

        }
        catch (\Exception $e) {
          $this->loggerChannel->error('Upload failed, removal failed, adding db row failed: @filename', [
            '@filename' => $filename,
          ]);
        }
      }
    }
    return $retval;
  }

  /**
   * Removes all files from attachment path.
   */
  public function purgeAllAttachments(): void {

    /** @var \Drupal\file\FileStorage $fileStorage */
    $fileStorage = $this->fileStorage;
    $database = $this->connection;
    $query = $database->query("SELECT sid FROM {sessions}");
    $result = $query->fetchAll();

    $activeSessions = array_map(fn($item) => $item->sid, $result);

    $pathsToClear = [
      "private://grants_attachments",
      "private://grants_messages",
      "private://grants_profile",
    ];
    // Loop all private filepaths.
    foreach ($pathsToClear as $schema) {
      // Figure out realpath of the schema folder.
      $attachmentPath = $this->fileSystem->realpath($schema);
      if (is_dir($attachmentPath)) {
        // Scan folder.
        $sessionFolders = array_diff(scandir($attachmentPath), ['.', '..']);
        // Loop session folders.
        foreach ($sessionFolders as $sessionHash) {
          // Remove files only for inactive sessions.
          if (!in_array($sessionHash, $activeSessions)) {
            // Create path for session hash.
            $sessionPath = $attachmentPath . '/' . $sessionHash;

            // Scan directory for this path.
            $pathScan = scandir($sessionPath);
            if (is_array($pathScan)) {
              $sessionItems = array_diff($pathScan, ['.', '..']);
            }
            else {
              $sessionItems = [];
            }
            // If we have items.
            foreach ($sessionItems as $sessionFilename) {
              // Try to load file entity.
              $fileUri = $sessionPath . '/' . $sessionFilename;
              $fileArray = $fileStorage->loadByProperties([
                'uri' => $fileUri,
              ]);
              /** @var \Drupal\file\Entity\File $fileEntity */
              $fileEntity = reset($fileArray);
              // If entity does not exist.
              if (!$fileEntity) {
                // Just remove file.
                @unlink($fileUri);
              }
              else {
                // If it exist, remove file and entity.
                try {
                  $fileEntity->delete();
                }
                catch (\Exception $e) {
                  $this->loggerChannel->error('Error purging leftover attachments');
                  $this->messenger->addError('Error purging leftover attachments');
                }
              }
            }
            // Remove session folder after all have been deleted.
            @rmdir($sessionPath);
          }
        }
      }
    }
  }

}
