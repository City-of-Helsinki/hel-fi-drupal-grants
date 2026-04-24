<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_helsinki_profiili\Unit;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\helfi_api_base\Traits\ApiTestTrait;
use Drupal\Tests\helfi_api_base\Traits\EnvironmentResolverTrait;
use Drupal\helfi_api_base\Environment\EnvironmentEnum;
use Drupal\helfi_api_base\Environment\Project;
use Drupal\helfi_helsinki_profiili\DTO\OpenIdConfiguration;
use Drupal\helfi_helsinki_profiili\DTO\ProfiiliToken;
use Drupal\helfi_helsinki_profiili\ProfileDataException;
use Drupal\helfi_helsinki_profiili\ProfiiliApiClient;
use Drupal\helfi_helsinki_profiili\ProfiiliException;
use Drupal\helfi_helsinki_profiili\TokenExpiredException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\RequestInterface;

/**
 * Tests for ProfiiliApiClient.
 */
#[Group('helfi_helsinki_profiili')]
class ProfiiliApiClientTest extends UnitTestCase {

  use ApiTestTrait;
  use ProphecyTrait;
  use EnvironmentResolverTrait;

  /**
   * Constructs a ProfiiliApiClient instance for testing.
   */
  private function getSut(
    ?ClientInterface $client = NULL,
    ?string $tunnistamoUrl = 'https://tunnistamo.example.com',
    ?string $profileEndpoint = 'https://profile.example.com/graphql',
    EnvironmentEnum $environment = EnvironmentEnum::Test,
    ?CacheBackendInterface $cache = NULL,
  ): ProfiiliApiClient {
    $client ??= $this->createMockHttpClient([]);

    $configFactory = $this->getConfigFactoryStub([
      'helfi_helsinki_profiili.settings' => [
        'tunnistamo_environment_url' => $tunnistamoUrl,
        'userinfo_profile_endpoint' => $profileEndpoint,
      ],
    ]);

    $cache ??= new MemoryBackend($this->prophesize(TimeInterface::class)->reveal());

    return new ProfiiliApiClient(
      $cache,
      $client,
      $configFactory,
      $this->getEnvironmentResolver(Project::ASUMINEN, $environment),
    );
  }

  /**
   * Helper to build an openid-configuration JSON response.
   */
  private function openIdConfigResponse(): Response {
    return new Response(200, body: json_encode([
      'token_endpoint' => 'https://tunnistamo.example.com/token',
      'jwks_uri' => 'https://tunnistamo.example.com/jwks',
    ], JSON_THROW_ON_ERROR));
  }

  /**
   * Tests fetching and caching openid configuration.
   */
  public function testGetOpenIdConfiguration(): void {
    $client = $this->createMockHttpClient([
      $this->openIdConfigResponse(),
      // Second request would throw OutOfBoundsException if cache is missed.
    ]);
    $sut = $this->getSut($client);

    $config = $sut->getOpenIdConfiguration();
    $this->assertInstanceOf(OpenIdConfiguration::class, $config);
    $this->assertEquals('https://tunnistamo.example.com/token', $config->token_endpoint);
    $this->assertEquals('https://tunnistamo.example.com/jwks', $config->jwks_uri);

    $second = $sut->getOpenIdConfiguration();
    $this->assertEquals('https://tunnistamo.example.com/token', $second->token_endpoint);
  }

  /**
   * Tests exception when no tunnistamo url is configured.
   */
  public function testGetOpenIdConfigurationNoUrl(): void {
    $client = $this->createMockHttpClient([]);
    $sut = $this->getSut($client, tunnistamoUrl: NULL);

    $this->expectException(ProfiiliException::class);
    $this->expectExceptionMessage('No tunnistamo environment url set');
    $sut->getOpenIdConfiguration();
  }

  /**
   * Tests exception on HTTP failure during openid config fetch.
   */
  public function testGetOpenIdConfigurationHttpFailure(): void {
    $sut = $this->getSut($this->createMockHttpClient([
      new RequestException('Connection failed', new Request('GET', '/openid-configuration')),
    ]));

    $this->expectException(ProfiiliException::class);
    $this->expectExceptionMessage('Failed to fetch openid configuration');
    $sut->getOpenIdConfiguration();
  }

  /**
   * Tests fetching and caching JWKS.
   */
  public function testGetJsonWebKeySet(): void {
    $sut = $this->getSut($this->createMockHttpClient([
      $this->openIdConfigResponse(),
      new Response(200, body: json_encode(['keys' => [['kty' => 'RSA']]], JSON_THROW_ON_ERROR)),
    ]));

    $jwks = $sut->getJsonWebKeySet();
    $this->assertArrayHasKey('keys', $jwks);

    $second = $sut->getJsonWebKeySet();
    $this->assertArrayHasKey('keys', $second);
  }

  /**
   * Tests exception on HTTP failure during JWKS fetch.
   */
  public function testGetJsonWebKeySetHttpFailure(): void {
    $client = $this->createMockHttpClient([
      $this->openIdConfigResponse(),
      new RequestException('Timeout', $this->prophesize(RequestInterface::class)->reveal()),
    ]);
    $sut = $this->getSut($client);

    $this->expectException(ProfiiliException::class);
    $this->expectExceptionMessage('Failed to fetch jwks');
    $sut->getJsonWebKeySet();
  }

