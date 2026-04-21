<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_atv\Kernel;

use Drupal\helfi_helsinki_profiili\HelsinkiProfiiliUserData;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Base class for ATV module kernel tests.
 */
abstract class AtvKernelTestBase extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Drupal.
    'system',
    'file',
    'user',
    // Contrib.
    'externalauth',
    'openid_connect',
    // Helfi modules.
    'helfi_api_base',
    'helfi_atv',
    'helfi_atv_test',
    'helfi_helsinki_profiili',
    // Helsinki profiili requires audit log unnecessarily.
    'helfi_audit_log',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $mock = $this->createMock(HelsinkiProfiiliUserData::class);
    $mock->method('getApiAccessTokens')->willReturn([
      'tokenName' => 'tokenFromMockHelsinkiProfiiliUserData',
    ]);
    $this->container->set('helfi_helsinki_profiili.userdata', $mock);

    $this->installConfig(['helfi_atv']);

    putenv('ATV_API_KEY=fake');
    putenv('ATV_USE_TOKEN_AUTH=true');
    putenv('ATV_TOKEN_NAME=tokenName');
    putenv('ATV_BASE_URL=127.0.0.1');
    putenv('ATV_VERSION=1.1');
    putenv('ATV_USE_CACHE=false');
    putenv('APP_ENV=UNIT_TEST');
    putenv('ATV_SERVICE=service');
    putenv('ATV_MAX_PAGES=10');

    $this->installEntitySchema('user');

    // User must have hard-coded role to be able to user ATV.
    $this->createRole([], 'helsinkiprofiili');
    $this->setUpCurrentUser()
      ->addRole('helsinkiprofiili')
      ->save();
  }

}
