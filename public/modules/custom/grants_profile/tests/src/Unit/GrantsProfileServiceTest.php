<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_profile\Unit;

use Drupal\Component\Uuid\Php as Uuid;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\grants_profile\GrantsProfileCache;
use Drupal\grants_profile\GrantsProfileService;
use Drupal\grants_profile\ProfileConnector;
use Drupal\helfi_atv\AtvDocument;
use Drupal\helfi_atv\AtvService;
use Drupal\helfi_audit_log\AuditLogService;
use Drupal\helfi_audit_log\AuditLogServiceInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Unit tests for grants profile service.
 */
class GrantsProfileServiceTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * Tests creating new grants profile.
   */
  public function testProfileCreation(): void {
    $atv = $this->prophesize(AtvService::class);

    // searchDocuments return [] -> profile does not exist.
    $atv->searchDocuments(Argument::any(), Argument::any())
      ->willReturn([]);

    $profileConnector = $this->prophesize(ProfileConnector::class);
    $profileConnector
      ->getUserId()
      ->willReturn('123');

    $sut = $this->getSut(
      atvService: $atv->reveal(),
      profileConnector: $profileConnector->reveal(),
      grantsProfileCache: $this->createMockedGrantsProfileCache([
        'selected_company' => [
          'companyName' => 'Test',
          'type' => 'registered_community',
          'identifier' => 'test_company',
        ],
      ]),
    );

    // Should create new ATV document.
    $atv
      ->createDocument(Argument::any())
      ->will(static fn ($args) => AtvDocument::create($args[0]));

    $argumentTest = function (AtvDocument $document) {
      // Document should have deleted after date.
      $this->assertGreaterThan(
        new \DateTimeImmutable('now'),
        new \DateTimeImmutable($document->getDeleteAfter())
      );

      return TRUE;
    };

    $atv
      ->postDocument(Argument::that($argumentTest))
      ->shouldBeCalled()
      ->willReturn(AtvDocument::create([]));

    $sut->saveGrantsProfile([
      'bankAccounts' => [],
    ]);
  }

  /**
   * Tests updating grants profile deleted_after field.
   */
  public function testProfileDeleteAfterUpdate(): void {
    $doc = AtvDocument::create([
      'id' => 'test-document-1',
      'transaction_id' => '123',
      'type' => 'grants_profile',
      'delete_after' => '2020-01-01',
      'content' => [
        'bankAccounts' => [],
      ],
      'metadata' => [],
      'attachments' => [],
    ]);

    $atv = $this->prophesize(AtvService::class);

    // searchDocuments return [] -> profile does not exist.
    $atv->searchDocuments(Argument::any(), Argument::any())
      ->willReturn([$doc]);

    $sut = $this->getSut(
      atvService: $atv->reveal(),
      grantsProfileCache: $this->createMockedGrantsProfileCache([
        'selected_company' => [
          'companyName' => 'Test',
          'type' => 'registered_community',
          'identifier' => 'test_company',
        ],
      ]),
    );

    $argumentTest = function (array $documentContent) {
      $this->assertEquals('2025-01-01', $documentContent['delete_after']);
      return TRUE;
    };

    $atv
      ->patchDocument($doc->getId(), Argument::that($argumentTest))
      ->shouldBeCalled()
      ->willReturn(TRUE);

    $sut->saveGrantsProfile([], deleteAfter: new \DateTimeImmutable('2025-01-01'));
  }

  /**
   * Tests updating grants profile.
   */
  public function testProfileUpdate(): void {
    $doc = AtvDocument::create([
      'id' => 'test-document-1',
      'transaction_id' => '123',
      'type' => 'grants_profile',
      'delete_after' => '2020-01-01',
      'content' => [
        'bankAccounts' => [],
      ],
      'metadata' => [],
      'attachments' => [],
    ]);

    $atv = $this->prophesize(AtvService::class);

    // searchDocuments return [] -> profile does not exist.
    $atv->searchDocuments(Argument::any(), Argument::any())
      ->willReturn([$doc]);

    $sut = $this->getSut(
      atvService: $atv->reveal(),
      grantsProfileCache: $this->createMockedGrantsProfileCache([
        'selected_company' => [
          'companyName' => 'Test',
          'type' => 'registered_community',
          'identifier' => 'test_company',
        ],
      ]),
    );

    // Regular update just set delete_after to some future date.
    $argumentTest = function (array $documentContent) {
      $this->assertGreaterThan(
        new \DateTimeImmutable('now'),
        new \DateTimeImmutable($documentContent['delete_after'])
      );
      return TRUE;
    };

    $atv
      ->patchDocument($doc->getId(), Argument::that($argumentTest))
      ->shouldBeCalled()
      ->willReturn(TRUE);

    $sut->saveGrantsProfile([]);
  }

  /**
   * Tests saveGrantsProfile().
   */
  public function testAttachmentCleaning(): void {
    $doc = AtvDocument::create([
      'id' => 'test-document-1',
      'transaction_id' => '123',
      'type' => 'grants_profile',
      'content' => [],
      'metadata' => [],
      'attachments' => [
        [
          'id' => '321',
          'filename' => 'foo.bar',
        ],
      ],
    ]);
    $atv = $this->prophesize(AtvService::class);
    $atv
      ->searchDocuments(Argument::any(), TRUE)
      ->willReturn([$doc]);
    $atv->patchDocument($doc->getId(), Argument::any())
      ->willReturn($doc);
    $cache = $this->createMockedGrantsProfileCache([
      'selected_company' => [
        'companyName' => 'Test',
        'type' => 'registered_community',
        'identifier' => 'test_company',
      ],
    ]);
    $auditLog = $this->prophesize(AuditLogService::class);

    $sut = $this->getSut(
      atvService: $atv->reveal(),
      grantsProfileCache: $cache,
      auditLogService: $auditLog->reveal()
    );

    // Should delete extra document.
    $atv->deleteAttachment($doc->getId(), '321')->shouldBeCalled();

    // Should dispatch audit log event.
    $auditLog->dispatchEvent(Argument::any())->shouldBeCalled();

    // Should remove existing attachments since
    // update does not contain documents.
    $sut->saveGrantsProfile([
      'bankAccounts' => [],
    ], cleanAttachments: TRUE);
  }

  /**
   * Gets service under test.
   */
  private function getSut(
    ?AtvService $atvService = NULL,
    ?MessengerInterface $messenger = NULL,
    ?ProfileConnector $profileConnector = NULL,
    ?LoggerInterface $logger = NULL,
    ?GrantsProfileCache $grantsProfileCache = NULL,
    ?UuidInterface $uuid = NULL,
    ?AuditLogServiceInterface $auditLogService = NULL,
  ): GrantsProfileService {
    if (!$atvService) {
      $service = $this->prophesize(AtvService::class);
      $atvService = $service->reveal();
    }

    if (!$messenger) {
      $service = $this->prophesize(MessengerInterface::class);
      $messenger = $service->reveal();
    }

    if (!$profileConnector) {
      $service = $this->prophesize(ProfileConnector::class);
      $profileConnector = $service->reveal();
    }

    if (!$logger) {
      $service = $this->prophesize(LoggerInterface::class);
      $logger = $service->reveal();
    }

    if (!$grantsProfileCache) {
      $service = $this->prophesize(GrantsProfileCache::class);
      $grantsProfileCache = $service->reveal();
    }

    if (!$uuid) {
      $uuid = new Uuid();
    }

    if (!$auditLogService) {
      $service = $this->prophesize(AuditLogServiceInterface::class);
      $auditLogService = $service->reveal();
    }

    return new GrantsProfileService(
      $atvService,
      $messenger,
      $profileConnector,
      $logger,
      $grantsProfileCache,
      $uuid,
      $auditLogService
    );
  }

  /**
   * Creates grants profile cache mock.
   */
  private function createMockedGrantsProfileCache(array $content): GrantsProfileCache {
    $service = $this->prophesize(RequestStack::class);
    $request = $this->prophesize(Request::class);
    $session = $this->prophesize(SessionInterface::class);

    $service->getCurrentRequest()->willReturn($request->reveal());
    $request->getSession()->willReturn($session->reveal());

    $session->get(Argument::any())->will(static fn ($args) => $content[$args[0]] ?? NULL);
    $session->set(Argument::any(), Argument::any())->will(static fn ($args) => $content[$args[0]] = $args[1]);

    return new GrantsProfileCache($service->reveal());

  }

}