  /**
   * Tests fetching a Helsinki Profiili token with correct request params.
   */
  public function testGetHelsinkiProfiiliToken(): void {
    $requests = [];
    $client = $this->createMockHistoryMiddlewareHttpClient($requests, [
      $this->openIdConfigResponse(),
      new Response(200, body: json_encode([
        'access_token' => 'profiili-token-123',
        'expires_in' => 3600,
      ], JSON_THROW_ON_ERROR)),
    ]);
    $sut = $this->getSut($client);

    $token = $sut->getHelsinkiProfiiliToken('auth-token');
    $this->assertInstanceOf(ProfiiliToken::class, $token);
    $this->assertEquals('profiili-token-123', $token->access_token);
    $this->assertEquals(3600, $token->expires_in);

    // Verify the token request included the auth header and form params.
    $tokenRequest = $requests[1]['request'];
    $this->assertEquals('Bearer auth-token', $tokenRequest->getHeaderLine('Authorization'));
    $body = (string) $tokenRequest->getBody();
    $this->assertStringContainsString('grant_type=urn', $body);
    $this->assertStringContainsString('permission=%23access', $body);
  }

  /**
   * Tests that expired auth token throws TokenExpiredException.
   */
  public function testGetHelsinkiProfiiliTokenExpired(): void {
    $client = $this->createMockHttpClient([
      $this->openIdConfigResponse(),
      new RequestException('Unauthorized', $this->prophesize(RequestInterface::class)->reveal()),
    ]);
    $sut = $this->getSut($client);

    $this->expectException(TokenExpiredException::class);
    $sut->getHelsinkiProfiiliToken('expired-token');
  }

  /**
   * Tests getUserProfileData makes correct HTTP requests.
   */
  public function testGetUserProfileDataRequests(): void {
    $requests = [];
    $client = $this->createMockHistoryMiddlewareHttpClient($requests, [
      $this->openIdConfigResponse(),
      new Response(200, body: json_encode([
        'access_token' => 'profiili-token',
        'expires_in' => 3600,
      ], JSON_THROW_ON_ERROR)),
      new Response(200, body: json_encode([
        'data' => [
          'myProfile' => [
            'firstName' => 'Test',
            'lastName' => 'User',
            'primaryEmail' => ['email' => 'test@example.com', 'primary' => TRUE],
            'primaryPhone' => NULL,
            'primaryAddress' => NULL,
            'emails' => ['edges' => []],
            'phones' => ['edges' => []],
            'addresses' => ['edges' => []],
          ],
        ],
      ], JSON_THROW_ON_ERROR)),
    ]);
    $sut = $this->getSut($client);

    $result = $sut->getUserProfileData('auth-token');
    $this->assertIsArray($result);
    $this->assertArrayHasKey('myProfile', $result);

    // Verify three requests: openid config, token, profile.
    $this->assertCount(3, $requests);
    $this->assertEquals('GET', $requests[0]['request']->getMethod());
    $this->assertEquals('POST', $requests[1]['request']->getMethod());
    $this->assertEquals('POST', $requests[2]['request']->getMethod());
    $this->assertEquals('Bearer profiili-token', $requests[2]['request']->getHeaderLine('Authorization'));
    $this->assertStringContainsString('myProfile', (string) $requests[2]['request']->getBody());
  }

  /**
   * Tests exception when no profile endpoint is configured.
   */
  public function testGetUserProfileDataNoEndpoint(): void {
    $client = $this->createMockHttpClient([]);
    $sut = $this->getSut($client, profileEndpoint: NULL);

    $this->expectException(ProfiiliException::class);
    $this->expectExceptionMessage('No profile endpoint set');
    $sut->getUserProfileData('auth-token');
  }

  /**
   * Tests ProfileDataException when GraphQL response contains errors.
   */
  public function testGetUserProfileDataWithErrors(): void {
    $client = $this->createMockHttpClient([
      $this->openIdConfigResponse(),
      new Response(200, body: json_encode([
        'access_token' => 'token',
        'expires_in' => 3600,
      ], JSON_THROW_ON_ERROR)),
      new Response(200, body: json_encode([
        'errors' => [['message' => 'Something went wrong']],
      ], JSON_THROW_ON_ERROR)),
    ]);
    $sut = $this->getSut($client);

    $this->expectException(ProfileDataException::class);
    $sut->getUserProfileData('auth-token');
  }

  /**
   * Tests exception on HTTP failure during profile data fetch.
   */
  public function testGetUserProfileDataHttpFailure(): void {
    $client = $this->createMockHttpClient([
      $this->openIdConfigResponse(),
      new Response(200, body: json_encode([
        'access_token' => 'token',
        'expires_in' => 3600,
      ], JSON_THROW_ON_ERROR)),
      new RequestException('Server error', $this->prophesize(RequestInterface::class)->reveal()),
    ]);
    $sut = $this->getSut($client);

    $this->expectException(ProfiiliException::class);
    $this->expectExceptionMessage('/userinfo endpoint threw errorcode');
    $sut->getUserProfileData('auth-token');
  }

}
