<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\grants_application\Drush\Commands\OpenFormsCommands;
use Drupal\grants_application\Entity\ApplicationMetadata;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Kernel test for OpenFormsCommands.
 *
 * @covers \Drupal\grants_application\Drush\Commands\OpenFormsCommands
 *
 * @group grants_application
 */
final class OpenFormsCommandsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var string[]
   */
  protected static $modules = [
    'grants_application',
  ];

  /**
   * The form the test closes and reopens.
   */
  private const FORM_IDENTIFIER = 'kuva_toiminta';

  /**
   * The application type id of that form.
   */
  private const FORM_TYPE_ID = 47;

  /**
   * Collects the command's output.
   */
  private BufferedOutput $output;

  /**
   * The environment the command sees, restored after the test.
   */
  private string|false $originalEnv;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('application_metadata');
    $this->originalEnv = getenv('APP_ENV');
    putenv('APP_ENV=LOCAL');
    $this->output = new BufferedOutput();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    putenv($this->originalEnv === FALSE ? 'APP_ENV' : 'APP_ENV=' . $this->originalEnv);
    parent::tearDown();
  }

  /**
   * Creates metadata for the form with a period that has lapsed.
   *
   * @return \Drupal\grants_application\Entity\ApplicationMetadata
   *   The saved metadata.
   */
  private function createClosedMetadata(): ApplicationMetadata {
    $storage = $this->container->get('entity_type.manager')->getStorage('application_metadata');
    $metadata = $storage->create([
      'form_identifier' => self::FORM_IDENTIFIER,
      'application_type_id' => self::FORM_TYPE_ID,
      'application_open' => '2020-01-01T00:00:00',
      'application_close' => '2020-12-31T23:59:59',
      'application_continuous' => FALSE,
    ]);
    assert($metadata instanceof ApplicationMetadata);
    $metadata->save();

    return $metadata;
  }

  /**
   * Builds the command with the real services.
   *
   * @return \Drupal\grants_application\Drush\Commands\OpenFormsCommands
   *   The command, ready to run.
   */
  private function command(): OpenFormsCommands {
    $command = new OpenFormsCommands(
      $this->container->get(FormSettingsServiceInterface::class),
      $this->container->get(ModuleExtensionList::class),
    );
    $command->setInput(new ArrayInput([]));
    $command->setOutput($this->output);

    return $command;
  }

  /**
   * Reloads the metadata entity.
   *
   * @param int|string $id
   *   The entity id.
   *
   * @return \Drupal\grants_application\Entity\ApplicationMetadata
   *   The reloaded metadata.
   */
  private function reload(int|string $id): ApplicationMetadata {
    $storage = $this->container->get('entity_type.manager')->getStorage('application_metadata');
    $storage->resetCache([$id]);
    $metadata = $storage->load($id);
    assert($metadata instanceof ApplicationMetadata);

    return $metadata;
  }

  /**
   * Tests that a lapsed period is widened on the metadata entity.
   */
  public function testOpensClosedFormThroughItsMetadata(): void {
    $metadata = $this->createClosedMetadata();

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $this->command()->openForms());

    $reloaded = $this->reload($metadata->id());
    $now = new \DateTimeImmutable();
    $this->assertLessThan($now, new \DateTimeImmutable($reloaded->application_open->value));
    $this->assertGreaterThan($now, new \DateTimeImmutable($reloaded->application_close->value));
    $this->assertStringContainsString(self::FORM_IDENTIFIER, $this->output->fetch());
  }

  /**
   * Tests that a dry run reports without saving.
   */
  public function testDryRunLeavesTheMetadataAlone(): void {
    $metadata = $this->createClosedMetadata();

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $this->command()->openForms(['dry-run' => TRUE]));

    $reloaded = $this->reload($metadata->id());
    $this->assertSame('2020-01-01T00:00:00', $reloaded->application_open->value);
    $this->assertStringContainsString('would be opened', $this->output->fetch());
  }

}
