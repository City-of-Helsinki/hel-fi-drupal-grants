<?php

declare(strict_types=1);

namespace Drupal\Tests\grants_application\Kernel;

use Drupal\grants_application\Avus2Exception;
use Drupal\grants_application\Avus2Integration;
use Drupal\helfi_atv\AtvDocument;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Tests Avus2Integration class.
 */
#[Group('grants_application')]
#[RunTestsInSeparateProcesses]
final class Avus2IntegrationTest extends KernelTestBase {

  use ApiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    putenv('AVUSTUS2_ENDPOINT=https://example.com/avus2');
    putenv('AVUSTUS2_USERNAME=user');
    putenv('AVUSTUS2_PASSWORD=pass');
    putenv('APP_ENV=testing');
  }

  /**
   * A successful request is sent without the react form data.
   */
  public function testSendToAvus2Success(): void {
    $history = [];
    $integration = $this->getIntegration([new Response(200)], $history);

    $integration->sendToAvus2($this->getSubmittedDocument(), 'TEST-058-0000001', 'save-id-123', FALSE);

    $this->assertCount(1, $history);
    /** @var \Psr\Http\Message\RequestInterface $request */
    $request = $history[0]['request'];
    $this->assertSame('POST', $request->getMethod());
    $this->assertSame(getenv('AVUSTUS2_ENDPOINT'), (string) $request->getUri());

    // The react form data must not be sent to Avus2.
    $sentContent = json_decode((string) $request->getBody(), TRUE);
    $this->assertArrayNotHasKey('form_data', $sentContent);
    $this->assertArrayNotHasKey('form_data', $sentContent['compensation']);
    $this->assertSame('avus2 data', $sentContent['compensation']['real']);

    // The request carries the expected headers.
    $this->assertSame('SUBMITTED', $request->getHeaderLine('X-Case-Status'));
    $this->assertSame('USER', $request->getHeaderLine('X-hki-UpdateSource'));
    $this->assertSame('TEST', $request->getHeaderLine('X-hki-appEnv'));
    $this->assertSame('TEST-058-0000001', $request->getHeaderLine('X-hki-applicationNumber'));
    $this->assertSame('save-id-123', $request->getHeaderLine('X-hki-saveId'));
    $this->assertSame('Basic ' . base64_encode('user:pass'), $request->getHeaderLine('Authorization'));
  }

  /**
   * A non-200 response or transport error is wrapped in an Avus2Exception.
   */
  #[TestWith(['response', 'integration rejected the application'])]
  #[TestWith(['transport', 'connection refused'])]
  public function testSendToAvus2Failures(string $kind, string $detail): void {
    $failure = $kind === 'transport'
      ? new ConnectException($detail, new Request('POST', 'https://example.com/avus2'))
      : new Response(201, [], $detail);

    $history = [];
    $integration = $this->getIntegration([$failure], $history);

    $this->expectException(Avus2Exception::class);
    $integration->sendToAvus2($this->getSubmittedDocument(), 'TEST-058-0000001', 'save-id-123', FALSE);
  }

  /**
   * Build the integration with a history-recording mock HTTP client.
   *
   * @param \Psr\Http\Message\ResponseInterface[]|\GuzzleHttp\Exception\GuzzleException[] $responses
   *   The queued responses.
   * @param array<mixed> $history
   *   The captured request/response history.
   */
  private function getIntegration(array $responses, array &$history): Avus2Integration {
    $client = $this->createMockHistoryMiddlewareHttpClient($history, $responses);

    return new Avus2Integration(
      $this->container->get('grants_metadata.atv_schema'),
      $client,
    );
  }

  /**
   * Build a submitted document with react form data mixed into the content.
   */
  private function getSubmittedDocument(): AtvDocument {
    $document = AtvDocument::create([
      'id' => 'test-id',
      'type' => 'type',
      'status' => ['value' => 'SUBMITTED'],
      'transaction_id' => '1234567890',
      'business_id' => '1234567-1',
      'tos_function_id' => '12345',
      'tos_record_id' => '54321',
      'metadata' => '{"name": "Name", "value": "Value"}',
      'created_at' => '2024-06-06',
      'updated_at' => '2024-06-07',
      'user_id' => 'userId',
      'document_language' => 'fi',
    ]);
    $document->setContent([
      'form_data' => ['react' => 'only data'],
      'compensation' => [
        'real' => 'avus2 data',
        'form_data' => ['react' => 'nested data'],
      ],
    ]);

    return $document;
  }

}
