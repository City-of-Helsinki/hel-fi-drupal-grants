<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_handler\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Base class for kernel tests.
 */
abstract class GrantsHandlerKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'externalauth',
    'entity_reference_revisions',
    'grants_handler',
    'grants_profile',
    'grants_mandate',
    'grants_metadata',
    'grants_attachments',
    'grants_events',
    'helfi_yjdh',
    'helfi_audit_log',
    'locale',
    'language',
    'block',
    'block_content',
    'path_alias',
    'file',
    'field',
    'helfi_api_base',
    'helfi_atv',
    'helfi_helsinki_profiili',
    'helfi_tunnistamo',
    'node',
    'openid_connect',
    'options',
    'openid_connect_logout_redirect',
    'paragraphs',
    'system',
    'taxonomy',
    'text',
    'user',
    'webform',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('webform', ['webform']);
    $this->installSchema('locale', [
      'locales_source',
      'locales_target',
      'locales_location',
    ]);
    $this->installEntitySchema('webform');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('paragraphs_type');
    $this->installEntitySchema('block_content');

    $this->installConfig([
      'externalauth',
      'grants_profile',
      'grants_mandate',
      'grants_metadata',
      'grants_attachments',
      'grants_events',
      'grants_handler',
      'helfi_yjdh',
      'helfi_audit_log',
      'locale',
      'language',
      'file',
      'field',
      'helfi_api_base',
      'helfi_atv',
      'helfi_tunnistamo',
      'openid_connect',
      'openid_connect_logout_redirect',
      'paragraphs',
      'system',
      'webform',
    ]);
  }

}
