<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Unit;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\grants_application\ApplicationEditPolicy;
use Drupal\grants_application\Form\FormSettings;
use Drupal\grants_handler\ApplicationStatusService;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the rules for changing React form applications.
 */
#[CoversClass(ApplicationEditPolicy::class)]
#[Group('grants_application')]
final class ApplicationEditPolicyTest extends UnitTestCase {

  /**
   * The policy under test.
   */
  private ApplicationEditPolicy $policy;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $statuses = [
      'DRAFT', 'SUBMITTED', 'RECEIVED', 'PREPARING', 'PENDING', 'PROCESSING',
      'CANCELLED', 'RESOLVED', 'READY', 'DONE', 'DELETED', 'CANCELED', 'CLOSED',
    ];
    $configFactory = $this->getConfigFactoryStub([
      'grants_metadata.settings' => [
        'third_party_options' => [
          'application_statuses' => array_combine($statuses, $statuses),
        ],
      ],
    ]);
    $loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);
    $loggerFactory->method('get')->willReturn($this->createMock(LoggerChannelInterface::class));

    $this->policy = new ApplicationEditPolicy(new ApplicationStatusService($configFactory, $loggerFactory));
  }

  /**
   * Builds form settings for an open or closed application round.
   */
  private function settings(bool $open): FormSettings {
    return new FormSettings([
      'continuous' => FALSE,
      'application_open' => $open ? '-1 day' : '-2 days',
      'application_close' => $open ? '+1 day' : '-1 day',
    ], [], [], []);
  }

  /**
   * Drafts can be created only while the round is open.
   */
  public function testCanCreateDraft(): void {
    $this->assertTrue($this->policy->canCreateDraft($this->settings(TRUE)));
    $this->assertFalse($this->policy->canCreateDraft($this->settings(FALSE)));
  }

  /**
   * Tests saving as a draft.
   */
  #[DataProvider('saveDraftProvider')]
  public function testCanSaveDraft(string $status, bool $open, bool $expected): void {
    $this->assertSame($expected, $this->policy->canSaveDraft($this->settings($open), $status));
  }

  /**
   * Data provider for testCanSaveDraft().
   *
   * @return array<array{string, bool, bool}>
   *   Status, whether the round is open and the expected result.
   */
  public static function saveDraftProvider(): array {
    return [
      ['DRAFT', TRUE, TRUE],
      ['DRAFT', FALSE, FALSE],
      ['SUBMITTED', TRUE, FALSE],
      ['RECEIVED', TRUE, FALSE],
      ['PROCESSING', TRUE, FALSE],
      ['RESOLVED', TRUE, FALSE],
      ['', TRUE, FALSE],
    ];
  }

  /**
   * Tests submitting.
   */
  #[DataProvider('submitProvider')]
  public function testCanSubmit(string $status, bool $open, bool $expected): void {
    $this->assertSame($expected, $this->policy->canSubmit($this->settings($open), $status));
  }

  /**
   * Data provider for testCanSubmit().
   *
   * @return array<array{string, bool, bool}>
   *   Status, whether the round is open and the expected result.
   */
  public static function submitProvider(): array {
    return [
      ['DRAFT', TRUE, TRUE],
      ['DRAFT', FALSE, FALSE],
      // A failed submission can be retried.
      ['SUBMITTED', TRUE, TRUE],
      ['SUBMITTED', FALSE, TRUE],
      ['RECEIVED', TRUE, FALSE],
      ['PREPARING', TRUE, FALSE],
      ['PROCESSING', TRUE, FALSE],
      ['RESOLVED', TRUE, FALSE],
      ['DONE', TRUE, FALSE],
      ['', TRUE, FALSE],
    ];
  }

  /**
   * Tests updating a submitted application.
   */
  #[DataProvider('updateSubmittedProvider')]
  public function testCanUpdateSubmitted(string $status, bool $expected): void {
    $this->assertSame($expected, $this->policy->canUpdateSubmitted($status));
  }

  /**
   * Data provider for testCanUpdateSubmitted().
   *
   * @return array<array{string, bool}>
   *   Status and the expected result.
   */
  public static function updateSubmittedProvider(): array {
    return [
      ['RECEIVED', TRUE],
      ['PREPARING', TRUE],
      ['DRAFT', FALSE],
      ['SUBMITTED', FALSE],
      ['PENDING', FALSE],
      ['PROCESSING', FALSE],
      ['RESOLVED', FALSE],
      ['READY', FALSE],
      ['DONE', FALSE],
      ['CLOSED', FALSE],
      ['', FALSE],
    ];
  }

}
