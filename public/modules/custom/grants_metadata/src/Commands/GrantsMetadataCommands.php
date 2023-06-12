<?php

namespace Drupal\grants_metadata\Commands;

use Drupal\helfi_atv\AtvService;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class GrantsMetadataCommands extends DrushCommands {

  /**
   * Use ATV.
   *
   * @var \Drupal\helfi_atv\AtvService
   */
  public AtvService $atvService;

  /**
   * Constructor.
   *
   * @param \Drupal\helfi_atv\AtvService $atvService
   *   ATV service.
   */
  public function __construct(AtvService $atvService) {
    parent::__construct();

    $this->atvService = $atvService;

  }

  /**
   * Command description here.
   *
   * @param array $users
   *   Comma separated list of users.
   *
   * @usage grants_metadata-commandName foo
   *   Usage description
   *
   * @command grants_metadata:deleteUserDataAtv
   * @aliases duda
   */
  public function deleteUserDocs(
    array $users) {

    $this->logger()->success(dt(print_r($users, 1)));
  }

  /**
   * Command description here.
   *
   * @param mixed $arg1
   *   Argument description.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option option-name
   *   Description
   * @usage grants_metadata-commandName foo
   *   Usage description
   *
   * @command grants_metadata:commandName
   * @aliases foo
   */
  public function commandName(mixed $arg1, array $options = ['option-name' => 'default']) {
    $this->logger()->success(dt('Achievement unlocked.'));
  }

}
