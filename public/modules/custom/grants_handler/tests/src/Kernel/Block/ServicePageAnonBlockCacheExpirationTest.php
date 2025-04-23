<?php

namespace Drupal\Tests\grants_handler\Kernel\Block;

use Drupal\grants_handler\ApplicationStatusServiceInterface;
use Drupal\grants_handler\ServicePageBlockService;
use Drupal\KernelTests\KernelTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\grants_handler\Plugin\Block\ServicePageAnonBlock;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Tests cache expiration logic in ServicePageAnonBlock.
 *
 * @group grants_handler
 */
class ServicePageAnonBlockCacheExpirationTest extends KernelTestBase {

  use NodeCreationTrait;
  use ProphecyTrait;

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

  /**
   * Test cache max age changes when webform start/end date changes.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testMaxAgeReactsToApplicationTimes() {
    // Create a webform.
    $webform = Webform::create([
      'id' => 'test_webform_expiry',
      'title' => 'Expire me',
    ]);
    // Set the applicationOpen and applicationClose dates.
    $webform->setThirdPartySetting('grants_metadata', 'applicationOpen', date('Y-m-d H:i:s', strtotime('+300 seconds')));
    $webform->setThirdPartySetting('grants_metadata', 'applicationClose', date('Y-m-d H:i:s', strtotime('+600 seconds')));
    $webform->save();

    $container = \Drupal::getContainer();

    // Create a service page block service mock.
    $servicePageBlockServiceMock = $this->createMock(ServicePageBlockService::class);
    $servicePageBlockServiceMock->method('loadServicePageWebform')->willReturn($webform);
    $servicePageBlockServiceMock->method('isCorrectApplicantType')->willReturn(TRUE);

    // Create an application status service mock.
    $applicationStatusServiceMock = $this->createMock(ApplicationStatusServiceInterface::class);
    $applicationStatusServiceMock->method('isApplicationOpen')->willReturn(TRUE);

    // Create the block.
    $block = new ServicePageAnonBlock(
      [],
      'grants_handler_service_page_anon_block',
      ['provider' => 'grants_handler'],
      $container->get('current_route_match'),
      $container->get('current_user'),
      $servicePageBlockServiceMock,
      $applicationStatusServiceMock,
      $container->get('cache.default'),
      $container->get('datetime.time'),
    );

    // Get the cache max age and assert it is between 290 and 600 seconds.
    $maxAge1 = $block->getCacheMaxAge();
    $this->assertGreaterThan(290, $maxAge1);
    $this->assertLessThanOrEqual(600, $maxAge1);

    // Simulate a change to the applicationOpen time; move it further
    // in the future.
    $webform->setThirdPartySetting('grants_metadata', 'applicationOpen', date('Y-m-d H:i:s', strtotime('+500 seconds')));
    $webform->save();

    // Force reload of the block and assert the cache max age has changed.
    $maxAge2 = $block->getCacheMaxAge();
    $this->assertNotEquals($maxAge1, $maxAge2);

    // Check that build() content reflects the application being open.
    $build = $block->build();
    $this->assertArrayHasKey('#theme', $build['content']);
    $this->assertEquals('grants_service_page_block', $build['content']['#theme']);
    $this->assertEquals('anon', $build['content']['#auth']);

    // Simulate application closed state.
    $applicationStatusServiceMock = $this->createMock(ApplicationStatusServiceInterface::class);
    $applicationStatusServiceMock->method('isApplicationOpen')->willReturn(FALSE);

    $blockClosed = new ServicePageAnonBlock(
      [],
      'grants_handler_service_page_anon_block',
      ['provider' => 'grants_handler'],
      $container->get('current_route_match'),
      $container->get('current_user'),
      $servicePageBlockServiceMock,
      $applicationStatusServiceMock,
      $container->get('cache.default'),
      $container->get('datetime.time'),
    );

    $buildClosed = $blockClosed->build();
    $this->assertEquals('not_open', $buildClosed['content']['#auth']);
    $this->assertStringContainsString('not open', (string) $buildClosed['content']['#text']);
  }

}
