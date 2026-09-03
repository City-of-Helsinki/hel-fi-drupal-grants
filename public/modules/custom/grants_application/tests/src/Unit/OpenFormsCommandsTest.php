<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Tests\UnitTestCase;
use Drupal\grants_application\Drush\Commands\OpenFormsCommands;
use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * @covers \Drupal\grants_application\Drush\Commands\OpenFormsCommands
 *
 * @group grants_application
 */
final class OpenFormsCommandsTest extends UnitTestCase {

  /**
   * The environment the command sees, restored after every test.
   */
  private string|false $originalEnv;

  /**
   * Collects the command's output.
   */
  private BufferedOutput $output;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
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
   * Builds the command with a stubbed form settings service.
   *
   * @param array<int, array<string, mixed>> $formTypes
   *   The form types the service reports.
   * @param array<string, mixed> $settings
   *   Keyed by form identifier: TRUE or FALSE for the open state, or an
   *   exception to throw for that form.
   *
   * @return \Drupal\grants_application\Drush\Commands\OpenFormsCommands
   *   The command, ready to run.
   */
  private function command(array $formTypes, array $settings = []): OpenFormsCommands {
    $service = $this->createMock(FormSettingsServiceInterface::class);
    $service->method('getFormConfig')->willReturn($formTypes);

    $service->method('getFormSettings')->willReturnCallback(
      function (int|string $id, ?string $identifier = NULL) use ($settings) {
        $value = $settings[$identifier] ?? FALSE;
        if ($value instanceof \Exception) {
          throw $value;
        }
        // FormSettings is final, so build a real one. A continuous period is
        // always open; a period in the past never is.
        return new FormSettings(
          $value ? ['continuous' => TRUE] : [
            'continuous' => FALSE,
            'application_open' => '2020-01-01T00:00:00',
            'application_close' => '2020-12-31T23:59:59',
          ],
          [], [], [],
        );
      }
    );

    // ApplicationMetadata is final too, so the branch that updates one is
    // covered by the kernel test instead.
    $service->method('getFormSettingsMetadata')->willReturn(NULL);

    // getPath() is relative to DRUPAL_ROOT, so the fixture check below runs
    // against the module's real fixtures.
    $extensionList = $this->createMock(ModuleExtensionList::class);
    $extensionList->method('getPath')->willReturn('modules/custom/grants_application');

    $command = new OpenFormsCommands($service, $extensionList);
    $command->setInput(new ArrayInput([]));
    $command->setOutput($this->output);

    return $command;
  }

  /**
   * A form type entry.
   *
   * @param string $identifier
   *   The form identifier.
   * @param int $id
   *   The application type id.
   *
   * @return array<string, mixed>
   *   The entry.
   */
  private function formType(string $identifier, int $id = 47): array {
    return ['id' => $id, 'form_identifier' => $identifier];
  }

  /**
   * Tests that the command refuses to run in production.
   */
  public function testRefusesToRunInProduction(): void {
    putenv('APP_ENV=production');

    $result = $this->command([$this->formType('kuva_toiminta')])->openForms();

    $this->assertSame(DrushCommands::EXIT_FAILURE, $result);
    $this->assertStringContainsString('PROD', $this->output->fetch());
  }

  /**
   * A closed form without metadata is reported rather than changed.
   */
  public function testReportsFormsGovernedByTheirFixture(): void {
    $command = $this->command(
      [$this->formType('kuva_toiminta')],
      ['kuva_toiminta' => FALSE],
    );

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $command->openForms());

    $output = $this->output->fetch();
    $this->assertStringContainsString('kuva_toiminta', $output);
    $this->assertStringContainsString('application-metadata', $output);
  }

  /**
   * Forms that are open, have no fixture, or no identifier are left alone.
   */
  public function testCountsOpenFormsAndSkipsNonReactForms(): void {
    $command = $this->command(
      [
        $this->formType('kuva_toiminta'),
        // A Webform application: listed in form_types, but has no fixture.
        $this->formType('hyte_yleisavustus', 71),
        ['id' => 99],
      ],
      ['kuva_toiminta' => TRUE],
    );

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $command->openForms());

    $output = $this->output->fetch();
    $this->assertStringContainsString('already open: 1', $output);
    $this->assertStringContainsString('skipped: 1', $output);
    $this->assertStringContainsString('Every React form is open', $output);
  }

  /**
   * Tests that an unreadable form is warned about rather than fatal.
   */
  public function testWarnsWhenFormCannotBeRead(): void {
    $command = $this->command(
      [$this->formType('kuva_toiminta')],
      ['kuva_toiminta' => new \Exception('Unable to load settings')],
    );

    $this->assertSame(DrushCommands::EXIT_SUCCESS, $command->openForms());
    $this->assertStringContainsString('Unable to load settings', $this->output->fetch());
  }

}
