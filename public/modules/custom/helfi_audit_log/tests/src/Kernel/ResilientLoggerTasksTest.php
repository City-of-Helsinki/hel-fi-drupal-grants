<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_audit_log\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\helfi_audit_log\AuditLogServiceInterface;
use Drupal\helfi_audit_log\ResilientLoggerTasks;
use Drupal\helfi_audit_log\Sources\HelfiAuditLogSource;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ResilientLogger\ResilientLogger;
use ResilientLogger\Targets\ElasticsearchLogTarget;

/**
 * Tests the cron-driven ResilientLoggerTasks pipeline end-to-end.
 */
#[Group('helfi_audit_log')]
#[RunTestsInSeparateProcesses]
class ResilientLoggerTasksTest extends KernelTestBase {

  use ApiTestTrait;

  private const int RETENTION_DAYS = 30;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'helfi_audit_log',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('helfi_audit_log', ['helfi_audit_logs']);

    // Configure resilient_logger to mirror all.settings.php.
    new Settings(Settings::getAll() + [
      'resilient_logger' => [
        'sources' => [
          ['class' => HelfiAuditLogSource::class],
        ],
        'targets' => [
          [
            'class' => ElasticsearchLogTarget::class,
            'es_url' => 'https://fake-es:9200',
            'es_username' => 'user',
            'es_password' => 'pass',
            'es_index' => 'audit-test',
            'required' => TRUE,
          ],
        ],
        'environment' => 'test',
        'origin' => 'helfi-audit-log-test',
        'store_old_entries_days' => self::RETENTION_DAYS,
      ],
    ]);
  }

  /**
   * Test that handleSubmitUnsentEntries ships rows to Elasticsearch.
   */
  public function testHandleSubmitUnsentEntriesShipsToElasticsearch(): void {
    $this->container->get(AuditLogServiceInterface::class)->logOperation([
      'operation' => 'TEST_OP',
      'status' => 'OK',
      'actor' => ['role' => 'TEST'],
      'target' => ['id' => '42'],
      'date_time' => gmdate('c'),
    ], 'DRUPAL');

    $history = [];
    $this->swapElasticsearchClient($history, [
      new GuzzleResponse(201, [
        'Content-Type' => 'application/json',
        'X-Elastic-Product' => 'Elasticsearch',
      ], json_encode([
        '_index' => 'audit-test',
        '_id' => 'irrelevant',
        'result' => 'created',
      ], flags: JSON_THROW_ON_ERROR)),
    ]);

    $this->container->get(ResilientLoggerTasks::class)
      ->handleSubmitUnsentEntries(time());

    // The single seeded row was shipped over HTTP.
    $this->assertCount(1, $history);
    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $history[0]['request'];
    $this->assertSame('PUT', $request->getMethod());
    $this->assertStringStartsWith('/audit-test/_doc/', $request->getUri()->getPath());
    $this->assertStringContainsString('op_type=create', $request->getUri()->getQuery());

    $payload = json_decode((string) $request->getBody(), TRUE, flags: JSON_THROW_ON_ERROR);
    $this->assertSame('TEST_OP', $payload['audit_event']['operation']);
    $this->assertSame('helfi-audit-log-test', $payload['audit_event']['origin']);

    // The row was marked sent.
    $isSent = $this->container->get('database')
      ->select('helfi_audit_logs', 'h')
      ->fields('h', ['is_sent'])
      ->execute()
      ->fetchField();
    $this->assertSame('1', (string) $isSent);
  }

  /**
   * Test that handleClearSentEntries deletes old sent rows.
   */
  public function testHandleClearSentEntriesRespectsRetentionWindow(): void {
    $now = time();
    $oldTs = gmdate('Y-m-d H:i:s', $now - (self::RETENTION_DAYS * 2 * 86400));
    $newTs = gmdate('Y-m-d H:i:s', $now);

    $oldSentId = $this->insertRow($oldTs, 1);
    $oldUnsentId = $this->insertRow($oldTs, 0);
    $newSentId = $this->insertRow($newTs, 1);
    $newUnsentId = $this->insertRow($newTs, 0);

    $this->container->get(ResilientLoggerTasks::class)
      ->handleClearSentEntries(time());

    $remaining = array_map(
      'intval',
      $this->container->get('database')
        ->select('helfi_audit_logs', 'h')
        ->fields('h', ['id'])
        ->execute()
        ->fetchCol()
    );
    sort($remaining);

    $expected = [$oldUnsentId, $newSentId, $newUnsentId];
    sort($expected);

    $this->assertSame($expected, $remaining, 'Only the old-and-sent row should be deleted.');
    $this->assertNotContains($oldSentId, $remaining);
  }

  /**
   * Mock the Elasticsearch HTTP transport on the configured target.
   *
   * @param array<mixed> $history
   *   Captured by Middleware::history; populated with each PSR-7 request.
   * @param \Psr\Http\Message\ResponseInterface[] $responses
   *   PSR-7 responses.
   */
  private function swapElasticsearchClient(array &$history, array $responses): void {
    $guzzle = $this->createMockHistoryMiddlewareHttpClient($history, $responses);
    $fakeEs = ClientBuilder::create()
      ->setHosts(['https://fake-es:9200'])
      ->setBasicAuthentication('user', 'pass')
      ->setHttpClient($guzzle)
      ->build();

    $logger = $this->container->get(ResilientLogger::class);
    [$target] = $logger->getTargets();
    $this->assertInstanceOf(ElasticsearchLogTarget::class, $target);

    (new \ReflectionProperty(ElasticsearchLogTarget::class, 'client'))
      ->setValue($target, $fakeEs);
  }

  /**
   * Inserts a row directly so created_at and is_sent can be controlled.
   */
  private function insertRow(string $createdAt, int $isSent): int {
    return (int) $this->container->get('database')
      ->insert('helfi_audit_logs')
      ->fields([
        'created_at' => $createdAt,
        'is_sent' => $isSent,
        'message' => '{"audit_event":{}}',
      ])
      ->execute();
  }

}
