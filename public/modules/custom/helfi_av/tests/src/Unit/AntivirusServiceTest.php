<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_av\Unit;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\helfi_av\AntivirusException;
use Drupal\helfi_av\AntivirusService;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Response;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;

/**
 * Unit tests for antivirus service.
 */
class AntivirusServiceTest extends UnitTestCase {

  use ApiTestTrait;
  use ProphecyTrait;

  /**
   * Tests the service.
   */
  public function testAntivirusService(): void {
    $sut = $this->getSut([
      new Response(body: '{"success":true,"data":{"result":[{"name":"safe.pdf","is_infected":false,"viruses":[]}]}}'),
    ]);

    $this->assertTrue($sut->scan(['safe.pdf' => 'blob']));
  }

  /**
   * Tests scan failure.
   */
  public function testScanFailure(): void {
    $sut = $this->getSut([
      new Response(body: '{"success":true,"data":{"result":[{"name":"safe.pdf","is_infected":false,"viruses":[]}, {"name":"evil.pdf","is_infected":true,"viruses":["something horrible"]}]}}'),
    ]);

    $this->expectException(AntivirusException::class);
    $sut->scan(['safe.pdf' => 'blob', 'evil.pdf' => 'blob']);
  }

  /**
   * Tests network failure.
   */
  public function testNetworkfailure(): void {
    $sut = $this->getSut([
      new ConnectException('test-failure', $this->prophesize(RequestInterface::class)->reveal()),
    ]);

    $this->expectException(AntivirusException::class);
    $sut->scan(['blob']);
  }

  /**
   * Gets service under test.
   */
  public function getSut(array $responses): AntivirusService {
    $config = $this->prophesize(ImmutableConfig::class);
    $config->get('base_url')->willReturn('https://example.com');

    $configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $configFactory->get('helfi_av.settings')->willReturn($config->reveal());

    return new AntivirusService($this->createMockHttpClient($responses), $configFactory->reveal());
  }

}
