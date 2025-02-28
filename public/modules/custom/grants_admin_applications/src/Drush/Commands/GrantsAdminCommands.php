<?php

declare(strict_types=1);

namespace Drupal\grants_admin_applications\Drush\Commands;

use Consolidation\AnnotatedCommand\Attributes;
use Drupal\grants_admin_applications\Service\HandleDocumentsBatchService;
use Drupal\grants_handler\Helpers;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Grants admin commands.
 */
final class GrantsAdminCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * Constructs a new instance.
   */
  public function __construct(
    #[Autowire(service: 'helfi_atv.atv_service')]
    private readonly AtvService $atv,
    private readonly HandleDocumentsBatchService $batchService,
  ) {
    parent::__construct();
  }

  /**
   * Clean test applications.
   */
  #[Attributes\Command(name: 'grants-tools:clean-test-applications')]
  #[Attributes\Argument(name: 'uid', description: 'Test user UUID')]
  #[Attributes\Option(name: 'older', description: 'Clean documents that are older than given time')]
  public function cleanTestApplications(
    string $uid,
    array $options = [
      'older' => '-1 month',
    ],
  ): int {
    if (Helpers::isProduction(Helpers::getAppEnv())) {
      $this->io()->error('This command should not be run in production environment');
      return self::EXIT_FAILURE;
    }

    try {
      $cutoff = new \DateTimeImmutable($options['older']);

      if ($cutoff >= new \DateTimeImmutable('-20 days')) {
        $this->io()->error('Avust2 deletes applications that are 10 days old during weekends. We refuse to delete test applications that might be present in the avust2.');
        return self::EXIT_FAILURE;
      }
    }
    catch (\Exception $e) {
      $this->io()->error($e->getMessage());
      return self::EXIT_FAILURE;
    }

    $documents = $this->atv->searchDocuments(['user_id' => $uid]);

    // Only process documents that are older that $cutoff.
    $documents = array_filter($documents, static fn (AtvDocument $document) => $cutoff > new \DateTimeImmutable($document->getUpdatedAt()));

    // Ensure that no production documents are processed
    // in case wrong ATV api keys are used by accident.
    $documents = array_filter($documents, static fn (AtvDocument $document) => $document->getMetadata()['appenv'] !== 'PROD');

    if ($documents) {
      $this->batchService->run($documents);

      drush_backend_batch_process();
    }

    return self::EXIT_SUCCESS;
  }

}
