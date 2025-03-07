<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_admin_applications\Kernel;

use Drupal\grants_admin_applications\Drush\Commands\GrantsAdminCommands;
use Drupal\grants_admin_applications\Service\HandleDocumentsBatchService;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Drupal\KernelTests\KernelTestBase;
use Drush\Commands\DrushCommands;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Kernel tests for Drush commands.
 */
class DrushCommandTest extends KernelTestBase {

  use ProphecyTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'grants_admin_applications',
    'file',
    'helfi_helsinki_profiili',
    'helfi_api_base',
    'user',
    'externalauth',
    'openid_connect',
    'helfi_atv',
  ];

  /**
   * Tests drush command.
   */
  public function testDrushCommand(): void {
    $documents = [
      // Protected appenv.
      AtvDocument::create([
        'updated_at' => (new \DateTimeImmutable('2020-01-01'))->format(\DateTimeInterface::RFC3339),
        'metadata' => json_encode(['appenv' => 'PROD']),
      ]),
      // Too recent.
      AtvDocument::create([
        'updated_at' => (new \DateTimeImmutable('now'))->format(\DateTimeInterface::RFC3339),
        'metadata' => json_encode(['appenv' => 'TEST']),
      ]),
      // Should be deleted.
      AtvDocument::create([
        'updated_at' => (new \DateTimeImmutable('2020-01-01'))->format(\DateTimeInterface::RFC3339),
        'metadata' => json_encode(['appenv' => 'TEST']),
      ]),
    ];

    $atvSevice = $this->prophesize(AtvService::class);
    $this->container->set('helfi_atv.atv_service', $atvSevice->reveal());
    $atvSevice->searchDocuments(Argument::any())
      ->willReturn($documents);

    $batch = $this->prophesize(HandleDocumentsBatchService::class);
    $batch
      ->run(Argument::containing($documents[2]))
      ->shouldBeCalled();

    $command = new GrantsAdminCommands($atvSevice->reveal(), $batch->reveal());
    $this->assertEquals(DrushCommands::EXIT_SUCCESS, $command->cleanTestApplications('test-uid'));
  }

  /**
   * Tests drush command refuses to run if production APP_ENV is selected.
   */
  public function testAppEnv(): void {
    $documents = [
      // Should be deleted.
      AtvDocument::create([
        'updated_at' => (new \DateTimeImmutable('2020-01-01'))->format(\DateTimeInterface::RFC3339),
        'metadata' => json_encode(['appenv' => 'TEST']),
      ]),
    ];

    $atvSevice = $this->prophesize(AtvService::class);
    $this->container->set('helfi_atv.atv_service', $atvSevice->reveal());
    $atvSevice->searchDocuments(Argument::any())
      ->willReturn($documents);

    $batch = $this->prophesize(HandleDocumentsBatchService::class);

    // Replace APP_ENV.
    $appEnv = getenv('APP_ENV');
    putenv('APP_ENV=PROD');

    $command = new GrantsAdminCommands($atvSevice->reveal(), $batch->reveal());

    $command->restoreState(
      $this->prophesize(InputInterface::class)->reveal(),
      $this->prophesize(OutputInterface::class)->reveal(),
      $this->prophesize(SymfonyStyle::class)->reveal()
    );

    $this->assertEquals(DrushCommands::EXIT_FAILURE, $command->cleanTestApplications('test-uid'));

    // Restore APP_ENV.
    putenv("APP_ENV=$appEnv");
  }

}
