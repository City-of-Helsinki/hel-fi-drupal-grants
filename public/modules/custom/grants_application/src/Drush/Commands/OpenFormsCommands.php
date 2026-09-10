<?php

declare(strict_types=1);

namespace Drupal\grants_application\Drush\Commands;

use Consolidation\AnnotatedCommand\Attributes;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\grants_application\Form\FormSettingsServiceInterface;
use Drupal\grants_application\Helper;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * Opens the React form application periods.
 */
final class OpenFormsCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * The environments the command may run in.
   *
   * Anything starting with "LOCAL" is allowed as well.
   */
  private const ALLOWED_ENVIRONMENTS = ['DEV', 'TEST', 'STAGE'];

  /**
   * How far in the future the application period is pushed.
   */
  private const YEARS_OPEN = 10;

  public function __construct(
    private readonly FormSettingsServiceInterface $formSettingsService,
    private readonly ModuleExtensionList $moduleExtensionList,
  ) {
    parent::__construct();
  }

  /**
   * Opens the application period of every React form.
   *
   * @param array<string, mixed> $options
   *   The command options.
   *
   * @return int
   *   The exit code.
   */
  #[Attributes\Command(name: 'grants-application:open-forms', aliases: ['gaof'])]
  #[Attributes\Option(name: 'dry-run', description: 'Report what would change without saving anything.')]
  #[Attributes\Usage(name: 'drush grants-application:open-forms --dry-run', description: 'List the forms that are closed.')]
  public function openForms(array $options = ['dry-run' => FALSE]): int {
    $appEnv = Helper::getAppEnv();

    if (!$this->isEnvironmentAllowed($appEnv)) {
      $this->io()->error(sprintf('Refusing to run in the "%s" environment.', $appEnv ?: 'unknown'));
      return self::EXIT_FAILURE;
    }

    $dryRun = (bool) $options['dry-run'];
    $opened = [];
    $alreadyOpen = 0;
    $fixtureGoverned = [];
    $skipped = [];

    foreach ($this->formSettingsService->getFormConfig('form_types') as $formType) {
      $identifier = $formType['form_identifier'] ?? NULL;

      if (!$identifier) {
        continue;
      }

      // form_types.json also lists the Webform applications, which have no
      // React fixture and are not this command's business.
      if (!$this->hasFixture($identifier)) {
        $skipped[] = $identifier;
        continue;
      }

      try {
        $settings = $this->formSettingsService->getFormSettings($formType['id'], $identifier);
      }
      catch (\Exception $e) {
        $this->io()->warning(sprintf('%s: %s', $identifier, $e->getMessage()));
        continue;
      }

      if ($settings->isApplicationOpen()) {
        $alreadyOpen++;
        continue;
      }

      $metadata = $this->formSettingsService->getFormSettingsMetadata($formType['id'], $identifier);

      if (!$metadata) {
        $fixtureGoverned[] = $identifier;
        continue;
      }

      $open = new \DateTimeImmutable('yesterday');
      $close = $open->add(new \DateInterval(sprintf('P%dY', self::YEARS_OPEN)));

      if (!$dryRun) {
        $metadata->set('application_open', $open->format('Y-m-d\TH:i:s'));
        $metadata->set('application_close', $close->format('Y-m-d\TH:i:s'));
        $metadata->save();
      }

      $opened[] = $identifier;
    }

    $this->report($opened, $alreadyOpen, $fixtureGoverned, $skipped, $dryRun);

    return self::EXIT_SUCCESS;
  }

  /**
   * Checks whether a form identifier has a React fixture.
   *
   * @param string $identifier
   *   The form identifier.
   *
   * @return bool
   *   TRUE when the form is a React form.
   */
  private function hasFixture(string $identifier): bool {
    $modulePath = $this->moduleExtensionList->getPath('grants_application');
    $root = defined('DRUPAL_ROOT') ? DRUPAL_ROOT . '/' : '';

    return is_file(sprintf('%s%s/fixtures/%s/settings.json', $root, $modulePath, $identifier));
  }

  /**
   * Checks whether the command may run in the given environment.
   *
   * @param string $appEnv
   *   The environment name.
   *
   * @return bool
   *   TRUE when the command may run.
   */
  private function isEnvironmentAllowed(string $appEnv): bool {
    // Helper::getAppEnv() passes an unrecognised value through as it is, and
    // local environments are named freely, so compare in upper case.
    $appEnv = strtoupper($appEnv);

    return in_array($appEnv, self::ALLOWED_ENVIRONMENTS, TRUE) || str_starts_with($appEnv, 'LOCAL');
  }

  /**
   * Reports what the command did.
   *
   * @param array<int, string> $opened
   *   The forms that were opened.
   * @param int $alreadyOpen
   *   How many forms were open to begin with.
   * @param array<int, string> $fixtureGoverned
   *   Closed forms whose period comes from the fixture.
   * @param array<int, string> $skipped
   *   Identifiers that are not React forms.
   * @param bool $dryRun
   *   Whether anything was actually saved.
   */
  private function report(array $opened, int $alreadyOpen, array $fixtureGoverned, array $skipped, bool $dryRun): void {
    $this->io()->writeln(sprintf('React forms already open: %d', $alreadyOpen));
    $this->io()->writeln(sprintf('Not React forms, skipped: %d', count($skipped)));

    if ($opened) {
      $this->io()->listing($opened);
      $this->io()->success(sprintf(
        $dryRun ? '%d form(s) would be opened for %d years.' : '%d form(s) opened for %d years.',
        count($opened),
        self::YEARS_OPEN
      ));
    }

    if ($fixtureGoverned) {
      $this->io()->listing($fixtureGoverned);
      $this->io()->note(
        'The forms above are closed and have no application metadata entity, so their period comes from ' .
        'their settings.json fixture. Open them by editing the fixture, or by adding metadata for them at ' .
        '/admin/tools/application-metadata.'
      );
    }

    if (!$opened && !$fixtureGoverned) {
      $this->io()->success('Every React form is open.');
    }
  }

}
