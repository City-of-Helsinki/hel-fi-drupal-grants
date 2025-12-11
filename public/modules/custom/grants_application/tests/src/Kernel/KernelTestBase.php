<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel;

use Drupal\KernelTests\KernelTestBase as CoreKernelTestBase;

/**
 * Kernel test base for grants application tests.
 */
class KernelTestBase extends CoreKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'datetime',
    'entity',
    'externalauth',
    'field',
    'file',
    'grants_application',
    'grants_attachments',
    'grants_handler',
    'grants_events',
    'grants_metadata',
    'grants_mandate',
    'grants_profile',
    'helfi_atv',
    'helfi_av',
    'helfi_audit_log',
    'helfi_api_base',
    'helfi_helsinki_profiili',
    'helfi_yjdh',
    'language',
    'locale',
    'openid_connect',
    'openid_connect_logout_redirect',
    'options',
    'rest',
    'serialization',
    'system',
    'taxonomy',
    'text',
    'user',
    'webform',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->config('content_lock.settings')
      ->set('types.application_submission', ['*' => '*'])
      ->set('verbose', TRUE);
    $config->save();

    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('application_metadata');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['grants_application']);
  }

}
